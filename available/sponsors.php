<?php
/*
 * @version .1
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
			'image' => $document['image'], 
			'tags' => [], 
			'categories' => $document['categories'], 
			'date' => date('c', $document['created_date']->sec) 
		];
	}
}