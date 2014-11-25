<?php
namespace Opine;

use PHPUnit_Framework_TestCase;
use Opine\Config\Service as Config;
use Oipine\Container\Service as Container;

/**
 * @backupGlobals disabled
 */
class CollectionTest extends PHPUnit_Framework_TestCase {
    private $db;
    private $collection;
    private $collectionRoutes;
    private $collectionModel;

    public function setup () {
        $root = __DIR__ . '/../public';
        $config = new Config($root);
        $config->cacheSet();
        $container = Container::instance($root, $config, $root . '/../config/container.yml');
        $this->route = $container->get('route');
        $this->route->testMode();
        $this->db = $container->db;
        $this->collection = $container->get('collection');
        $this->collectionRoute = $container->get('collectionRoute');
        $this->collectionModel = $container->get('collectionModel');
        $this->collectionModel->build();
        $this->collectionRoute->paths();
    }

    public function testFactory () {
        $class = $this->collection->factory(new \Collection\Books());
        $this->assertTrue(get_class($class) == 'Opine\Collection\Service');
        $this->assertTrue($class->collection == 'books');
    }

    public function testNormalCollection () {
        $json = json_decode($this->route->run('GET', '/api/collection/Books'), true);
        $this->assertTrue(is_array($json['books']));
    }

    public function testBundleCollection () {
        $json = json_decode($this->route->run('GET', '/Test/api/collection/Books'), true);
        $this->assertTrue(is_array($json['books']));
    }
}