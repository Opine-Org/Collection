<?php
date_default_timezone_set('UTC');
require_once __DIR__ . '/../vendor/autoload.php';

$root = __DIR__ . '/../public';
$config = new \Opine\Config\Service($root);
$config->cacheSet();
$container = new \Opine\Container($root, $config, $root . '/../config/container.yml');
$collectionRoute = $container->get('collectionRoute');
$collectionRoute->paths();

$files = glob(__DIR__ . '/../available/*.php');
foreach ($files as $file) {
	require_once $file;
}
/*
$files = glob(__DIR__ . '/../bundles/Test/collections/*.php');
foreach ($files as $file) {
	require_once $file;
}
*/