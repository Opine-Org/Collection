<?php
/*
 * @version .1
 * @link https://raw.github.com/virtuecenter/collection/master/available/programs.php
 * @mode upgrade
 */
namespace Collection;

class programs {
	public $publishable = true;
	public $singular = 'program';

	public function index ($document) {
		return [
			'title' => $document['title'], 
			'description' => $document['description'], 
			'image' => $document['image'], 
			'tags' => $document['tags'], 
			'categories' => [], 
			'date' => date('c', $document['created_date']->sec) 
		];
	}
}