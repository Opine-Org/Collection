<?php
/*
 * @version .2
 * @link https://raw.github.com/virtuecenter/collection/master/available/carouselsReportByTag.php
 * @mode upgrade
 *
 * .2 correct field to provide
 */
namespace Collection;

class carouselsReportByTag {
	public $publishable = false;
	public $singular = 'carousels';
	public $path = false;

	public function all ($instance) {
		$map = <<<MAP
			function() {
				if (!this.tags) {
					emit('none', this.body);
					return;
				}
				for (var i=0; i < this.tags.length; i++) {
					emit(this.tags[i], this.carousel_individual);
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
			'mapreduce' => 'carousels',
			'out' => 'carouselsReportByTag'
		]);

		$instance->total = $instance->db->collection('carouselsReportByTag')->find($instance->criteria)->count();
		$docs = $instance->fetchAll('carouselsReportByTag', $instance->db->collection('carouselsReportByTag')->find($instance->criteria)->sort($instance->sort)->limit($instance->limit)->skip($instance->skip));
		$docsOut = [];
		foreach ($docs as $doc) {
			$docsOut[$doc['_id']] = $doc['value'];
		}
		return $docsOut;
	}
}