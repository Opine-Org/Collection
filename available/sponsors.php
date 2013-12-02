<?php
/*
 * @version .2
 * @link https://raw.github.com/virtuecenter/collection/master/available/sponsors.php
 * @mode upgrade
 */
namespace Collection;

class sponsors {
	public $publishable = true;
	public $singular = 'sponsor';

	public function index ($document) {
		return [
			'title' => $document['title'], 
			'description' => $document['description'], 
			'image' => isset($document['image']) ? $document['image'] : '',
			'tags' => [], 
			'categories' => isset($document['categories']) ? $document['categories']: [],
			'date' => date('c', $document['created_date']->sec) 
		];
	}
}