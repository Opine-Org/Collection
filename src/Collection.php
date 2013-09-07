<?php
trait Collection {
	public static $config = false;
	public $collection = false;
	public $criteria = [];
	public $sort = [];
	public $limit = 100;
	public $skip = 0;
	public $total = 0;
	public $name = null;
	private $local = false;

	public function __construct ($limit=20, $skip=0, $sort=[]) {
		$this->collection = get_class($this);
		$this->limit = $limit;
		$this->skip = $skip;
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

	private static function db ($collection) {
		self::$config = require(__DIR__ . '/../config.php');
		$client = new MongoClient(self::$config['conn']);
		$db = new MongoDB($client, self::$config['name']);
		return new MongoCollection($db, $collection);
	}

	private function decorate (&$document) {
		$document['_id'] = (string)$document['_id'];
		$template = '';
		if (isset($document['template_separation'])) {
			$template = '-' . $document['template_separation'];
		}
		if ($this->local) {
			$path = $this::$singular . $template . '.html#{"Sep":"' . $this->collection . '", "a": {"id":"' . (string)$document['_id'] . '"}}';
		} else {
			$path = '/' . $this::$singular . $template;
			if (isset($document['code_name'])) {
				$path .= '/' . $document['code_name'] . '.html';
			} else {
				$path .= '/id/' . (string)$document['_id'] . '.html';
			}
		}
		$document['separation_path'] = $path;
		if (method_exists($this, 'document')) {
			$this->document($document);
		}
		if (method_exists($this, 'myDocument')) {
			$this->myDocument($document);
		}
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

	private static function id ($id) {
		return new MongoId((string)$id);
	}

	public function all () {
		$this->name = $this->collection;
		if ($this->publishable) {
			$this->criteria['status'] = 'published';
		}
		$this->total = self::db($this->collection)->find($this->criteria)->count();
		return $this->fetchAll($this->collection, self::db($this->collection)->find($this->criteria)->sort($this->sort)->limit($this->limit)->skip($this->skip));
	}

	public function byId ($id) {
		$this->name = $this::$singular;
		return self::db($this->collection)->findOne(['_id' => self::id($id)]);
	}

	public function bySlug ($slug) {
		$this->name = $this::$singular;
		return self::db($this->collection)->findOne(['code_name' => $slug]); 
	}

	public function featured () {
		$this->criteria['featured'] = 't';
		return $this->all();
	}

	public function byCategoryId ($categoryId) {
		$this->criteria['category'] = self::id($categoryId);
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
		$this->criteria['category'] = self::id($categoryId);
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
		$this->criteria['author'] = self::id($id);
	}

	public function byAuthorSlug ($slug) {
		$this->criteria['author'] = self::id($id);
	}

	public function tags () {
		if (!isset($this->tagCacheCollection)) {
			throw new Exception('Model configuration missing tagCacheCollection field');
		}
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