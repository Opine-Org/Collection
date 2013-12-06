<?php
/*
 * @version .1
 * @link https://raw.github.com/virtuecenter/collection/master/available/pages_tags.php
 * @mode upgrade
 *
 * .1 initial load
 */
namespace Collection;

class pages_tags {
	public $publishable = false;
	public $singular = 'pages_tag';
	public $path = false;

	public function document (&$document) {
		$tmp = [
			'tag' => $document['_id'],
			'count' => $document['value']
		];
		$document = $tmp;
	}
}