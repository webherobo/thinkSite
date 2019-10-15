<?php
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
require_once 'vendor/autoload.php';
$config = require_once 'config/database.php';
$isDevMoe = true;
$configuration =  Setup::createAnnotationMetadataConfiguration(array(__DIR__. '/app/entity'), $isDevMoe);
$conn = array(
    'driver'   => 'pdo_mysql',
    'user'     => $config['username'] ? $config['username'] : 'root',
    'password' => $config['password'] ? $config['password'] : '',
    'dbname'   => $config['database'] ? $config['database'] : 'symfony',
    'port' => $config['hostport'] ? $config['hostport'] : 3306,
    'charset' => 'utf8'
);
$entityManager = EntityManager::create($conn, $configuration);
return \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($entityManager);