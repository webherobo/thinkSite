<?php
use think\facade\Env;

// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------

return [
    // 默认缓存驱动
    'default' => Env::get('cache.driver', 'redis'),

    // 缓存连接方式配置
    'stores'  => [
        'file' => [
            // 驱动方式
            'type'       => 'File',
            // 缓存保存目录
            'path'       => '',
            // 缓存前缀
            'prefix'     => '',
            // 缓存有效期 0表示永久缓存
            'expire'     => 0,
            // 缓存标签前缀
            'tag_prefix' => 'tag:',
            // 序列化机制 例如 ['serialize', 'unserialize']
            'serialize'  => [],
        ],
        // 更多的缓存连接
		//redis配置
        'redis'  => [
        'type'      => 'redis',
        'host'      => '127.0.0.1',
        'port'      => '6379',//你redis的端口号，可以在配置文件设置其他的
        'password'  => '', //这里是你redis配置的密码，如果没有则留空
        'timeout'   => 3600 //缓存时间
    ],
    ],
];
