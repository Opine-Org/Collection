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
    public $local = false;
    public $db;
    public $queue;
    public $publishable = false;
    public $instance;
    public $class;
    private $search;
    public $method = false;
    public $value = false;
    private $language;

    public function __construct ($root, $db, $queue, $search, $language, $person) {
        $this->root = $root;
        $this->db = $db;
        $this->queue = $queue;
        $this->search = $search;
        $this->language = $language;
        $this->person = $person;
    }

    public function factory ($collectionObj, $limit=20, $page=1, $sort=[], $method=false, $value=false) {
        $collection = explode('\\', get_class($collectionObj));
        $collection = array_pop($collection);
        $collectionInstance = new Service($this->root, $this->db, $this->queue, $this->search, $this->language, $this->person);
        $collectionInstance->instance = $collectionObj;
        $collectionInstance->class = get_class($collectionObj);
        if (isset($collectionInstance->instance->singular)) {
            $collectionInstance->singular = $collectionInstance->instance->singular;
        }
        if (isset($collectionInstance->instance->publishable)) {
            $collectionInstance->publishable = $collectionInstance->instance->publishable;
        }
        if (isset($collectionInstance->instance->path)) {
            $collectionInstance->path = $collectionInstance->instance->path;
        }
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

    public function collection () {
        return $this->collection;
    }

    public function totalGet () {
        return $this->total;
    }

    public function localSet() {
        $this->local = true;
    }

    public function decorate (&$document) {
        $document['_id'] = (string)$document['_id'];
        if (method_exists($this->instance, $this->transform)) {
            $method = $this->transform;
            $this->instance->{$method}($document);
        }
        if (method_exists($this->instance, $this->myTransform)) {
            $method = $this->myTransform;
            $this->instance->{$method}($document);
        }
        $template = '';
        if (isset($document['template_separation'])) {
            $template = '-' . $document['template_separation'];
        }
        if (property_exists($this, 'path')) {
            if ($this->path === false) {
                return;
            }
        }
        if (!property_exists($this->instance, 'path')) {
            $path = '/' . $this->singular . $template;
            if (isset($document['code_name'])) {
                $path .= '/' . $document['code_name'] . '.html';
            } else {
                $path .= '/id/' . (string)$document['_id'] . '.html';
            }
        } else {
            $path = $this->instance->path . $document[$this->pathKey] . '.html';
        }
        $document['path'] = $path;
    }

    public function fetchAll ($collection, $cursor) {
        $rows = [];
        while ($cursor->hasNext()) {
            $document = $cursor->getNext();
            $this->decorate($document);
            $rows[] = $document;
        }
        if (method_exists($this->instance, $this->transformChunk)) {
            $method = $this->transformChunk;
            $this->instance->{$method}($rows);
        }
        if (method_exists($this->instance, $this->myTransformChunk)) {
            $method = $this->myTransformChunk;
            $this->instance->{$method}($rows);
        }
        return $rows;
    }

    public function all () {
        if (method_exists($this->instance, 'all')) {
            return $this->instance->all($this);
        }
        $this->name = $this->collection;
        if ($this->publishable) {
            $this->criteria['status'] = 'published';
        }
        $language = $this->language->get();
        if ($language !== NULL) {
            $this->criteria['language'] = $language;
        }
        $groups = $this->person->groups();
        if (is_array($groups) && count($groups) > 0) {
            $groups[] = 'public';
            $this->criteria['acl'] = ['$in' => $groups];
        } else {
            $this->criteria['acl'] = 'public';
        }
        return $this->fetch();
    }

    public function fetch () {
        $this->total = $this->db->collection($this->collection)->find($this->criteria)->count();
        return $this->fetchAll($this->collection, $this->db->collection($this->collection)->find($this->criteria)->sort($this->sort)->limit($this->limit)->skip($this->skip));
    }

    public function manager () {
        if (method_exists($this->instance, 'manager')) {
            return $this->instance->manager($this);
        }
        $this->name = $this->collection;
        $this->total = $this->db->collection($this->collection)->find($this->criteria)->count();
        return $this->fetchAll($this->collection, $this->db->collection($this->collection)->find($this->criteria)->sort($this->sort)->limit($this->limit)->skip($this->skip));
    }

    public function byEmbeddedField ($dbURI) {
        $filter = [];
        if (substr_count($dbURI, ':') > 0) {
            $parts = explode(':', $dbURI);
            $collection = array_shift($parts);
            $id = array_shift($parts);
            $filter = [$parts[0]];
        }
        $document = $this->db->collection($this->collection)->findOne(['_id' => $this->db->id($id)], $filter);
        if (!isset($document['_id'])) {
            return [];
        }
        if (sizeof($parts) == 1) {
            if (!isset($document[$parts[0]])) {
                $this->total = 0;
                return [];
            }
            $this->total = count($document[$parts[0]]);
            return $document[$parts[0]];
        }
    }

    public function byId ($id) {
        $this->name = $this->singular;
        $document = $this->db->collection($this->collection)->findOne(['_id' => $this->db->id($id)]);
        if (!isset($document['_id'])) {
            return [];
        }
        $this->decorate($document);
        return $document;
    }

    public function byField ($field) {
        if (method_exists($this->instance, 'byField')) {
            return $this->instance->byField($this, $field);
        }
        list ($field, $value) = explode('-', $field, 2);
        $this->criteria[$field] = $value;
        return $this->all();
    }

    public function bySlug ($slug) {
        $this->name = $this->singular;
        $document = $this->db->collection($this->collection)->findOne(['code_name' => $slug]);
        if (!isset($document['_id'])) {
            return [];
        }
        $this->decorate($document);
        return $document;
    }

    public function featured () {
        $this->criteria['featured'] = 't';
        return $this->all();
    }

    public function byCategoryId ($categoryId) {
        $this->criteria['category'] = $this->db->id($categoryId);
        return $this->all();
    }

    public function byCategory ($category) {
        $category = $this->categoryIdFromTitle($category);
        if (!isset($category['_id'])) {
            return $this->all();
        }
        $this->criteria['categories'] = ['$in' => [$category['_id'], (string)$category['_id']]];
        return $this->all();
    }

    public function categoryIdFromTitle ($title) {
        return $this->db->collection('categories')->findOne(['title' => urldecode($title)], ['id']);
    }

    public function byCategoryFeatured ($category) {
        $category = $this->categoryIdFromTitle($category);
        if (!isset($category['_id'])) {
            return $this->all();
        }
        $this->criteria['categories'] = $category['_id'];
        $this->criteria['featured'] = 't';
        return $this->all();
    }

    public function byTag ($tag) {
        $this->criteria['tags'] = $tag;
        return $this->all();
    }

    public function byCategoryIdFeatured ($categoryId) {
        $this->criteria['categories'] = $this->db->id($categoryId);
        $this->criteria['featured'] = 't';
        return $this->all();
    }

    public function byTagFeatured ($tag) {
        $this->criteria['tags'] = $tag;
        $this->criteria['featured'] = 't';
        return $this->all();
    }

    public function dateFieldValidate() {
        if (isset($this->dateField)) {
            throw new \Exception('Model configuration mmissing dateField');
        }
    }

    public function byDateUpcoming () {
        $this->dateFieldValidate();
        $this->criteria[$this->dateField] = ['$gte' => new MongoDate(strtorime('today'))];
        $this->all();
    }

    public function byDatePast () {
        $this->dateFieldValidate();
        $this->criteria[$this->dateField] = ['$lt' => new MongoDate(strtorime('today'))];
        $this->all();
    }

    public function byAuthorId ($id) {
        $this->criteria['author'] = $this->db->id($id);
    }

    public function byAuthor ($slug) {
        $this->criteria['author'] = $this->db->id($id);
    }

    public function tags () {
        if (!isset($this->tagCacheCollection)) {
            throw new \Exception('Model configuration missing tagCacheCollection field');
        }
        $this->path = '/' . $this->collection . '/byTag/';
        $this->pathKey = 'tag';
        $this->collection = $this->tagCacheCollection;
        $this->publishable = false;
        $this->transform = 'documentTags';
        $this->myTransform = 'myDocumentTags';
        return $this->all();
    }

    public function document (&$document) {
        //format date
        if (isset($document['display_date'])) {
            $document['display_date__MdY'] = date('M d, Y', $document['display_date']->sec);
        }

        //lookup authors

        //lookup categories
    }

    public function documentTags (&$document) {
        $document['tag'] = $document['_id'];
        $document['count'] = $document['value'];
        unset($document['_id']);
        unset($document['value']);
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
        $managersCache = $this->root . '/../cache/managers.json';
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
}