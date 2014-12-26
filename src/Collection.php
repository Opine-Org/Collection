<?php
namespace Opine\Collection;

use Exception;
use MongoDate;
use Opine\Interfaces\DB as DBInterface;

class Collection
{
    private $root;
    private $route;
    private $db;
    private $person;
    private $search;
    private $language;
    private $metadata = [];
    private $queryOptions = [];
    private $name;
    private $value;
    private $method;
    private $managerCache;
    private $criteria = [];

    public function __construct(Array $metadata, $root, $route, DBInterface $db, $language, $person, $search)
    {
        $this->metadata = $metadata;
        $this->root = $root;
        $this->route = $route;
        $this->db = $db;
        $this->language = $language;
        $this->person = $person;
        $this->search = $search;
    }

    public function queryOptionsSet($limit = 10, $page = 1, Array $sort = [], $method = 'all', $value = null)
    {
        $this->queryOptions['field'] = null;
        $this->queryOptions['skip'] = (integer) (($page - 1) * $limit);
        $this->queryOptions['limit'] = (integer) $limit;
        $this->method = $method;
        if ($value !== null) {
            $this->value = $value;
        }
        $this->queryOptions['sort'] = $sort;
        $this->name = $this->metadata['name'];
        if ($method == 'byEmbeddedField') {
            $tmp = explode(':', $value);
            $this->name = array_pop($tmp);
        }
        if (substr($method, 0, 7) == 'byField') {
            $tmp = explode(':', $method);
            $this->method = $tmp[0];
            $this->queryOptions['field'] = $tmp[1];
        }
    }

    public function methodGet()
    {
        return $this->method;
    }

    public function valueGet()
    {
        return $this->value;
    }

    public function limitGet()
    {
        if (!isset($this->queryOptions['limit'])) {
            return 20;
        }
        if ($this->queryOptions['limit'] == 0) {
            return 20;
        }

        return (integer) $this->queryOptions['limit'];
    }

    public function pageGet()
    {
        if (!isset($this->queryOptions['page'])) {
            return 1;
        }

        return $this->queryOptions['page'];
    }

    public function collection()
    {
        return $this->metadata['name'];
    }

    public function totalGet()
    {
        return $this->total;
    }

    public function singularGet()
    {
        return $this->metadata['singular_slug'];
    }

    private function transform(&$document)
    {
        if (!isset($this->metadata['transform'])) {
            return;
        }
        if (substr_count($this->metadata['transform'], '@') != 1) {
            throw new Exception('bad service declaration: '.$this->metadata['transform']);
        }
        $this->route->serviceMethod($this->metadata['transform'], $document);
    }

    private function chunk(&$documents)
    {
        if (!isset($this->metadata['chunk'])) {
            return;
        }
        if (substr_count($this->metadata['chunk'], '@') != 1) {
            throw new Exception('bad service declaration: '.$this->metadata['chunk']);
        }
        $this->route->serviceMethod($this->metadata['chunk'], $documents);
    }

    private function decorate(&$document)
    {
        if (isset($document['path'])) {
            return;
        }
        $document['_id'] = (string) $document['_id'];
        $slug = $document['_id'];
        if (isset($document['code_name'])) {
            $slug = $document['code_name'];
        }
        if (isset($this->metadata['path'])) {
            $document['path'] = $this->metadata['path'].'/'.$slug.(isset($metadata['path_extension']) ? '.'.$metadata['path_extension'] : '');

            return;
        }
        $document['path'] = '/'.$this->metadata['singular_slug'].'/'.$slug.(isset($metadata['path_extension']) ? '.'.$metadata['path_extension'] : '');
    }

    private function fetchAll($collection, $cursor)
    {
        $documents = [];
        while ($cursor->hasNext()) {
            $document = $cursor->getNext();
            $this->decorate($document);
            $documents[] = $document;
        }
        $this->chunk($documents);

        return $documents;
    }

    public function all()
    {
        $this->name = $this->metadata['name'];
        if ($this->metadata['publishable'] == true) {
            $this->criteria['status'] = 'published';
        }
        $language = $this->language->get();
        if ($language !== null) {
            $this->criteria['language'] = $language;
        }
        $this->criteria['acl'] = 'public';
        $groups = $this->person->groups();
        if (is_array($groups) && count($groups) > 0) {
            $this->criteria['acl'] = ['$in' => array_merge([$this->criteria['acl']], $groups)];
        }

        return $this->fetch();
    }

