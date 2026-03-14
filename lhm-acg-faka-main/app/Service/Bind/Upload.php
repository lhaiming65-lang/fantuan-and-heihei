<?php
declare(strict_types=1);

namespace App\Service\Bind;


use Kernel\Util\Date;
use Kernel\Util\File;

class Upload implements \App\Service\Upload
{

    /**
     * @param string $path
     * @param string $type
     * @param int|null $userId
     * @return void
     */
    public function add(string $path, string $type, ?int $userId = null): void
    {
        if (!is_file(BASE_PATH . $path)) {
            return;
        }
        $upload = new \App\Model\Upload();
        $upload->hash = md5_file(BASE_PATH . $path);
        $upload->type = $type;
        $upload->path = $path;
        $upload->create_time = Date::current();
        $userId && ($upload->user_id = $userId);
        $upload->save();
    }


    /**
     * @param string $hash
     * @return string|null
     */
    public function get(string $hash): ?string
    {
        return (\App\Model\Upload::query()->where("hash", $hash)->first())?->path;
    }

    /**
     * @param string $path
     * @return void
     */
    public function remove(string $path): void
    {
        if (!is_file(BASE_PATH . $path)) {
            return;
        }

        $hash = md5_file(BASE_PATH . $path);
        \App\Model\Upload::query()->where("hash", $hash)->delete(); //删除数据库
        File::remove(BASE_PATH . $path);
    }

    public function handle($upload, $dir, $type, int $size = 10000, string $fileName = ''): mixed
    {
        if (!is_array($upload)) {
            return "请选择文件";
        }

        //单文件处理
        if (count($upload) == count($upload, 1)) {
            $load = self::error($upload, $type, $size);
            if (is_array($load)) {
                //上传文件
                return self::move($load, $dir, $fileName);
            } else {
                return $load;
            }
        } else {
            //多文件初始化
            $list = array();
            //多文件处理
            for ($i = 0; $i < count($upload); $i++) {

                $load = self::error($upload[$i], $type, $size);
                if (is_array($load)) {
                    //上传文件
                    $move = self::move($load, $dir, $fileName);
                    //上传成功加入数组
                    if (is_array($move)) {
                        $list[] = $move;
                    }
                }

            }
            return $list;
        }
    }

    //抛异常
    private static function error($upload, $type, $size)
    {
        //异常代码处理
        if ($upload['error'] > 0) {
            switch ($upload['error']) {
                case 1:
                    $err_info = "文件超过服务器允许大小，请上传5MB以内的图片";
                    break;
                case 2:
                    $err_info = "文件太大,无法上传";
                    break;
                case 3:
                    $err_info = "上传失败,文件可能损坏";
                    break;
                case 4:
                    $err_info = "上传失败,请选择需要上传的文件";
                    break;
                case 6:
                    $err_info = "上传失败,无写入权限";
                    break;
                case 7:
                    $err_info = "上传失败,文件写入失败";
                    break;
                default:
                    $err_info = "未知的上传错误";
                    break;
            }
            return $err_info;
        }
        //文件类型处理
        $exp = explode(".", (string)$upload['name']);

        //判断文件数组是否大于2
        if (count($exp) < 2) return "文件无后缀无法识别";

        //最后一个值必定是后缀
        $fix = $exp[count($exp) - 1];
        if (!in_array(strtolower($fix), $type)) return '不支持该后缀的文件:' . $type;

        //文件大小限制
        $upload_size = $upload['size'] / 1024;
        if ($upload_size > $size) return '文件太大';

        return array('tmp' => $upload['tmp_name'], 'size' => $upload_size, 'name' => $upload['name'], 'fix' => $fix);
    }

    //开始处理文件
    private static function move($array, $dir, $file_name)
    {
        $tmpPath = $array['tmp'] ?? '';
        if (!is_string($tmpPath) || $tmpPath === '' || !is_uploaded_file($tmpPath)) {
            return '文件上传失败：临时文件无效或已过期';
        }
        // 统一目录分隔符，避免 Windows 下路径问题
        $dir = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dir), DIRECTORY_SEPARATOR);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                return '文件上传失败：无法创建上传目录，请检查权限';
            }
        }
        if (!is_writable($dir)) {
            return '文件上传失败：上传目录不可写，请检查权限';
        }
        $names = date("YmdHis") . mt_rand(1000000, 9999999) . '.' . $array['fix'];
        if ($file_name != '') {
            $uniqueName = $dir . DIRECTORY_SEPARATOR . $file_name;
        } else {
            $uniqueName = $dir . DIRECTORY_SEPARATOR . $names;
        }
        if (move_uploaded_file($tmpPath, $uniqueName)) {
            return array('dir' => $uniqueName, 'size' => $array['size'], 'name' => $array['name'], 'new_name' => $names, 'ext' => $array['fix']);
        }
        if (!file_exists($tmpPath)) {
            return '文件上传失败：临时文件已丢失';
        }
        return '文件上传失败：无法写入目标目录，请检查权限或磁盘空间';
    }
}