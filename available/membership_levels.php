<?php
/*
 * @version .1
 * @link https://raw.github.com/virtuecenter/collection/master/available/membership_levels.php
 * @mode upgrade
 */
namespace Collection;

class membership_levels {
	public $publishable = true;
	public $singular = 'membership_level';

	public function index ($document) {
		return [
			'title' => $document['title'], 
			'description' => $document['description'], 
			'image' => [], 
			'tags' => [], 
			'categories' => [], 
			'date' => date('c', $document['created_date']->sec) 
		];
	}
}