<?php
declare(strict_types=1);

namespace App\Controller\Admin\Api;

use App\Controller\Base\API\Manage;
use App\Entity\Query\Get;
use App\Interceptor\ManageSession;
use App\Model\Business;
use App\Model\Config as CFG;
use App\Model\ManageLog;
use App\Service\Email;
use App\Service\Query;
use App\Service\Sms;
use App\Util\Client;
use App\Util\Date;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Kernel\Annotation\Inject;
use Kernel\Annotation\Interceptor;
use Kernel\Context\Interface\Request;
use Kernel\Exception\JSONException;
use Kernel\Exception\RuntimeException;
use Kernel\Waf\Filter;
use PHPMailer\PHPMailer\PHPMailer;

#[Interceptor(ManageSession::class, Interceptor::TYPE_API)]
class Config extends Manage
{

    #[Inject]
    private Query $query;

    #[Inject]
    private Sms $sms;

    #[Inject]
    private Email $email;

    /**
     * @param Request $request
     * @return array
     * @throws JSONException
     * @throws \Throwable
     */
    public function setting(Request $request): array
    {
        $post = $request->post(flags: Filter::NORMAL);
        $keys = ["closed_message", "background_mobile_url", "closed", "username_len", "user_theme", "user_mobile_theme", "background_url", "shop_name", "title", "description", "keywords", "registered_state", "registered_type", "registered_verification", "registered_phone_verification", "registered_email_verification", "login_verification", "forget_type", "notice", "trade_verification", "session_expire"]; //全部字段
        $inits = ["closed", "registered_state", "registered_type", "registered_verification", "registered_phone_verification", "registered_email_verification", "login_verification", "forget_type", "trade_verification", "session_expire"]; //需要初始化的字段

        // 优先使用前端上传时带回的 base64（多实例/无持久盘下最可靠）
        $logoBase64 = isset($post['logo_base64']) ? trim((string)$post['logo_base64']) : '';
        if ($logoBase64 !== '') {
            // 表单 application/x-www-form-urlencoded 会把 base64 里的 + 转成空格，导致解码后图片损坏
            $logoBase64 = str_replace(' ', '+', $logoBase64);
            $decoded = @base64_decode($logoBase64, true);
            if ($decoded !== false && $decoded !== '') {
                $mime = isset($post['logo_mime']) ? trim((string)$post['logo_mime']) : 'image/png';
                CFG::put('logo_data', $logoBase64);
                CFG::put('logo_mime', $mime);
                CFG::put('logo_updated_at', (string)time());
            }
        } else {
            $file = isset($post['logo']) ? trim((string)$post['logo']) : '';
            if ($file !== '' && (str_starts_with($file, 'http://') || str_starts_with($file, 'https://'))) {
                $file = (string)parse_url($file, PHP_URL_PATH);
            }
            $raw = null;
            $mime = 'image/png';
            if ($file !== '' && $file !== '/favicon.ico') {
                $absPath = BASE_PATH . $file;
                if (is_file($absPath)) {
                    @copy($absPath, BASE_PATH . '/favicon.ico');
                    $raw = @file_get_contents($absPath);
                    if (function_exists('finfo_open') && $raw !== false) {
                        $fi = finfo_open(FILEINFO_MIME_TYPE);
                        if ($fi) {
                            $mime = (string)finfo_file($fi, $absPath) ?: $mime;
                            finfo_close($fi);
                        }
                    }
                    @unlink($absPath);
                } else {
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                        ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'] ?? '';
                    if ($host !== '') {
                        $imageUrl = $scheme . '://' . $host . $file;
                        $ctx = stream_context_create([
                            'http' => ['timeout' => 10],
                            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
                        ]);
                        $raw = @file_get_contents($imageUrl, false, $ctx);
                        if ($raw !== false && $raw !== '') {
                            $fi = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
                            if ($fi) {
                                $mime = (string)@finfo_buffer($fi, $raw) ?: $mime;
                                finfo_close($fi);
                            }
                        }
                    }
                }
                if ($raw !== false && $raw !== '') {
                    CFG::put('logo_data', base64_encode($raw));
                    CFG::put('logo_mime', $mime);
                    CFG::put('logo_updated_at', (string)time());
                }
            }
        }
        try {
            if (isset($post['ip_get_mode'])) {
                Client::setClientMode((int)$post['ip_get_mode']);
            }

            foreach ($keys as $index => $key) {
                if (in_array($key, $inits)) {
                    if (!isset($post[$key])) {
                        $post[$key] = 0;
                    }
                }
                CFG::put($key, $post[$key]);
            }
        } catch (\Exception $e) {
            throw new JSONException("保存失败，请检查原因");
        }

        _plugin_start($post['user_theme'], true);
        ManageLog::log($this->getManage(), "修改了网站设置");
        return $this->json(200, '保存成功');
    }

