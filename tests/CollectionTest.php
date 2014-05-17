<?php
namespace Opine;

class CollectionTest extends \PHPUnit_Framework_TestCase {
    private $db;
    private $collection;
    private $collectionRoutes;

    public function setup () {
        date_default_timezone_set('UTC');
        $root = getcwd();
        $container = new Container($root, $root . '/container.yml');
        $this->db = $container->db;
        $this->collection = $container->collection;
        $this->collectionRoute = $container->collectionRoute;
    }

    public function testSample () {
        $this->assertTrue(true);
    }
}