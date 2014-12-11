<?php
namespace Opine\Collection\Collection;

use Exception;

class Collection {
    private $db;
    private $skip;

    public function __construct ($collection, $limit, $page, $sort, $method, $value) {
        $this->skip = ($page - 1) * $limit;
    }

    public function collection () {
        return $this->collection;
    }

    public function totalGet () {
        return $this->total;
    }

    private function decorate (&$document) {
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
            throw new Exception('Model configuration mmissing dateField');
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
            throw new Exception('Model configuration missing tagCacheCollection field');
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

        //lookup authors

        //lookup categories
    }

    public function documentTags (&$document) {
        $document['tag'] = $document['_id'];
        $document['count'] = $document['value'];
        unset($document['_id']);
        unset($document['value']);
    }
}