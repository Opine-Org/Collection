<?php
/*
 * @version .1
 * @link https://raw.github.com/virtuecenter/collection/master/available/photo_galleries.php
 * @mode upgrade
 */
namespace Collection;

class photo_galleries {
	public $publishable = true;
	public $singular = 'photo_gallery';

	public function index ($document) {
		return [
			'title' => $document['title'], 
			'description' => $document['description'], 
			'image' => $document['image'], 
			'tags' => $document['tags'], 
			'categories' => $document['catgories'], 
			'date' => date('c', $document['created_date']->sec) 
		];
	}
}