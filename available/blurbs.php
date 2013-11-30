<?php
/*
 * @version .3
 * @link https://raw.github.com/virtuecenter/collection/master/available/blurbs.php
 * @mode upgrade
 */
namespace Collection;

class blurbs {
	public $publishable = false;
	public $singular = 'blurb';

	public function index ($document) {
		return [
			'title' => $document['title'], 
			'description' => $document['body'], 
			'image' => null, 
			'tags' => $document['tags'],  
			'categories' => [], 
			'date' => date('c', $document['created_date']->sec) 
		];
	}
}