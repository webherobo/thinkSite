#!/usr/bin/env php
<?php
require_once 'vendor/autoload.php';
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

$isDevMode = true;
$proxyDir = null;
$cache = null;
$useSimpleAnnotationReader = false;
$configuration = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/config/xml"), $isDevMode, $proxyDir, $cache, $useSimpleAnnotationReader);
//$isDevMoe = false;
//$configuration =  Setup::createAnnotationMetadataConfiguration(array(__DIR__. '/config/xml'), $isDevMoe);
$conn = array(
    'driver' => 'pdo_mysql',
    'user' => 'root',
    'password' => 'root',
    'dbname' => 'vmtestdb',
    'port' => '3306',
    'charset' => 'utf8'
);
$entityManager = EntityManager::create($conn, $configuration);
return \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($entityManager);