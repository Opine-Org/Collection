<?php
/**
 * Opine\Collection\Model
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
namespace Opine\Collection;
use Exception;

class Model {
	public $cache = false;
    private $cacheFile;
    private $collectionService;
    private $root;
    private $bundleModel;
    private $db;

    public function __construct ($root, $db, $collectionService, $bundleModel) {
        $this->collectionService = $collectionService;
        $this->cacheFile = $root . '/../cache/collections.json';
        $this->root = $root;
        $this->db = $db;
        $this->bundleModel = $bundleModel;
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

    private function metadataByName ($name) {
        $collections = $this->collections();
        foreach ($collections as $collection) {
            if ($collection['collection'] == $name) {
                return $collection;
            }
        }
        return false;
    }

    public function generate ($collectionObj, $method='all', $limit=20, $page=1, $sort=[], $fields=[]) {
        $value = false;
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
        return $this->collectionService->factory($collectionObj, $limit, $page, $sort, $method, $value);
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
        if (empty($cache)) {
            $this->cache = $this->cacheRead();
            return;
        }
        $this->cache = $cache;
    }

    private function directoryScan ($path, &$collections, $bundle='') {
        if ($bundle != '') {
            $bundle .= '\\';
        }
        $dirFiles = glob($path);
        foreach ($dirFiles as $collection) {
            $collection = basename($collection, '.php');
            $className = $bundle . 'Collection\\' . $collection;
            $instance = new $className();
            $collections[] = [
                'name' => $collection,
                'p' => $this->collectionService->toUnderscore($collection),
                's' => $instance->singular,
                'class' => $className,
                'bundle' => str_replace('\\', '', $bundle),
                'collection' => $this->collectionService->toUnderscore($collection)
            ];
        }
    }

	public function build () {
        $collections = [];
        $this->directoryScan($this->root . '/../collections/*.php', $collections);
        $bundles = $this->bundleModel->bundles();
        foreach ($bundles as $bundle) {
            $this->directoryScan($bundle['root'] . '/../collections/*.php', $collections, $bundle['name']);
        }
        $this->cacheWrite($collections);
        foreach ($collections as $collection) {
            $filename = $this->root . '/layouts/collections/' . $collection['p'] . '.html';
            if (!file_exists($filename) && is_writable($filename)) {
                file_put_contents($filename, $this->stubRead('layout-collection.html', $collection));
            }
            $filename = $this->root . '/partials/collections/' . $collection['p'] . '.hbs';
            if (!file_exists($filename) && is_writable($filename)) {
                file_put_contents($filename, $this->stubRead('partial-collection.hbs', $collection));
            }
            $filename = $this->root . '/layouts/documents/' . $collection['s'] . '.html';
            if (!file_exists($filename) && is_writable($filename)) {
                file_put_contents($filename, $this->stubRead('layout-document.html', $collection));
            }
            $filename = $this->root . '/partials/documents/' . $collection['s'] . '.hbs';
            if (!file_exists($filename) && is_writable($filename)) {
                file_put_contents($filename, $this->stubRead('partial-document.hbs', $collection));
            }
            $filename = $this->root . '/../app/collections/' . $collection['p'] . '.yml';
            if (!file_exists($filename) && is_writable($filename)) {
                file_put_contents($filename, $this->stubRead('app-collection.yml', $collection));
            }
            $filename = $this->root . '/../app/documents/' . $collection['s'] . '.yml';
            if (!file_exists($filename) && is_writable($filename)) {
                file_put_contents($filename, $this->stubRead('app-document.yml', $collection));
            }
        }
        return json_encode($collections);
    }

    private function stubRead ($name, $collection) {
        $data = file_get_contents($this->root . '/../vendor/opine/build/static/' . $name);
        return str_replace(['{{$url}}', '{{$plural}}', '{{$singular}}'], ['', $collection['p'], $collection['s']], $data);
    }

    public function upgrade () {
        $manifest = (array)json_decode(file_get_contents('https://raw.github.com/Opine-Org/Collection/master/available/manifest.json'), true);
        $upgraded = 0;
        foreach (glob($this->root . '/../collections/*.php') as $filename) {
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

    public function statsAll () {
        $collections = $this->collections();
        foreach ($collections as $collection) {
            $this->db->collection('collection_stats')->update(
                ['collection' => $collection['p']],
                ['$set' => [
                    'collection' => $collection['p'],
                    'count' => $this->db->collection($collection['p'])->count()
                ]],
                ['upsert' => true]
            );
        }
    }

    public function reIndex ($name) {
        $metadata = $this->metadataByName($name);
        $class = $metadata['class'];
        $service = $this->collectionService->factory(new $class());
        $this->db->each($this->db->collection($name)->find(), function ($doc) use ($service) {
            $service->index($doc['_id'], $doc);
            echo 'Indexed: ', (string)$doc['_id'], "\n";
        });
    }

    public function reIndexAll ($drop=false) {
        $collections = $this->collections();
        foreach ($collections as $collection) {
            $this->reIndex($collection['collection']);
        }
    }

    public function tagsCollection ($context) {
        $map = <<<MAP
            function() {
                if (!this.tags) {
                    return;
                }
                for (var i=0; i < this.tags.length; i++) {
                    emit(this.tags[i], 1);
                }
            }
MAP;
            
        $reduce = <<<REDUCE
            function(key, values) {
                var count = 0;
                for (var i = 0; i < values.length; i++) {
                    count += values[i];
                }
                return count;
            }
REDUCE;
        
        try {
            $result = $this->db->mapReduce($map, $reduce, [
                'mapreduce' => $context['collection'],
                'out' => $context['collection'] . '_tags'
            ]);
        } catch (Exception $e) {
            $result = false;
        }

        return $result;
    }
}