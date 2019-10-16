<?php

namespace app\controller;

use think\facade\Env;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
// bootstrap.php
class Entity
{
    public static function getEntityManager()
    {

        // Create a simple "default" Doctrine ORM configuration for Annotations
        $isDevMode = true;
        $proxyDir = null;
        $cache = null;
        $useSimpleAnnotationReader = false;
        $config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/src"), $isDevMode, $proxyDir, $cache, $useSimpleAnnotationReader);
        // or if you prefer yaml or XML
        //$config = Setup::createXMLMetadataConfiguration(array(__DIR__."/config/xml"), $isDevMode);
        //$config = Setup::createYAMLMetadataConfiguration(array(__DIR__."/config/yaml"), $isDevMode);

        // database configuration parameters
        $conn = array(
            'driver' => 'pdo_sqlite',
            'path' => __DIR__ . '/db.sqlite',
        );

        // obtaining the entity manager
        $entityManager = EntityManager::create($conn, $config);

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