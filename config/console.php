<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'hello' => 'app\command\Hello',
        'rabbitmq' => 'app\command\Mytest',
        'swooleServer' => 'app\command\swooleServer',
    ],
];
