<?php

namespace app\controller;

use think\Config;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

class Entity
{
    public static function getEntityManager()
    {
        $isDevMoe = true;
        $config = Setup::createAnnotationMetadataConfiguration(array(APP_PATH . '/entity'), $isDevMoe);
        $conn = array(
            'driver' => 'pdo_mysql',
            'user' => Config::get('database.username') ? Config::get('database.username') : 'root',
            'password' => Config::get('database.password') ? Config::get('database.password') : '',
            'dbname' => Config::get('database.database') ? Config::get('database.database') : 'symfony',
            'port' => Config::get('database.hostport') ? Config::get('database.hostport') : 3306,
            'charset' => 'utf8'
        );
        return EntityManager::create($conn, $config);
    }
}