    private function fetch()
    {
        $this->total = $this->db->collection($this->metadata['name'])->find($this->criteria)->count();

        return $this->fetchAll(
            $this->metadata['name'],
            $this->db->collection($this->metadata['name'])->
                find($this->criteria)->
                sort($this->queryOptions['sort'])->
                limit((integer) $this->queryOptions['limit'])->
                skip((integer) $this->queryOptions['skip']));
    }

    public function manager()
    {
        $this->name = $this->metadata['name'];
        $this->total = $this->db->collection($this->metadata['name'])->find($this->criteria)->count();

        return $this->fetchAll(
            $this->metadata['name'],
            $this->db->collection($this->metadata['name'])->
                find($this->criteria)->
                sort($this->queryOptions['sort'])->
                limit((integer) $this->queryOptions['limit'])->
                skip((integer) $this->queryOptions['skip']));
    }

    public function byEmbeddedField($dbURI)
    {
        $this->total = 0;
        $filter = [];
        if (substr_count($dbURI, ':') > 0) {
            $parts = explode(':', $dbURI);
            $collection = array_shift($parts);
            $id = array_shift($parts);
            $filter = [$parts[0]];
        }
        $document = $this->db->collection($this->metadata['name'])->findOne(['_id' => $this->db->id($id)], $filter);
        if (!isset($document['_id'])) {
            return [];
        }
        if (sizeof($parts) != 1) {
            return [];
        }
        if (!isset($document[$parts[0]])) {
            return [];
        }
        $this->total = count($document[$parts[0]]);

        return $document[$parts[0]];
    }

    public function byId($id)
    {
        $this->total = 0;
        $this->name = $this->metadata['singular_slug'];
        $document = $this->db->collection($this->metadata['name'])->findOne(['_id' => $this->db->id($id)]);
        if (!isset($document['_id'])) {
            return [];
        }
        $this->total = 1;
        $this->decorate($document);

        return $document;
    }

    public function byField()
    {
        $this->criteria[$this->queryOptions['field']] = $this->value;

        return $this->all();
    }

    public function bySlug($slug)
    {
        $this->name = $this->metadata['singular_slug'];
        $document = $this->db->collection($this->metadata['name'])->findOne(['code_name' => $slug]);
        if (!isset($document['_id'])) {
            return [];
        }
        $this->decorate($document);

        return $document;
    }

    public function featured()
    {
        $this->criteria['featured'] = 't';

        return $this->all();
    }

    public function byCategoryId($categoryId)
    {
        $this->criteria['category'] = $this->db->id($categoryId);

        return $this->all();
    }

    public function byCategory($category)
    {
        $category = $this->categoryIdFromTitle($category);
        if (!isset($category['_id'])) {
            return $this->all();
        }
        $this->criteria['categories'] = ['$in' => [$category['_id'], (string) $category['_id']]];

        return $this->all();
    }

    private function categoryIdFromTitle($title)
    {
        return $this->db->collection('categories')->findOne(['title' => urldecode($title)], ['id']);
    }

    public function byCategoryFeatured($category)
    {
        $category = $this->categoryIdFromTitle($category);
        if (!isset($category['_id'])) {
            return $this->all();
        }
        $this->criteria['categories'] = $category['_id'];
        $this->criteria['featured'] = 't';

        return $this->all();
    }

    public function byTag($tag)
    {
        $this->criteria['tags'] = $tag;

        return $this->all();
    }

    public function byCategoryIdFeatured($categoryId)
    {
        $this->criteria['categories'] = $this->db->id($categoryId);
        $this->criteria['featured'] = 't';

        return $this->all();
    }

    public function byTagFeatured($tag)
    {
        $this->criteria['tags'] = $tag;
        $this->criteria['featured'] = 't';

        return $this->all();
    }

    private function dateFieldValidate()
    {
        if (isset($this->dateField)) {
            throw new Exception('Model configuration mmissing dateField');
        }
    }

    public function byDateUpcoming()
    {
        $this->dateFieldValidate();
        $this->criteria[$this->dateField] = ['$gte' => new MongoDate(strtorime('today'))];
        $this->all();
    }

    public function byDatePast()
    {
        $this->dateFieldValidate();
        $this->criteria[$this->dateField] = ['$lt' => new MongoDate(strtorime('today'))];
        $this->all();
    }

    public function byAuthorId($id)
    {
        $this->criteria['author'] = $this->db->id($id);
    }

    public function byAuthor($slug)
    {
        $this->criteria['author'] = $this->db->id($id);
    }

