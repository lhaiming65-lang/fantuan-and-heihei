<?php
declare(strict_types=1);

namespace App\Pay\Epay\Impl;

class Signature implements \App\Pay\Signature
{
    public function verification(array $data, array $config): bool
    {
        $key = $config['key'] ?? '';

        if (empty($data['sign'])) {
            return false;
        }

        $receivedSign = $data['sign'];
        $params = $data;
        unset($params['sign'], $params['sign_type']);

        $expectedSign = Pay::generateSign($params, $key);

        return hash_equals($expectedSign, $receivedSign);
    }
}
