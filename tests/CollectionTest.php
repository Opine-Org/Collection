<?php
namespace Opine;

/**
 * @backupGlobals disabled
 */
class CollectionTest extends \PHPUnit_Framework_TestCase {
    private $db;
    private $collection;
    private $collectionRoutes;
    private $collectionModel;

    public function setup () {
        date_default_timezone_set('UTC');
        $root = __DIR__ . '/../public';
        $container = new Container($root, $root . '/../container.yml');
        $this->route = $container->route;
        $this->route->testMode();
        $this->db = $container->db;
        $this->collection = $container->collection;
        $this->collectionRoute = $container->collectionRoute;
        $this->collectionModel = $container->collectionModel;
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