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
use Symfony\Component\Yaml\Yaml;
use MongoDate;

class Model {
	private $cache;
    private $cacheFile;
    private $root;
    private $bundleModel;
    private $db;

    public function __construct ($root, $db, $bundleModel) {
        $this->cacheFile = $root . '/../var/cache/collections.json';
        $this->root = $root;
        $this->db = $db;
        $this->bundleModel = $bundleModel;
    }

	public function collections () {
        $collections = [];
        if (!empty($this->cache)) {
            $collections = $this->cache;
        }
        if (empty($collections)) {
            $collections = $this->cacheRead();
        }
        if (!is_array($collections)) {
            return [];
        }
        return $collections;
    }

    public function collection ($slug) {
        if (!is_string($slug)) {
            throw new Exception ('Invalid collection type: ' . gettype($slug));
        }
        $collections = $this->collections();
        if (!isset($collections[$slug])) {
            return false;
        }
        return $collections[$slug];
    }

	private function cacheWrite ($collections) {
        file_put_contents($this->cacheFile, json_encode($collections, JSON_PRETTY_PRINT));
    }

    private function cacheRead () {
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
        $dirFiles = glob($path);
        foreach ($dirFiles as $collectionFile) {
            $metadata = array_merge($this->yaml($collectionFile), ['bundle' => $bundle]);
            if (!isset($metadata['name'])) {
                throw new Exception('Collection metadata must define a name value');
            }
            $collections[$metadata['name']] = $metadata;
        }
    }

	public function build () {
        $collections = [];
        $this->directoryScan($this->root . '/../config/collections/*.yml', $collections);
        $bundles = $this->bundleModel->bundles();
        foreach ($bundles as $bundle) {
            $this->readBundleCollections($bundle, $collections);
        }
        $this->cacheWrite($collections);
        foreach ($collections as $collection) {
            $this->writeIndexLayout($collection);
            $this->writeIndexPartial($collection);
            $this->writeItemLayout($collection);
            $this->writeItemPartial($collection);
            $this->writeIndexConfig($collection);
            $this->writeItemConfig($collection);
        }
        return json_encode($collections);
    }

    private function readBundleCollections ($bundle, &$collection) {
        if (!isset($bundle['root'])) {
            return;
        }
        $this->directoryScan($bundle['root'] . '/../config/collections/*.yml', $collections, $bundle['name']);
    }

    private function writeItemConfig ($collection) {
        $filename = $this->root . '/../config/layouts/documents/' . $collection['singular_slug'] . '.yml';
        if (file_exists($filename) || !is_writable($filename)) {
            return;
        }
        file_put_contents($filename, $this->stubRead('app-document.yml', $collection));
    }

    private function writeIndexConfig ($collection) {
        $filename = $this->root . '/../config/layouts/collections/' . $collection['plural_slug'] . '.yml';
        if (file_exists($filename) || !is_writable($filename)) {
            return;
        }
        file_put_contents($filename, $this->stubRead('app-collection.yml', $collection));
    }

    private function writeItemPartial ($collection) {
        $filename = $this->root . '/partials/documents/' . $collection['singular_slug'] . '.hbs';
        if (file_exists($filename) || !is_writable($filename)) {
            return;
        }
        file_put_contents($filename, $this->stubRead('partial-document.hbs', $collection));
    }

    private function writeItemLayout ($collection) {
        $filename = $this->root . '/layouts/documents/' . $collection['singular_slug'] . '.html';
        if (file_exists($filename) || !is_writable($filename)) {
            return;
        }
        file_put_contents($filename, $this->stubRead('layout-document.html', $collection));
    }

    private function writeIndexPartial ($collection) {
        $filename = $this->root . '/partials/collections/' . $collection['plural_slug'] . '.hbs';
        if (file_exists($filename) || !is_writable($filename)) {
            return;
        }
        file_put_contents($filename, $this->stubRead('partial-collection.hbs', $collection));
    }

    private function writeIndexLayout ($collection) {
        $filename = $this->root . '/layouts/collections/' . $collection['plural_slug'] . '.html';
        if (file_exists($filename) || !is_writable($filename)) {
            return;
        }
        file_put_contents($filename, $this->stubRead('layout-collection.html', $collection));
    }

    private function stubRead ($name, $collection) {
        $data = file_get_contents($this->root . '/../vendor/opine/build/static/' . $name);
        return str_replace(['{{$url}}', '{{$plural}}', '{{$singular}}'], ['', $collection['plural_slug'], $collection['singular_slug']], $data);
    }

    private function statsDbUpdate ($name) {
        $this->db->collection('collection_stats')->update(
            ['collection' => $name],
            ['$set' => [
                'collection' => $name,
                'count' => $this->db->collection($name)->count(),
                'modified_date' => new MongoDate(strtotime('now'))
            ]],
            ['upsert' => true]
        );
    }

    public function statsAll () {
        $collections = $this->collections();
        foreach ($collections as $collection) {
            $this->statsDbUpdate($collection['name']);
        }
    }

    private function yaml ($file) {
        try {
            if (function_exists('yaml_parse_file')) {
                $metadata = yaml_parse_file($file);
            }
            $metadata = Yaml::parse(file_get_contents($file));
            if (!isset($metadata['collection'])) {
                throw new Exception('Malformed collection YAML, missing "collection": ' . $file);
            }
            return $metadata['collection'];
        } catch (Exception $e) {
            throw new Exception('YAML error: ' . $file . ': ' . $e->getMessage());
        }
    }

    public function statsUpdate ($dbURI) {
        $this->queue->add('CollectionStats', [
            'dbURI' => $dbURI,
            'root'  => $this->root
        ]);
    }

    public function statsSetByDbURI ($dbURI) {
        if (substr_count($dbURI, ':') == 0) {
            throw new Exception('Invalid dbURI format');
        }
        $collection = explode(':', $dbURI)[0];
        return $this->statsDbUpdate($collection);
    }

    public function reIndexSearch ($name) {
        $metadata = $this->metadataByName($name);
        $class = $metadata['class'];
        $service = $this->factory(new $class());
        $this->db->each($this->db->collection($name)->find(), function ($doc) use ($service) {
            $service->indexSearch($doc['_id'], $doc);
            echo 'Indexed: ', (string)$doc['_id'], "\n";
        });
    }

    public function reIndexData ($name) {
        $metadata = $this->metadataByName($name);
        $class = $metadata['class'];
        $service = $this->factory(new $class());
        $service->indexData();
    }

    public function reIndexSearchAll ($drop=false) {
        $collections = $this->collections();
        foreach ($collections as $collection) {
            $this->reIndexSearch($collection['collection']);
        }
    }

    public function reIndexDataAll ($drop=false) {
        $collections = $this->collections();
        foreach ($collections as $collection) {
            $this->reIndexData($collection['collection']);
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