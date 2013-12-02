<?php
/*
 * @version .2
 * @link https://raw.github.com/virtuecenter/collection/master/available/podcasts.php
 * @mode upgrade
 */
namespace Collection;

class podcasts {
	public $publishable = true;
	public $singular = 'podcast';

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