    /**
     * @return array
     * @throws JSONException
     */
    public function other(): array
    {
        $map = $this->request->post(flags: Filter::NORMAL);
        $keys = ["recharge_min", "commodity_recommend", "commodity_name", "recharge_max", "cname", "default_category", "callback_domain", "recharge_welfare_config", "recharge_welfare", "substation_display", "domain", "service_url", "service_qq", "cash_type_alipay", "cash_type_wechat", "cash_type_balance", "cash_cost", "cash_min"]; //全部字段
        $inits = ["recharge_min", "commodity_recommend", "recharge_max", "recharge_welfare", "substation_display", "cash_type_alipay", "cash_type_wechat", "cash_type_balance", "cash_cost", "cash_min", "default_category"]; //需要初始化的字段

        if (!empty($map['recharge_welfare_config'])) {
            $explode = explode(PHP_EOL, trim($map['recharge_welfare_config'], PHP_EOL));
            foreach ($explode as $item) {
                $def = explode("-", $item);
                if (count($def) != 2) {
                    throw new JSONException("充值赠送配置规则表达式错误");
                }
            }
        }

        try {
            foreach ($keys as $index => $key) {
                if (in_array($key, $inits)) {
                    if (!isset($map[$key])) {
                        $map[$key] = 0;
                    }
                }
                CFG::put($key, $map[$key]);
            }
        } catch (\Exception $e) {
            throw new JSONException("保存失败，请检查原因");
        }

        ManageLog::log($this->getManage(), "修改了其他设置");
        return $this->json(200, '保存成功');
    }


    /**
     * @return array
     * @throws RuntimeException
     */
    public function setSubstationDisplayList(): array
    {
        $userId = (int)$_POST['id'];
        $type = (int)$_POST['type'];
        $list = json_decode(CFG::get("substation_display_list"), true);
        if ($type == 0) {
            //添加过滤
            if (!in_array($userId, $list)) {
                $list[] = $userId;
            }
        } else {
            //解除过滤
            if (($key = array_search($userId, $list)) !== false) {
                unset($list[$key]);
                $list = array_values($list);
            }
        }

        ManageLog::log($this->getManage(), "修改了子站显示列表");
        CFG::put("substation_display_list", json_encode($list));
        return $this->json(200, "成功", $list);
    }

    /**
     * @throws JSONException
     */
    public function sms(): array
    {
        try {
            CFG::put("sms_config", json_encode($_POST));
        } catch (\Exception $e) {
            throw new JSONException("保存失败，请检查原因");
        }

        ManageLog::log($this->getManage(), "修改了短信配置");
        return $this->json(200, '保存成功');
    }

    /**
     * @throws JSONException
     */
    public function email(): array
    {
        try {
            CFG::put("email_config", json_encode($_POST));
        } catch (\Exception $e) {
            throw new JSONException("保存失败，请检查原因");
        }

        ManageLog::log($this->getManage(), "修改了邮件配置");
        return $this->json(200, '保存成功');
    }


    public function smsTest(): array
    {
        $this->sms->sendCaptcha($_POST['phone'], Sms::CAPTCHA_REGISTER);

        ManageLog::log($this->getManage(), "测试了短信发送");
        return $this->json(200, "短信发送成功");
    }

    /**
     * @return array
     * @throws JSONException
     * @throws RuntimeException
     */
    public function emailTest(): array
    {
        $shopName = CFG::get("shop_name");
        $result = $this->email->send($_POST['email'], $shopName . "-手动测试邮件", '测试邮件，发送时间：' . Date::current());
        if (!$result) {
            throw new JSONException("发送失败");
        }
        ManageLog::log($this->getManage(), "测试了邮件发送");
        return $this->json(200, "成功!");
    }

    /**
     * @return array
     */
    public function getBusiness(): array
    {
        $get = new Get(Business::class);
        $get->setPaginate((int)$this->request->post("page"), (int)$this->request->post("limit"));
        $data = $this->query->get($get, function (Builder $builder) {
            return $builder->with(['user' => function (Relation $relation) {
                $relation->with(['businessLevel'])->select(["id", "business_level", "username", "avatar"]);
            }]);
        });
        return $this->json(data: $data);
    }
}