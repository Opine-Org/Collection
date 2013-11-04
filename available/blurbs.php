<?php
namespace Collection;

class blurbs {
	public $publishable = false;
	public $singular = 'blurb';
	public function index ($document) {
		return [
			'title' => $document['title'], 
			'description' => $document['body'], 
			'image' => null, 
			'tags' => [], 
			'categories' => [], 
			'date' => date('c', $document['created_date']->sec) 
		];
	}
}