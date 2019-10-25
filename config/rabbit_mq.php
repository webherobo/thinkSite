<?php
/**
 * rabbitMQ配置文件
 */
return [
    'host' => "localhost",
    'port' => 5672,
    'user' => 'webherobo',
    'pwd' => 'admin123',
    'vhost' => 'vhost_rabbitmq',//虚拟主机(项目队列隔离使用)
    'rabbit_mq_queue' => [
        'test' => [
            'exchange_name' => 'ex_test', // 交换机名称
            'queue_name' => 'que_test', // 队列名称
            'process_num' => 3, // 默认单台机器的进程数量
            'deal_num' => '50', // 单次处理数量
            'consumer' => 'RabbitMqConsumer' // 消费地址
        ]
    ]
];
