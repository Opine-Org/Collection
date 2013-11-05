<?php
/*
 * @version .2
 * @link https://raw.github.com/virtuecenter/collection/master/available/blurbsReportByTag.php
 * @mode upgrade
 */
namespace Collection;

class blurbsReportByTag {
	public $publishable = false;
	public $singular = 'blurb';
	public $path = false;

	public function all ($collection) {
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

		$collection->total = $collection->db->collection('blurbsReportByTag')->find($collection->criteriaGet())->count();
		$docs = $collection->fetchAll('blurbsReportByTag', $db->collection('blurbsReportByTag')->find($collection->criteriaGet())->sort($collection->sortGet())->limit($collection->limitGet())->skip($collection->skipGet()));
		$docsOut = [];
		foreach ($docs as $doc) {
			$docsOut[$doc['_id']] = $doc['value'];
		}
		return $docsOut;
	}
}