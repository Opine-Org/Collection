<?php
trait Collection {
	public $collection = false;
	public $criteria = [];
	public $sort = [];
	public $limit = 100;
	public $skip = 0;
	public $total = 0;
	public $name = null;
	public $transform = 'document';
	public $myTransform = 'myDocument';
	private $local = false;

	public function __construct ($limit=20, $page=1, $sort=[]) {
		$this->collection = get_class($this);
		$this->limit = $limit;
		$this->skip = ($page - 1) * $limit;
		if (is_string($sort)) {
			$this->sort = json_decode($sort);
		} else {
			$this->sort = $sort;
		}
	}

	public function totalGet () {
		return $this->total;
	}

	public function localSet() {
		$this->local = true;
	}

	private function decorate (&$document) {
		$document['_id'] = (string)$document['_id'];
		if (method_exists($this, $this->transform)) {
			$method = $this->transform;
			$this->$method($document);
		}
		if (method_exists($this, $this->myTransform)) {
			$method = $this->myTransform;
			$this->$method($document);
		}
		$template = '';
		if (isset($document['template_separation'])) {
			$template = '-' . $document['template_separation'];
		}
		if ($this->local) {
			$path = $this::$singular . $template . '.html#{"Sep":"' . $this->collection . '", "a": {"id":"' . (string)$document['_id'] . '"}}';
		} else {
			if (empty($this->path)) {
				$path = '/' . $this::$singular . $template;
				if (isset($document['code_name'])) {
					$path .= '/' . $document['code_name'] . '.html';
				} else {
					$path .= '/id/' . (string)$document['_id'] . '.html';
				}
			} else {
				$path =	$this->path . $document[$this->pathKey] . '.html';
			}
		}
		$document['separation_path'] = $path;
	}

	private function fetchAll ($collection, $cursor) {
		$rows = [];
		while ($cursor->hasNext()) {
			$document = $cursor->getNext();
			$this->decorate($document);
			$rows[] = $document;
		}
		return $rows;
	}

	public function all () {
		$this->name = $this->collection;
		if ($this->publishable) {
			$this->criteria['status'] = 'published';
		}
		$this->total = DB::collection($this->collection)->find($this->criteria)->count();
		return $this->fetchAll($this->collection, DB::collection($this->collection)->find($this->criteria)->sort($this->sort)->limit($this->limit)->skip($this->skip));
	}

	public function byId ($id) {
		$this->name = $this::$singular;
		return DB::collection($this->collection)->findOne(['_id' => DB::id($id)]);
	}

	public function bySlug ($slug) {
		$this->name = $this::$singular;
		return DB::collection($this->collection)->findOne(['code_name' => $slug]); 
	}

	public function featured () {
		$this->criteria['featured'] = 't';
		return $this->all();
	}

	public function byCategoryId ($categoryId) {
		$this->criteria['category'] = DB::id($categoryId);
		return $this->all();
	}

	public function byCategorySlug () {
		//lookup category id
	}

	public function byTag ($tag) {
		$this->criteria['tag'] = $tag;
		return $this->all();
	}

	public function byCategoryIdFeatured ($categoryId) {
		$this->criteria['category'] = DB::id($categoryId);
		$this->criteria['featured'] = 't';
		return $this->all();
	}

	public function byTagFeatured ($tag) {
		$this->criteria['tag'] = $tag;
		$this->criteria['featured'] = 't';
		return $this->all();
	}

	private function dateFieldValidate() {
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
		$this->criteria['author'] = DB::id($id);
	}

	public function byAuthorSlug ($slug) {
		$this->criteria['author'] = DB::id($id);
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

//Todo: wrap up additional functions
	public function tagsRandom () {

	}

	public function popular () {

	}

	public function search () {
		//solr integration
	}
}