    public function document(&$document)
    {
        //format date

        //lookup authors

        //lookup categories
    }

    public function documentTags(&$document)
    {
        $document['tag'] = $document['_id'];
        $document['count'] = $document['value'];
        unset($document['_id']);
        unset($document['value']);
    }

    public function indexData()
    {
        if (!isset($this->metadata['indexData']) || !is_array($this->metadata['indexData'])) {
            return false;
        }
        foreach ($indexes as $index) {
            if (!isset($index['keys'])) {
                echo 'Index can not be created for collection: ', $this->metadata['name'], ': missing keys.', "\n";
            }
            if (!isset($index['options'])) {
                $index['options'] = [];
            }
            $this->db->collection($this->metadata['name'])->
                createIndex($index['keys'], $index['options']);
        }
        echo $this->metadata['name'], " indexed", "\n";
    }

    private function indexString(&$index, $field, $map, &$document)
    {
        if (!isset($document[$map])) {
            $index[$field] = null;
            return;
        }
        $index[$field] = $document[$map];
    }

    private function indexArray(&$index, $field, Array $map, &$document)
    {
        $data = null;
        if (isset($document[$map])) {
            $data = $index[$field];
        }
        if (!isset($map['field'])) {
            throw new Exception('indexing a field via service requires the field key to be set');
        }
        if (!isset($map['service'])) {
            throw new Exception('indexing a field via service requires the service key to be set');
        }
        try {
            $index[$field] = $this->route->serviceMethod($map['service'], $field, $map, $document);
        } catch (Exception $e) {
            throw new Exception('can not call indexing service for field: ', $field, ', '.$e->getMessage());
        }
    }

    public function indexSearch($id, Array $document, $managerUrl = null, $publicUrl = null)
    {
        if (!isset($this->metadata['indexSearch']) || !is_array($this->metadata['indexSearch'])) {
            return false;
        }
        $index = [];
        foreach ($this->metadata['indexSearch'] as $field => $map) {
            $type = gettype($map);
            switch ($type) {
                case 'string':
                    $this->indexString($index, $field, $map, $document);
                    break;

                case 'array':
                    $this->indexArray($index, $field, $map, $document);
                    break;

                default:
                    throw new Exception('Unknown index type: ', $type, ', for field: ', $field);
            }
        }
        if (empty($index)) {
            return false;
        }
        if (empty($managerUrl) && !empty($document['dbURI'])) {
            $managerUrl = $this->urlManager($document['dbURI']);
        }
        if (empty($publicUrl) && !empty($document['code_name'])) {
            $publicUrl = '/'.$this->metadata['singular_slug'].'/'.$document['code_name'];
        }

        return $this->search->indexToDefault(
            (string) $id,
            $this->metadata['name'],
            (isset($index['title']) ? $index['title'] : null),
            (isset($index['description']) ? $index['description'] : null),
            (isset($index['image']) ? $index['image'] : null),
            (isset($index['tags']) ? $index['tags'] : []),
            (isset($index['categories']) ? $index['categories'] : []),
            (isset($index['date']) ? date('Y/m/d H:i:s', strtotime($index['date'])) : null),
            (isset($document['created_date']) ? date('Y/m/d H:i:s', $document['created_date']->sec) : null),
            (isset($document['modified_date']) ? date('Y/m/d H:i:s', $document['modified_date']->sec) : null),
            (isset($document['status']) ? $document['status'] : null),
            (isset($document['featured']) ? $document['featured'] : null),
            (isset($document['acl']) ? $document['acl'] : ['public']),
            $managerUrl,
            $publicUrl,
            (isset($index['language']) ? $index['language'] : null)
        );
    }

    private function managerCache()
    {
        $managersCache = $this->root.'/../var/cache/managersByCollection.json';
        if (!file_exists($managersCache)) {
            return false;
        }
        $managers = json_decode(file_get_contents($managersCache), true);
        if (!isset($managers['managers']) || !isset($managers['managers'][$this->metadata['slug']])) {
            return false;
        }
        $managers['managers'][$this->metadata['slug']];
    }

    private function urlManager($dbURI)
    {
        if ($this->managerCache === null) {
            $this->managerCache = $this->managerCache();
        }
        if (!is_array($this->managerCache)) {
            return;
        }

        return '/Manager/item/'.$this->managerCache['slug'].'/'.$dbURI;
    }

    public function views () {
        //regenerate and views of collection
    }

    public function statsUpdate () {
        //regenerate the count of this collection
    }
}
