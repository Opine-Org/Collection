<?php
class blurbsReportByTag {
	public $publishable = false;
	public $singular = 'blurb';
	public $path = false;

	public function all ($collection, $db) {
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

		$db->mapReduce($map, $reduce, [
			'mapreduce' => 'blurbs',
			'out' => 'blurbsReportByTag'
		]);

		$collection->total = $db->collection('blurbsReportByTag')->find($collection->criteria)->count();
		$docs = $collection->fetchAll('blurbsReportByTag', $db->collection('blurbsReportByTag')->find($collection->criteria)->sort($collection->sort)->limit($collection->limit)->skip($collection->skip));
		$docsOut = [];
		foreach ($docs as $doc) {
			$docsOut[$doc['_id']] = $doc['value'];
		}
		return $docsOut;
	}
}