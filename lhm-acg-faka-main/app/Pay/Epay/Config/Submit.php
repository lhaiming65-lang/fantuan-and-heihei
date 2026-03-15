<?php
declare(strict_types=1);

return [
    [
        'title' => '网关地址',
        'name' => 'gateway',
        'type' => 'input',
        'placeholder' => '易支付网关地址，例如：https://pay.example.com',
        'default' => '',
        'tips' => '填写易支付平台提供的网关地址，不要带末尾斜杠',
        'required' => true,
    ],
    [
        'title' => '商户ID',
        'name' => 'pid',
        'type' => 'input',
        'placeholder' => '商户ID',
        'default' => '',
        'tips' => '易支付平台分配的商户ID(PID)',
        'required' => true,
    ],
    [
        'title' => '商户密钥',
        'name' => 'key',
        'type' => 'input',
        'placeholder' => '商户密钥',
        'default' => '',
        'tips' => '易支付平台分配的商户密钥(KEY)',
        'required' => true,
    ],
];
