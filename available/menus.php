<?php
/*
 * @version .2
 * @link https://raw.github.com/virtuecenter/collection/master/available/menus.php
 * @mode upgrade
 */
namespace Collection;

class menus {
	public $publishable = false;
	public $singular = 'menu';

	public function index ($document) {
		return [
			'title' => $document['label'], 
			'description' => '', 
			'image' => null, 
			'tags' => [], 
			'categories' => [], 
			'date' => date('c', $document['created_date']->sec) 
		];
	}
}