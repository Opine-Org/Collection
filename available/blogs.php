<?php
/*
 * @version .1
 * @link https://raw.github.com/virtuecenter/collection/master/available/blogs.php
 * @mode upgrade
 */
namespace Collection;

class blogs {
	public $publishable = true;
	public $singular = 'blog';
	public $singlePath = '/blog/';

	public function index ($document) {
		return [
			'title' => $document['title'], 
			'description' => $document['description'], 
			'image' => isset($document['image']) ? $document['image'] : '', 
			'tags' => [], 
			'categories' => [], 
			'date' => date('c', $document['created_date']->sec) 
		];
	}
}