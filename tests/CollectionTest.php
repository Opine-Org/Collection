<?php
namespace Opine;

use PHPUnit_Framework_TestCase;
use Opine\Config\Service as Config;
use Opine\Container\Service as Container;

/**
 * @backupGlobals disabled
 */
class CollectionTest extends PHPUnit_Framework_TestCase {
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
        $this->collection = $container->get('collection');
        $this->collectionRoute = $container->get('collectionRoute');
        $this->collectionModel = $container->get('collectionModel');
    }

    public function testBuild () {
        $json = $this->collectionModel->build();
        $array = json_decode($json, true);
        $this->assertTrue(is_array($array) && isset($array['blogs']));
    }

    public function testFactory () {
        $object = $this->collection->factory('blogs');
        $this->assertTrue(get_class($object) === 'Opine\Collection\Collection');
        $this->assertTrue($object->collection() == 'blogs');
    }

    public function testNormalCollection () {
        $data = json_decode($this->route->run('GET', '/api/collection/blogs'), true);
        $this->assertTrue(is_array($data['blogs']) && count($data['blogs']) > 0);
    }

/*
    public function testBundleCollection () {
        $json = json_decode($this->route->run('GET', '/Test/api/collection/Books'), true);
        $this->assertTrue(is_array($json['books']));
    }
*/
}