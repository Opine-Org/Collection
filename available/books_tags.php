<?php
/*
 * @version .1
 * @link https://raw.github.com/virtuecenter/collection/master/available/books_tags.php
 * @mode upgrade
 *
 * .1 initial load
 */
namespace Collection;

class books_tags {
	public $publishable = false;
	public $singular = 'books_tag';
	public $path = false;

	public function document (&$document) {
		$tmp = [
			'tag' => $document['_id'],
			'count' => $document['value']
		];
		$document = $tmp;
	}
}