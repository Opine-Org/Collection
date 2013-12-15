<?php
/*
 * @version .3
 * @link https://raw.github.com/virtuecenter/collection/master/available/jobs.php
 * @mode upgrade
 */
namespace Collection;

class jobs {
	public $publishable = false;
	public $singular = 'job';

	public function index ($document) {
		return [
			'title' => $document['title'], 
			'description' => $document['body'], 
			'image' => null, 
			'tags' => isset($document['tags']) ? $document['tags'] : [], 
			'categories' => [],
			'date' => date('c', $document['created_date']->sec) 
		];
	}
}