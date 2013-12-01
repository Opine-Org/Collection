<?php
/*
 * @version .1
 * @link https://raw.github.com/virtuecenter/collection/master/available/peoples_person.php
 * @mode upgrade
 */
namespace Collection;

class peoples_person {
	public $publishable = true;
	public $singular = 'event';

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