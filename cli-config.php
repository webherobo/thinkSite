#!/usr/bin/env php
<?php
namespace think;
require_once 'vendor/autoload.php';
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

$isDevMoe = false;
$configuration =  Setup::createAnnotationMetadataConfiguration(array(__DIR__. '/app/entitys'), $isDevMoe);
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