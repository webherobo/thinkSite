<?php
// bootstrap.php
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\ClassLoader, Doctrine\DBAL\Logging\EchoSQLLogger, Doctrine\Common\Cache\ArrayCache;
define(APPPATH,__DIR__);
date_default_timezone_set("Asia/Shanghai");
require_once "./vendor/autoload.php";
// database configuration parameters
if (defined(APPPATH)) {
    require_once APPPATH . 'config/database.php';
    $conn = array('driver' => 'pdo_mysql', 'user' => $db['default']['username'], 'password' => $db['default']['password'], 'host' => $db['default']['hostname'], 'dbname' => $db['default']['database']);
} else {
    $conn = array('driver' => 'pdo_mysql', 'user' => 'root', 'password' => 'root', 'host' => '192.168.154.129', 'dbname' => 'vmtestdb');
}
//Below can be exected in cli
require_once APPPATH . './vendor/Doctrine/Common/lib/doctrine/common/ClassLoader.php';
$doctrineClassLoader = new ClassLoader('Doctrine', APPPATH . 'libraries');
$doctrineClassLoader->register();
$entitiesClassLoader = new ClassLoader('models', rtrim(APPPATH, "/"));
$entitiesClassLoader->register();
$proxiesClassLoader = new ClassLoader('Proxies', APPPATH . 'models/proxies');
$proxiesClassLoader->register();
// Create a simple "default" Doctrine ORM configuration for Annotations
$isDevMode = true;
$config = Setup::createAnnotationMetadataConfiguration(array(__DIR__ . "/models/entities"), $isDevMode);
// or if you prefer yaml or XML
$config = Setup::createXMLMetadataConfiguration(array(__DIR__ . "/config/xml"), $isDevMode);
$config = Setup::createYAMLMetadataConfiguration(array(__DIR__ . "/config/yaml"), $isDevMode);
$cache = new ArrayCache;
$config->setMetadataCacheImpl($cache);
$driverImpl = $config->newDefaultAnnotationDriver(array(__DIR__ . '/models/entities'));
$config->setMetadataDriverImpl($driverImpl);
$config->setQueryCacheImpl($cache);
$config->setQueryCacheImpl($cache);
//Proxy configuration
$config->setProxyDir(__DIR__ . '/models/proxies');
$config->setProxyNamespace('Proxies');
// Set up logger
$logger = new EchoSQLLogger;
$config->setSQLLogger($logger);
$config->setAutoGenerateProxyClasses(TRUE);
// obtaining the entity manager
global $entityManager;
$entityManager = EntityManager::create($conn, $config);


//requireonce "bootstrap.php";

return DoctrineORMToolsConsoleConsoleRunnercreateHelperSet($entityManager);

//vendor/bin/doctrine orm:schema-tool:create

//vendor/bin/doctrine orm:schema-tool:update --force