<?php
declare(strict_types=1);

namespace App\Pay\Epay\Impl;

use App\Entity\PayEntity;
use App\Pay\Base;

class Pay extends Base implements \App\Pay\Pay
{
    public function trade(): PayEntity
    {
        $gateway = rtrim($this->config['gateway'] ?? '', '/');
        $pid = $this->config['pid'] ?? '';
        $key = $this->config['key'] ?? '';

        $params = [
            'pid' => $pid,
            'type' => $this->code,
            'out_trade_no' => $this->tradeNo,
            'notify_url' => $this->callbackUrl,
            'return_url' => $this->returnUrl,
            'name' => $this->tradeNo,
            'money' => sprintf('%.2f', $this->amount),
        ];

        $params['sign'] = self::generateSign($params, $key);
        $params['sign_type'] = 'MD5';

        $url = $gateway . '/submit.php?' . http_build_query($params);

        $payEntity = new PayEntity();
        $payEntity->setType(\App\Pay\Pay::TYPE_REDIRECT);
        $payEntity->setUrl($url);

        $this->log("发起支付请求: {$url}");
        return $payEntity;
    }

    public static function generateSign(array $params, string $key): string
    {
        ksort($params);
        $signStr = '';
        foreach ($params as $k => $v) {
            if ($k === 'sign' || $k === 'sign_type' || $v === '') {
                continue;
            }
            $signStr .= $k . '=' . $v . '&';
        }
        $signStr = rtrim($signStr, '&') . $key;
        return md5($signStr);
    }
}
