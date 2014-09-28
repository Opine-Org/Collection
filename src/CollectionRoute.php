<?php
/**
 * Opine\CollectionRoute
 *
 * Copyright (c)2013, 2014 Ryan Mahoney, https://github.com/Opine-Org <ryan@virtuecenter.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Opine;
use Exception;

class CollectionRoute {
    public $cache = false;
    private $separation;
    private $route;
    private $db;
    private $root;
    private $cacheFile;

    public function __construct ($root, $collection, $route, $db, $separation) {
        $this->root = $root;
        $this->route = $route;
        $this->db = $db;
        $this->separation = $separation;
        $this->collection = $collection;
        $this->cacheFile = $this->root . '/../cache/collections.json';
    }

    private function cacheWrite ($collections) {
        file_put_contents($this->cacheFile, json_encode($collections, JSON_PRETTY_PRINT));
    }

    public function cacheRead () {
        if (!file_exists($this->cacheFile)) {
            return [];
        }
        return (array)json_decode(file_get_contents($this->cacheFile), true);
    }

    public function cacheSet ($cache) {
        $this->cache = $cache;
    }

    public function generate ($collectionClass, $method='all', $limit=20, $page=1, $sort=[], $fields=[]) {
        if (in_array($method, ['byId', 'bySlug'])) {
            $value = $limit;
        } else {
            $value = false;
            if (substr_count($method, '-') > 0) {
                list($method, $value) = explode('-', urldecode($method), 2);
            }
        }
        if ($page == 0) {
            $page = 1;
        }
        $collectionObj = $this->collection->factory($collectionClass, $limit, $page, $sort);
        if (!method_exists($collectionObj, $method)) {
            exit ($method . ': unknown method.');
        }
        $head = '';
        $tail = '';
        if (isset($_GET['callback'])) {
            if ($_GET['callback'] == '?') {
                $_GET['callback'] = 'callback';
            }
            $head = $_GET['callback'] . '(';
            $tail = ');';
        }
        $options = null;
        $data = $collectionObj->$method($value);
        $name = $collectionObj->collection();
        if ($method == 'byEmbeddedField') {
            $name = $collectionObj->name;
        }
        if (isset($_GET['pretty'])) {
            $options = JSON_PRETTY_PRINT;
            $head = '<html><head></head><body style="margin:0; border:0; padding: 0"><textarea wrap="off" style="overflow: auto; margin:0; border:0; padding: 0; width:100%; height: 100%">';
            $tail = '</textarea></body></html>';
        }
        if (in_array($method, ['byId', 'bySlug'])) {
            $name = $collectionObj->singular;
            echo $head . json_encode([
                $name => $data
            ], $options) . $tail;
        } else {
            echo $head . json_encode([
                $name => $data,
                'pagination' => [
                    'limit' => $limit,
                    'total' => $collectionObj->totalGet(),
                    'page' => $page,
                    'pageCount' => ceil($collectionObj->totalGet() / $limit)
                ],
                'metadata' => array_merge(
                    ['display' => [
                        'collection' => ucwords(str_replace('_', ' ', $collectionObj->collection)),
                        'document' => ucwords(str_replace('_', ' ', $collectionObj->singular)),
                    ],
                    'method' => $method
                ], get_object_vars($collectionObj))
            ], $options) . $tail;
        }
    }

    public function jsonData ($collection, $method='all', $limit=20, $page=1, $sort=[], $fields=[]) {
        $collectionClass = '\Collection\\' . $collection;
        if (!class_exists($collectionClass)) {
            throw new CollectionException ('Collection not found: ' . $collectionClass);
        }
        $this->generate(new $collectionClass, $method, $limit, $page, $sort, $fields);
    }

    public function jsonBundleData ($bundle, $collection, $method='all', $limit=20, $page=1, $sort=[], $fields=[]) {
        $collectionClass = '\\' . $bundle . '\Collection\\' . $collection;
        if (!class_exists($collectionClass)) {
            throw new CollectionException ('Bundled Collection not found: ' . $collectionClass);
        }
        $this->generate(new $collectionClass, $method, $limit, $page, $sort, $fields, $bundle, $namespace);
    }

    public function frontendDatum ($method='all', $limit=10, $page=1, $sort=[]) {
        $name = explode('/', trim($_SERVER['REQUEST_URI'], '/'))[0];
        if ($limit === null) {
            $limit = 10;
        }
        $args = [];
        if ($limit != null) {
            $args['limit'] = $limit;
        }
        $args['method'] = $method;
        $args['page'] = $page;
        $args['sort'] = json_encode($sort);
        foreach (['limit', 'page', 'sort'] as $option) {
            $key = $name . '-' . $method . '-' . $option;
            if (isset($_GET[$key])) {
                $args[$option] = $_GET[$key];
            }
        }
        $this->separation->
            app('app/collections/' . $name)->
            layout('collections/' . $name)->
            args($name, $args)->
            template()->
            write();
    }

    public function frontendData ($slug) {
        $name = explode('/', trim($_SERVER['REQUEST_URI'], '/'))[0];
        $this->separation->
            app('app/documents/' . $name)->
            layout('documents/' . $name)->
            args($name, ['slug' => basename($slug, '.html')])->
            template()->
            write();
    }

    public function frontendList () {
        $collections = $this->collections();
        echo '<html><body>';
        foreach ($collections as $collection) {
            echo '<a href="/json-data/' . $collection['p'] . '/all?pretty">', $collection['p'], '</a><br />';
        }
        echo '</body></html>';
    }

    public function paths () {
        $this->route->get('/json-data/{collection}', 'collectionRoute@jsonData');
        $this->route->get('/json-data/{collection}/{method}', 'collectionRoute@jsonData');
        $this->route->get('/json-data/{collection}/{method}/{limit}', 'collectionRoute@jsonData');
        $this->route->get('/json-data/{collection}/{method}/{limit}/{page}', 'collectionRoute@jsonData');
        $this->route->get('/json-data/{collection}/{method}/{limit}/{page}/{sort}', 'collectionRoute@jsonData');
        $this->route->get('/json-data/{collection}/{method}/{limit}/{page}/{sort}/{fields}', 'collectionRoute@jsonData');

        $this->route->get('/{bundle}/json-data/{collection}', 'collectionRoute@jsonBundleData');
        $this->route->get('/{bundle}/json-data/{collection}/{method}', 'collectionRoute@jsonBundleData');
        $this->route->get('/{bundle}/json-data/{collection}/{method}/{limit}', 'collectionRoute@jsonBundleData');
        $this->route->get('/{bundle}/json-data/{collection}/{method}/{limit}/{page}', 'collectionRoute@jsonBundleData');
        $this->route->get('/{bundle}/json-data/{collection}/{method}/{limit}/{page}/{sort}', 'collectionRoute@jsonBundleData');
        $this->route->get('/{bundle}/json-data/{collection}/{method}/{limit}/{page}/{sort}/{fields}', 'collectionRoute@jsonBundleData');
   
        $collections = $this->collections();
        $routed = [];
        foreach ($collections as $collection) {
            if (isset($collection['p']) && !isset($routed[$collection['p']])) {
                $this->route->get('/' . $collection['p'], 'collectionRoute@frontendDatum');
                $this->route->get('/' . $collection['p'] . '/{method}', 'collectionRoute@frontendDatum');
                $this->route->get('/' . $collection['p'] . '/{method}/{limit}', 'collectionRoute@frontendDatum');
                $this->route->get('/' . $collection['p'] . '/{method}/{limit}/{page}', 'collectionRoute@frontendDatum');
                $this->route->get('/' . $collection['p'] . '/{method}/{limit}/{page}/{sort}', 'collectionRoute@frontendDatum');
                $routed[$collection['p']] =  true;
            }
            if (!isset($collection['s']) || isset($routed[$collection['s']])) {
                continue;
            }
            $this->route->get('/' . $collection['s'] . '/{slug}', 'collectionRoute@frontendData');
            $this->route->get('/' . $collection['s'] . '/id/{id}', 'collectionRoute@frontendData');
            $routed[$collection['s']] = true;
        }
        $this->route->get('/collections', 'collectionRoute@FrontendList');
    }

    public function build ($root, $url) {
        $collections = [];
        $dirFiles = glob($root . '/../collections/*.php');
        foreach ($dirFiles as $collection) {
            $collection = basename($collection, '.php');
            $className = 'Collection\\' . $collection;
            $instance = new $className();
            $collections[] = [
                'p' => $collection,
                's' => $instance->singular
            ];
        }
        $this->cacheWrite($collections);
        foreach ($collections as $collection) {
            $filename = $root . '/layouts/collections/' . $collection['p'] . '.html';
            if (!file_exists($filename)) {
                file_put_contents($filename, self::stubRead('layout-collection.html', $collection, $url, $root));
            }
            $filename = $root . '/partials/collections/' . $collection['p'] . '.hbs';
            if (!file_exists($filename)) {
                file_put_contents($filename, self::stubRead('partial-collection.hbs', $collection, $url, $root));
            }
            $filename = $root . '/layouts/documents/' . $collection['s'] . '.html';
            if (!file_exists($filename)) {
                file_put_contents($filename, self::stubRead('layout-document.html', $collection, $url, $root));
            }
            $filename = $root . '/partials/documents/' . $collection['s'] . '.hbs';
            if (!file_exists($filename)) {
                file_put_contents($filename, self::stubRead('partial-document.hbs', $collection, $url, $root));
            }
            $filename = $root . '/../app/collections/' . $collection['p'] . '.yml';
            if (!file_exists($filename)) {
                file_put_contents($filename, self::stubRead('app-collection.yml', $collection, $url, $root));
            }
            $filename = $root . '/../app/documents/' . $collection['s'] . '.yml';
            if (!file_exists($filename)) {
                file_put_contents($filename, self::stubRead('app-document.yml', $collection, $url, $root));
            }
        }
        return json_encode($collections);
    }

    private static function stubRead ($name, &$collection, $url, $root) {
        $data = file_get_contents($root . '/../vendor/opine/build/static/' . $name);
        return str_replace(['{{$url}}', '{{$plural}}', '{{$singular}}'], [$url, $collection['p'], $collection['s']], $data);
    }

    public function upgrade ($root) {
        $manifest = (array)json_decode(file_get_contents('https://raw.github.com/Opine-Org/Collection/master/available/manifest.json'), true);
        $upgraded = 0;
        foreach (glob($root . '/../collections/*.php') as $filename) {
            $lines = file($filename);
            $version = false;
            $mode = false;
            $link = false;
            foreach ($lines as $line) {
                if (substr_count($line, ' * @') != 1) {
                    continue;
                }
                if (substr_count($line, '* @mode') == 1) {
                    $mode = trim(str_replace('* @mode', '', $line));
                    continue;
                }
                if (substr_count($line, '* @version') == 1) {
                    $version = floatval(trim(str_replace('* @version', '', $line)));
                    continue;
                }
                if (substr_count($line, '* @link') == 1) {
                    $link = trim(str_replace('* @link', '', $line));
                    continue;
                }
            }
            if ($mode === false || $version === false || $link === false) {
                continue;
            }
            if ($version == '' || $link == '' || $mode == '') {
                continue;
            }
            if ($mode != 'upgrade') {
                continue;
            }
            if ($version == $manifest['collections'][basename($filename, '.php')]) {
                continue;
            }
            $newVersion = floatval($manifest['collections'][basename($filename, '.php')]);
            if ($newVersion > $version) {
                file_put_contents($filename, file_get_contents($link));
                echo 'Upgraded Collection: ', basename($filename, '.php'), ' to version: ', $newVersion, "\n";
                $upgraded++;
            }
        }
        echo 'Upgraded ', $upgraded, ' collections.', "\n";
    }

    public function collections () {
        if (!empty($this->cache)) {
            $collections = $this->cache;
        } else {
            $collections = $this->cacheRead();
        }
        if (!is_array($collections)) {
            return [];
        }
        return $collections;
    }

    public function jsonList () {
        $collections = $this->collections();
        foreach ($collections as &$collection) {
            $collectionObj = $this->collection->factory($collection['p']);
            $reflection = new \ReflectionClass($collectionObj);
            $methods = $reflection->getMethods();
            foreach ($methods as $method) {
                if (in_array($method->name, ['document','__construct','totalGet','localSet','decorate','fetchAll'])) {
                    continue;
                }
                $collection['methods'][] = $method->name;
            }
        }
        $head = '';
        $tail = '';
        if (isset($_GET['callback'])) {
            if ($_GET['callback'] == '?') {
                $_GET['callback'] = 'callback';
            }
            $head = $_GET['callback'] . '(';
            $tail = ');';
        }
        echo $head . json_encode($collections) . $tail;
    }

    public function showAllRoute () {
        $this->route->get('/json-collections', 'collectionRoute@jsonList');
    }
}

class CollectionException extends Exception {}