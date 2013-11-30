<?php
/*
 * @version .2
 * @link https://raw.github.com/virtuecenter/collection/master/available/blogs.php
 * @mode upgrade
 */
namespace Collection;

class blogs {
	public $publishable = true;
	public $singular = 'blog';
	public $permalink = '/blog/';

	public function index ($document) {
		return [
			'title' => $document['title'],
			'description' => $document['description'],
			'image' => isset($document['image']) ? $document['image'] : '', 
			'tags' => isset($document['tags']) ? $document['tags'] : [], 
			'categories' => isset($document['categories']) ? $document['categories']: [],
			'date' => date('c', $document['created_date']->sec) 
		];
	}
}