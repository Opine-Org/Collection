<?php
require_once __DIR__ . '/../vendor/autoload.php';
$files = glob(__DIR__ . '/../available/*.php');
foreach ($files as $file) {
	require_once $file;
}
$files = glob(__DIR__ . '/../bundles/Test/collections/*.php');
foreach ($files as $file) {
	require_once $file;
}