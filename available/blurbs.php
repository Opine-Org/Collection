<?php
use Collection\Collection;
use DB\Mongo;

class blurbs {
	use Collection;
	public $publishable = false;
	public static $singular = 'blurb';
	public $path = false;

	public function all () {
		$map = <<<MAP
			function() {
				for (var i=0; i < this.tags.length; i++) {
					emit(this.tags[i], this.body);
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
		
		\DB\Mongo::mapReduce($map, $reduce, [
			'mapreduce' => 'blurbs',
			'out' => ' ns does not exist'
		]);


		$this->name = $this->collection;
		$this->total = Mongo::collection('blurbsMR')->find($this->criteria)->count();
		$docs = $this->fetchAll('blurbsMR', Mongo::collection('blurbsMR')->find($this->criteria)->sort($this->sort)->limit($this->limit)->skip($this->skip));
		$docsOut = [];
		foreach ($docs as $doc) {
			$docsOut[$doc['_id']] = $doc['value'];
		}
		return $docsOut;
	}
}