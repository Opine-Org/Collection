<?php
/*
 * @version .1
 * @link https://raw.github.com/virtuecenter/collection/master/available/publications_tags.php
 * @mode upgrade
 *
 * .1 initial load
 */
namespace Collection;

class publications_tags {
	public $publishable = false;
	public $singular = 'publications_tag';
	public $path = false;

	public function document (&$document) {
		$tmp = [
			'tag' => $document['_id'],
			'count' => $document['value']
		];
		$document = $tmp;
	}
}