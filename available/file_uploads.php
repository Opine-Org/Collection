<?php
/*
 * @version .1
 * @link https://raw.github.com/virtuecenter/collection/master/available/file_uploads.php
 * @mode upgrade
 */
namespace Collection;

class file_uploads {
	public $publishable = true;
	public $singular = 'book';

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