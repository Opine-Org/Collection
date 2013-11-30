<?php
/*
 * @version .1
 * @link https://raw.github.com/virtuecenter/collection/master/available/links.php
 * @mode upgrade
 */
namespace Collection;

class links {
	public $publishable = true;
	public $singular = 'link';

	public function index ($document) {
		return [
			'title' => $document['title'], 
			'description' => $document['description'], 
			'image' => $document['image'], 
			'tags' => [], 
			'categories' => [], 
			'date' => date('c', $document['created_date']->sec) 
		];
	}
}