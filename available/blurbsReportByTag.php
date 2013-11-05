<?php
/*
 * @version .5
 * @link https://raw.github.com/virtuecenter/collection/master/available/blurbsReportByTag.php
 * @mode upgrade
 */
namespace Collection;

class blurbsReportByTag {
	public $publishable = false;
	public $singular = 'blurb';
	public $path = false;

	public function all ($instance) {
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

		$instance->db->mapReduce($map, $reduce, [
			'mapreduce' => 'blurbs',
			'out' => 'blurbsReportByTag'
		]);

		$instance->total = $instance->db->collection('blurbsReportByTag')->find($instance->criteria)->count();
		$docs = $instance->fetchAll('blurbsReportByTag', $instance->db->collection('blurbsReportByTag')->find($instance->criteria)->sort($instance->sort)->limit($instance->limit)->skip($instance->skip));
		$docsOut = [];
		foreach ($docs as $doc) {
			$docsOut[$doc['_id']] = $doc['value'];
		}
		return $docsOut;
	}
}