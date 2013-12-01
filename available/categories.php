<?php
/*
 * @version .1
 * @link https://raw.github.com/virtuecenter/collection/master/available/categories.php
 * @mode upgrade
 */
namespace Collection;

class categories {
	public $publishable = false;
	public $singular = 'category';

	public function index ($document) {
		return [
			'title' => $document['title'], 
			'description' => '', 
			'image' => isset($document['image']) ? $document['image'] : '', 
			'tags' => [], 
			'categories' => [], 
			'date' => date('c', $document['created_date']->sec) 
		];
	}
}