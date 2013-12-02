<?php
/*
 * @version .2
 * @link https://raw.github.com/virtuecenter/collection/master/available/pages.php
 * @mode upgrade
 */
namespace Collection;

class pages {
	public $publishable = false;
	public $singular = 'page';


	public function index ($document) {
		return [
			'title' => $document['title'], 
			'description' => $document['metadata_description'], 
			'image' => null, 
			'tags' => isset($document['tags']) ? $document['tags'] : [], 
			'categories' => isset($document['categories']) ? $document['categories']: [],
			'date' => date('c', $document['created_date']->sec) 
		];
	}
}