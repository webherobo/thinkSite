<?php

namespace app\controller;

use think\facade\Env;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

class Entity
{
    public static function getEntityManager()
    {
        $isDevMoe = true;
        $config = Setup::createAnnotationMetadataConfiguration(array(app_path() . 'entity'), $isDevMoe);
        $conn = array(
            'host' => Env::get('database.HOSTNAME', 'localhost'),
            'driver' => Env::get('database.driver', 'pdo_mysql'),
            'user' => Env::get('database.username', 'root'),
            'password' => Env::get('database.password', ''),
            'dbname' => Env::get('database.database', ''),
            'port' => Env::get('database.hostport', '3306'),
            'charset' => Env::get('database.charset', 'utf8')
        );
        $result=EntityManager::create($conn, $config);
        //return  $result;
        var_dump(app_path() . 'entity');
        //var_dump($result);exit;
    }
}