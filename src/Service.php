<?php
/**
 * Opine\Collection
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
use MongoDate;
use Opine\Interfaces\DB as DBInterface;
use Opine\Collection\Collection as Collection;

class Service {
    public $root;
    public $collection;
    public $criteria = [];
    public $tagCacheCollection;
    public $sort = [];
    public $limit = 100;
    public $page = 1;
    public $skip = 0;
    public $total = 0;
    public $name = null;
    public $transform = 'document';
    public $myTransform = 'myDocument';
    public $transformChunk = 'chunk';
    public $myTransformChunk = 'myChunk';
    public $db;
    public $queue;
    public $publishable = false;
    public $instance;
    public $class;
    private $search;
    public $method = false;
    public $value = false;
    private $language;
    private $model;

    public function __construct ($root, $model, DBInterface $db, $queue, $search, $language, $person) {
        $this->root = $root;
        $this->db = $db;
        $this->queue = $queue;
        $this->search = $search;
        $this->language = $language;
        $this->person = $person;
        $this->model = $model;
    }

    public function factory ($slug, $limit=20, $page=1, $sort=[], $method=false, $value=false) {
        $collection = $this->model->colelction($slug);

        return new Collection($collection, $limit, $page, $sort, $method, $value);

        $collectionInstance->instance->db = $collectionInstance->db;
        $collectionInstance->instance->queue = $collectionInstance->queue;
        $collectionInstance->collection = $this->toUnderscore($collection);
        $collectionInstance->tagCacheCollection = $collectionInstance->collection . 'Tags';
        $collectionInstance->limit = $limit;
        $collectionInstance->skip = ($page - 1) * $limit;
        $collectionInstance->page = $page;
        if ($method !== false) {
            $collectionInstance->method = $method;
        }
        if ($value !== false) {
            $collectionInstance->value = $value;
        }
        if (is_string($sort)) {
            $collectionInstance->sort = (array)json_decode($sort, true);
        } else {
            $collectionInstance->sort = $sort;
        }
        if ($method == 'byEmbeddedField') {
            $tmp = explode(':', $value);
            $collectionInstance->name = array_pop($tmp);
        }
        return $collectionInstance;
    }

    public function indexData () {
        if (!method_exists($this->instance, 'indexData')) {
            return false;
        }
        $indexes = $this->instance->indexData();
        foreach ($indexes as $index) {
            if (!isset($index['keys'])) {
                echo 'Index can not be created for collection: ', $this->collection, ': missing keys.', "\n";
            }
            if (!isset($index['options'])) {
                $index['options'] = [];
            }
            $this->db->collection($this->collection)->createIndex($index['keys'], $index['options']);
        }
        echo $this->collection, " indexed", "\n";
    }

    public function indexSearch ($id, $document, $managerUrl=false, $publicUrl=false) {
        if (!method_exists($this->instance, 'indexSearch')) {
            return false;
        }
        $index = $this->instance->indexSearch($document);
        if ($index === false) {
            return false;
        }
        if ($managerUrl === false) {
            if (isset($document['dbURI'])) {
                $managerUrl = $this->urlManager($document['dbURI']);
            } else {
                $managerUrl = '';
            }
        }
        if ($publicUrl === false) {
            if (isset($document['code_name'])) {
                $publicUrl = $this->urlPublic($document['code_name']);
            } else {
                $publicUrl = '';
            }
        }
        return $this->search->indexToDefault (
            (string)$id,
            $this->collection,
            (isset($index['title']) ? $index['title'] : null),
            (isset($index['description']) ? $index['description'] : null),
            (isset($index['image']) ? $index['image'] : null),
            (isset($index['tags']) ? $index['tags'] : null),
            (isset($index['categories']) ? $index['categories'] : null),
            (isset($index['date']) ? date('Y/m/d H:i:s', strtotime($index['date'])) : null),
            date('Y/m/d H:i:s', $document['created_date']->sec),
            date('Y/m/d H:i:s', $document['modified_date']->sec),
            $document['status'],
            $document['featured'],
            $document['acl'],
            $managerUrl,
            $publicUrl,
            'en'
        );
    }

    private function urlManager ($dbURI) {
        $managersCache = $this->root . '/../var/cache/managers.json';
        if (!file_exists($managersCache)) {
            return '';
        }
        $managers = json_decode(file_get_contents($managersCache), true);
        $managers = $managers['managers'];
        $metadata = false;
        foreach ($managers as $manager) {
            if (!isset($manager['collection'])) {
                continue;
            }
            if ($manager['collection'] == $this->class) {
                $metadata = $manager;
                break;
            } else {
                echo $manager['collection'], ' == ', $this->class, "\n";
            }
        }
        if ($metadata === false) {
            return '';
        }
        return '/Manager/item/' . $metadata['link'] . '/' . $dbURI;
    }

    private function urlPublic ($slug) {
        return '/' . $this->singular . '/' . $slug;
    }

    public function views ($mode, $id, $document=[]) {
        $reflector = new \ReflectionClass($this->instance);
        $methods = $reflector->getMethods();
        foreach ($methods as $method) {
            if (preg_match('/View$/', (string)$method->name) == 0) {
                continue;
            }
            $method->invoke($this->instance, $mode, $id, $document);
        }
    }

    public function statsUpdate ($dbURI) {
        $this->queue->add('CollectionStats', [
            'dbURI' => $dbURI,
            'root'  => $this->root
        ]);
    }

    public function statsSet ($dbURI) {
        $collection = explode(':', $dbURI)[0];
        return $this->db->collection('collection_stats')->update(
            ['collection' => $collection],
            ['$set' => [
                'collection' => $collection,
                'count' => $this->db->collection($collection)->count(),
                'modified_date' => new MongoDate(strtotime('now'))
            ]],
            ['upsert' => true]
        );
    }

    public function toUnderscore ($value) {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $value));
    }

    public function toCamelCase ($value, $capitalise_first_char=true) {
        if ($capitalise_first_char) {
            $value[0] = strtoupper($value[0]);
        }
        return preg_replace_callback('/_([a-z])/', function ($c) { return strtoupper($c[1]); }, $value);
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
        return $this->factory($collectionObj, $limit, $page, $sort, $method, $value);
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