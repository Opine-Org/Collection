<?php
/*
 * @version .2
 * @link https://raw.github.com/virtuecenter/collection/master/available/photo_galleries.php
 * @mode upgrade
 */
namespace Collection;

class photo_galleries {
	public $publishable = true;
	public $singular = 'photo_gallery';
	public $permalink = '/photo_gallery/';

	public function index ($document) {
		$depth = substr_count($document['dbURI'], ':');
		if ($depth > 1) {
			return false;
		}
		return [
			'title' => $document['title'], 
			'description' => $document['description'], 
<<<<<<< HEAD
			'image' => $document['image'], 
			'tags' => $document['tags'], 
			'categories' => $document['catgories'], 
=======
			'image' => isset($document['image']) ? $document['image'] : '',
			'tags' => [], 
			'categories' => [], 
>>>>>>> 46bfa0de63733a0b1b2a0815f99bdf57ac72c15f
			'date' => date('c', $document['created_date']->sec) 
		];
	}
}