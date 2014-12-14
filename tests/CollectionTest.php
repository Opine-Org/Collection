<?php
namespace Opine;

use PHPUnit_Framework_TestCase;
use Opine\Config\Service as Config;
use Opine\Container\Service as Container;
use MongoDate;

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

    public static function setUpBeforeClass() {
        $root = __DIR__ . '/../public';
        $config = new Config($root);
        $config->cacheSet();
        $container = Container::instance($root, $config, $root . '/../config/container.yml');

        $db = $container->get('db');
        $dbURI = 'blogs:54750ca92798718d438b45a7';
        $db->document($dbURI, [
            'author'               => '',
            'body'                 => '',
            'categories'           => ['547f377827987110048b4579'],
            'code_name'            => 'test-post',
            'comments'             => 'f',
            'date_published'       => null,
            'description'          => 'this is the summary',
            'display_date'         => new MongoDate(strtotime('2014-12-01T00:00:00Z')),
            'featured'             => 'f',
            'metadata_description' => '',
            'metadata_keywords'    => '',
            'pinned'               => 'f',
            'publication_name'     => '',
            'status'               => 'published',
            'title'                => 'Test Post 2',
            'tags'                 => ['test']
        ])->upsert();
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

    public function testQueryAll () {
        $data = json_decode($this->route->run('GET', '/api/collection/blogs'), true);
        $this->assertTrue(is_array($data['blogs']) && count($data['blogs']) > 0);
    }

    public function testQueryBySlug () {
        $data = json_decode($this->route->run('GET', '/api/collection/blogs/bySlug/test-post'), true);
        $this->assertTrue(is_array($data['blog']) && $data['blog']['_id'] == '54750ca92798718d438b45a7');
    }

    public function testQueryById () {
        $data = json_decode($this->route->run('GET', '/api/collection/blogs/byId/54750ca92798718d438b45a7'), true);
        $this->assertTrue(is_array($data['blog']) && $data['blog']['_id'] == '54750ca92798718d438b45a7');
    }

    public function testQueryByField () {
        $data = json_decode($this->route->run('GET', '/api/collection/blogs/byField:comments:f'), true);
        $this->assertTrue(is_array($data['blogs']) && count($data['blogs']) > 0);
        $data = json_decode($this->route->run('GET', '/api/collection/blogs/byField:comments:t'), true);
        $this->assertTrue(is_array($data['blogs']) && count($data['blogs']) == 0);
    }

    public function testQueryByTag () {
        $data = json_decode($this->route->run('GET', '/api/collection/blogs/byTag:test'), true);
        $this->assertTrue(is_array($data['blogs']) && count($data['blogs']) > 0);
        $data = json_decode($this->route->run('GET', '/api/collection/blogs/byTag:notset'), true);
        $this->assertTrue(is_array($data['blogs']) && count($data['blogs']) == 0);
    }
}