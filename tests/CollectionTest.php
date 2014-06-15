<?php
namespace Opine;

class CollectionTest extends \PHPUnit_Framework_TestCase {
    private $db;
    private $collection;
    private $collectionRoutes;

    public function setup () {
        date_default_timezone_set('UTC');
        $root = __DIR__ . '/../public';
        $container = new Container($root, $root . '/../container.yml');
        $this->db = $container->db;
        $this->collection = $container->collection;
        $this->collectionRoute = $container->collectionRoute;
        $this->config = $container->config;
    }

    public function testFactory () {
        $class = $this->collection->factory(new \Collection\Books());
        $this->assertTrue(get_class($class) == 'Opine\Collection');
        $this->assertTrue($class->collection == 'books');
    }

    public function testNormalCollection () {
        ob_start();
        $this->collectionRoute->generate(new \Collection\Books(), 'all', 10, 1, [], []);
        $json = json_decode(ob_get_clean(), true);
        $this->assertTrue(is_array($json['books']));
    }

    public function testBundleCollection () {
        ob_start();
        $this->collectionRoute->generate(new \Test\Collection\Books(), 'all', 10, 1, [], []);
        $json = json_decode(ob_get_clean(), true);
        $this->assertTrue(is_array($json['books']));
    } 
}