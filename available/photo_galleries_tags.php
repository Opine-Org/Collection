<?php
/*
 * @version .1
 * @link https://raw.github.com/virtuecenter/collection/master/available/photo_galleries_tags.php
 * @mode upgrade
 *
 * .1 initial load
 */
namespace Collection;

class photo_galleries_tags {
	public $publishable = false;
	public $singular = 'photo_galleries_tag';
	public $path = false;

	public function document (&$document) {
		$tmp = [
			'tag' => $document['_id'],
			'count' => $document['value']
		];
		$document = $tmp;
	}
}