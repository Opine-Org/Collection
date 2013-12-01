<?php
/*
 * @version .1
 * @link https://raw.github.com/virtuecenter/collection/master/available/departments.php
 * @mode upgrade
 */
namespace Collection;

class departments {
	public $publishable = true;
	public $singular = 'event';

	public function index ($document) {
		return [
			'title' => $document['title'], 
			'description' => $document['description'], 
			'image' => $document['image'], 
			'tags' => $document['tags'], 
			'categories' => $document['categories'], 
			'date' => date('c', $document['created_date']->sec) 
		];
	}
}