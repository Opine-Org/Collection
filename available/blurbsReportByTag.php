<?php
class blurbsReportByTag {
	use Collection\Collection;
	public $publishable = false;
	public static $singular = 'blurb';
	public $path = false;

	public function all () {
		$map = <<<MAP
			function() {
				if (!this.tags) {
					emit('none', this.body);
					return;
				}
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

		$this->db->mapReduce($map, $reduce, [
			'mapreduce' => 'blurbs',
			'out' => 'blurbsReportByTag'
		]);

		$this->name = $this->collection;
		$this->total = $this->db->collection('blurbsReportByTag')->find($this->criteria)->count();
		$docs = $this->fetchAll('blurbsReportByTag', $this->db->collection('blurbsReportByTag')->find($this->criteria)->sort($this->sort)->limit($this->limit)->skip($this->skip));
		$docsOut = [];
		foreach ($docs as $doc) {
			$docsOut[$doc['_id']] = $doc['value'];
		}
		return $docsOut;
	}
}