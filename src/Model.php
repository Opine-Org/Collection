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

class Model {
	public $cache = false;
    private $cacheFile;
    private $collectionService;
    private $root;

    public function __construct ($root, $collectionService) {
        $this->collectionService = $collectionService;
        $this->cacheFile = $root . '/../cache/collections.json';
        $this->root = $root;
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
        $this->cache = $cache;
    }

	public function build ($url='') {
        $collections = [];
        $dirFiles = glob($this->root . '/../collections/*.php');
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
            $filename = $this->root . '/layouts/collections/' . $collection['p'] . '.html';
            if (!file_exists($filename) && is_writable($filename)) {
                file_put_contents($filename, $this->stubRead('layout-collection.html', $collection, $url));
            }
            $filename = $this->root . '/partials/collections/' . $collection['p'] . '.hbs';
            if (!file_exists($filename) && is_writable($filename)) {
                file_put_contents($filename, $this->stubRead('partial-collection.hbs', $collection, $url));
            }
            $filename = $this->root . '/layouts/documents/' . $collection['s'] . '.html';
            if (!file_exists($filename) && is_writable($filename)) {
                file_put_contents($filename, $this->stubRead('layout-document.html', $collection, $url));
            }
            $filename = $this->root . '/partials/documents/' . $collection['s'] . '.hbs';
            if (!file_exists($filename) && is_writable($filename)) {
                file_put_contents($filename, $this->stubRead('partial-document.hbs', $collection, $url));
            }
            $filename = $this->root . '/../app/collections/' . $collection['p'] . '.yml';
            if (!file_exists($filename) && is_writable($filename)) {
                file_put_contents($filename, $this->stubRead('app-collection.yml', $collection, $url));
            }
            $filename = $this->root . '/../app/documents/' . $collection['s'] . '.yml';
            if (!file_exists($filename) && is_writable($filename)) {
                file_put_contents($filename, $this->stubRead('app-document.yml', $collection, $url));
            }
        }
        return json_encode($collections);
    }

    private function stubRead ($name, $collection, $url='') {
        $data = file_get_contents($this->root . '/../vendor/opine/build/static/' . $name);
        return str_replace(['{{$url}}', '{{$plural}}', '{{$singular}}'], [$url, $collection['p'], $collection['s']], $data);
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
}