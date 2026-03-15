<?php
declare(strict_types=1);

namespace App\Controller\Admin\Api;


use App\Controller\Base\API\Manage;
use App\Entity\Query\Get;
use App\Interceptor\ManageSession;
use App\Service\Image;
use App\Service\Query;
use Kernel\Annotation\Inject;
use Kernel\Annotation\Interceptor;
use Kernel\Context\Interface\Request;
use Kernel\Exception\JSONException;
use Kernel\Util\File;

/**
 * Class Upload
 * @package App\Controller\Admin\Api
 */
#[Interceptor(ManageSession::class, Interceptor::TYPE_API)]
class Upload extends Manage
{
    #[Inject]
    private \App\Service\Upload $upload;

    #[Inject]
    private Query $query;

    #[Inject]
    private Image $image;


    const MIME = ['image', 'video', 'doc', 'other'];


    /**
     * @param Request $request
     * @return array
     * @throws JSONException
     */
    /** 图片最大 5MB，其他类型 1000MB */
    private const IMAGE_MAX_SIZE_KB = 5120;
    private const OTHER_MAX_SIZE_KB = 1024000;

    public function send(Request $request): array
    {
        $type = strtolower((string)$request->get("mime"));
        $thumbHeight = (int)$request->get("thumb_height");

        if (!in_array($type, self::MIME)) {
            throw new JSONException("mime not supported");
        }
        if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'] ?? '')) {
            throw new JSONException("请选择要上传的文件");
        }
        $maxSizeKb = $type === 'image' ? self::IMAGE_MAX_SIZE_KB : self::OTHER_MAX_SIZE_KB;
        $static_path = "/assets/cache/general/{$type}/";
        $handle = $this->upload->handle($_FILES['file'], BASE_PATH . $static_path, ['jpg', 'png', 'jpeg', 'bmp', 'webp', 'ico', 'gif', 'mp4', 'zip', 'woff', 'woff2', 'ttf', 'otf'], $maxSizeKb);
        if (!is_array($handle)) {
            $msg = ($type === 'image' && $handle === '文件太大') ? '图片不能超过5MB' : $handle;
            throw new JSONException($msg);
        }

        $fileName = $static_path . $handle['new_name'];

        $md5 = md5_file(BASE_PATH . $fileName);
        $existingPath = $this->upload->get($md5);
        if ($existingPath && is_file(BASE_PATH . $existingPath)) {
            File::remove(BASE_PATH . $fileName);
            $fileName = $existingPath;
        } else {
            if ($existingPath) {
                $this->upload->remove($existingPath);
            }
            $this->upload->add($fileName, $type);
        }

        $append = [];
        //生成缩略图
        if ($type == self::MIME[0] && $thumbHeight > 0) {
            $imageFile = BASE_PATH . $fileName;
            $thumbUrl = $this->image->createThumbnail($fileName, $thumbHeight);
            if (!$thumbUrl) {
                if (is_file($imageFile)) {
                    $this->upload->remove($fileName);
                }
                throw new JSONException("图片上传失败，原因：生成缩略图失败");
            }
            $append['thumb_url'] = $thumbUrl;
        }

        return $this->json(200, '上传成功', ["url" => $fileName, "append" => $append]);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function get(Request $request): array
    {
        $map = $request->post();
        $get = new Get(\App\Model\Upload::class);
        $get->setPaginate((int)$this->request->post("page"), (int)$this->request->post("limit"));
        $get->setWhere($map);
        $get->setOrderBy('id', "desc");
        $data = $this->query->get($get);

        foreach ($data['list'] as &$item) {
            $baseImagePathInfo = pathinfo($item['path']);
            $thumbPath = $baseImagePathInfo['dirname'] . '/thumb/' . $baseImagePathInfo['basename'];
            if (is_file(BASE_PATH . $thumbPath)) {
                $item['thumb_url'] = $thumbPath;
            }
        }

        return $this->json(data: $data);
    }


    /**
     * 文件上传
     * @return array
     * @throws JSONException
     */
    public function handle(): array
    {
        if (!isset($_FILES['file'])) {
            throw new JSONException("请选择文件");
        }

        $handle = $this->upload->handle($_FILES['file'], BASE_PATH . '/assets/cache/images', ['jpg', 'png', 'jpeg', 'bmp', 'webp', 'ico', 'gif', 'mp4', 'zip', 'woff', 'woff2', 'ttf', 'otf'], self::OTHER_MAX_SIZE_KB);
        if (!is_array($handle)) {
            throw new JSONException($handle);
        }

        return $this->json(200, '上传成功', ['path' => '/assets/cache/images/' . $handle['new_name']]);
    }


    /**
     * 获取图像列表
     * @return array
     */
    public function images(): array
    {
        $page = (int)$_POST['page'];
        $limit = (int)$_POST['limit'];


        $path = BASE_PATH . '/assets/cache/images/';

        $list = (array)scandir($path, SCANDIR_SORT_DESCENDING);
        array_splice($list, -2);

        $ext = ['png', 'jpg', 'jpeg', 'ico'];
        foreach ($list as $index => $val) {
            $exp = explode(".", $val);
            if (!in_array(end($exp), $ext)) {
                unset($list[$index]);
            }
        }

        $list = array_values($list);
        $count = count($list);
        $offset = ($page - 1) * $limit;
        $data = array_slice($list, $offset, $limit);
        $json = $this->json(200, "success", $data);
        $json['count'] = $count;
        return $json;
    }
}