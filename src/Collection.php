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
namespace Opine;

class Collection {
    public $root;
    public $collection;
    public $criteria = [];
    public $tagCacheCollection;
    public $sort = [];
    public $limit = 100;
    public $skip = 0;
    public $total = 0;
    public $name = null;
    public $transform = 'document';
    public $myTransform = 'myDocument';
    public $local = false;
    public $db;
    public $queue;
    public $publishable = false;
    public $instance;

    public function __construct ($root, $db, $queue) {
        $this->root = $root;
        $this->db = $db;
        $this->queue = $queue;
    }

    public function factory ($collection, $limit=20, $page=1, $sort=[], $bundle='', $path='', $namespace='Collection\\') {
        $collectionInstance = new Collection($this->root, $this->db, $this->queue);
        if ($bundle == '') {
            $collectionClassFile = $this->root . '/../collections/' . $collection . '.php';
        } else {
            $collectionClassFiles = $this->root . '/../bundles/' . $bundle . '/collections/' . $collection . '.php';
        }
        $collectionClass = $namespace . $collection;
        if (!file_exists($collectionClassFile)) {
            return false;
        }
        require_once($collectionClassFile);
        if (!class_exists($collectionClass)) {
            exit ($collectionClass . ': unknown class.');
        }
        $collectionInstance->instance = new $collectionClass();
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
        $collectionInstance->collection = $collection;
        $collectionInstance->tagCacheCollection = $collectionInstance->collection . 'Tags';
        $collectionInstance->limit = $limit;
        $collectionInstance->skip = ($page - 1) * $limit;
        if (is_string($sort)) {
            $collectionInstance->sort = (array)json_decode($sort, true);
        } else {
            $collectionInstance->sort = $sort;
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
        if ($this->local) {
            $key = '_id';
            if (!empty($this->pathKey)) {
                $key = $this->pathKey;
            }
            $path = $this->singular . $template . '.html#{"Sep":"' . $this->collection . '", "a": {"id":"' . (string)$document[$key] . '"}}';
        } else {
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
        return $this->fetch();
    }

    public function fetch () {
        $this->total = $this->db->collection($this->collection)->find($this->criteria)->count();

        if ($this->collection == 'blogs') {
//            var_dump($this->criteria);
//            exit;
        }

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
            $this->name = $parts[0];
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
        $this->criteria[$this->dateField] = ['$gte' => new \MongoDate(strtorime('today'))];
        $this->all();
    }

    public function byDatePast () {
        $this->dateFieldValidate();
        $this->criteria[$this->dateField] = ['$lt' => new \MongoDate(strtorime('today'))];
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

    public function index ($search, $id, $document) {
        if (!method_exists($this->instance, 'index')) {
            return false;
        }
        $index = $this->instance->index($document);
        if ($index === false) {
            return false;
        }
        $search->indexToDefault (
            (string)$id, 
            $this->collection, 
            (isset($index['title']) ? $index['title'] : null), 
            (isset($index['description']) ? $index['description'] : null), 
            (isset($index['image']) ? $index['image'] : null), 
            (isset($index['tags']) ? $index['tags'] : null), 
            (isset($index['categories']) ? $index['categories'] : null), 
            (isset($index['date']) ? $index['date'] : null), 
            date('c', $document['created_date']->sec),
            date('c', $document['modified_date']->sec),
            $document['status'], 
            $document['featured'], 
            $document['acl'],
            '/Manager/edit/' . $this->collection . '/' . $document['dbURI'],
            (isset($document['code_name']) ? ('/' . $this->instance->singular . '/' . $document['code_name']) : null)
        );
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
        $this->db->collection('collection_stats')->update(
            ['collection' => $collection],
            ['$set' => [
                'collection' => $collection,
                'count' => $this->db->collection($collection)->count(),
                'modified_date' => new \MongoDate(strtotime('now'))
            ]],
            ['upsert' => true]
        );
    }

    public function statsAll () {
        $collections = (array)json_decode(file_get_contents($this->root . '/../collections/cache.json'), true);
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
}