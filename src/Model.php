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

class Model {
	public $cache = false;
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

    private function collection ($slug) {
        $collections = $this->collections();
        if (!isset($collections[$slug])) {
            return false;
        }
        return $collections[$slug];
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
        $dirFiles = glob($path);
        foreach ($dirFiles as $collectionFile) {
            $collections[] = array_merge($this->yaml($collectionFile), ['bundle' => $bundle]);
        }
    }

	public function build () {
        $collections = [];
        $this->directoryScan($this->root . '/../config/collections/*.yml', $collections);
        $bundles = $this->bundleModel->bundles();
        foreach ($bundles as $bundle) {
            if (!isset($bundle['root'])) {
                continue;
            }
            $this->directoryScan($bundle['root'] . '/../config/collections/*.yml', $collections, $bundle['name']);
        }
        $this->cacheWrite($collections);
        foreach ($collections as $collection) {
            $filename = $this->root . '/layouts/collections/' . $collection['plural'] . '.html';
            if (!file_exists($filename) && is_writable($filename)) {
                file_put_contents($filename, $this->stubRead('layout-collection.html', $collection));
            }
            $filename = $this->root . '/partials/collections/' . $collection['plural'] . '.hbs';
            if (!file_exists($filename) && is_writable($filename)) {
                file_put_contents($filename, $this->stubRead('partial-collection.hbs', $collection));
            }
            $filename = $this->root . '/layouts/documents/' . $collection['singular'] . '.html';
            if (!file_exists($filename) && is_writable($filename)) {
                file_put_contents($filename, $this->stubRead('layout-document.html', $collection));
            }
            $filename = $this->root . '/partials/documents/' . $collection['singular'] . '.hbs';
            if (!file_exists($filename) && is_writable($filename)) {
                file_put_contents($filename, $this->stubRead('partial-document.hbs', $collection));
            }
            $filename = $this->root . '/../config/layouts/collections/' . $collection['plural'] . '.yml';
            if (!file_exists($filename) && is_writable($filename)) {
                file_put_contents($filename, $this->stubRead('app-collection.yml', $collection));
            }
            $filename = $this->root . '/../config/layouts/documents/' . $collection['singular'] . '.yml';
            if (!file_exists($filename) && is_writable($filename)) {
                file_put_contents($filename, $this->stubRead('app-document.yml', $collection));
            }
        }
        return json_encode($collections);
    }

    private function stubRead ($name, $collection) {
        $data = file_get_contents($this->root . '/../vendor/opine/build/static/' . $name);
        return str_replace(['{{$url}}', '{{$plural}}', '{{$singular}}'], ['', $collection['plural'], $collection['singular']], $data);
    }

    public function statsAll () {
        $collections = $this->collections();
        foreach ($collections as $collection) {
            $this->db->collection('collection_stats')->update(
                ['collection' => $collection['plural']],
                ['$set' => [
                    'collection' => $collection['plural'],
                    'count' => $this->db->collection($collection['plural'])->count()
                ]],
                ['upsert' => true]
            );
        }
    }

    private function yaml ($file) {
        if (function_exists('yaml_parse_file')) {
            return yaml_parse_file($file);
        }
        return Yaml::parse(file_get_contents($file));
    }
}