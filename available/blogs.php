<?php
/*
 * @version .5
 * @link https://raw.github.com/virtuecenter/collection/master/available/blogs.php
 * @mode upgrade
 *
 * .4 remove dead code
 * .5 tag view
 */
namespace Collection;

class blogs {
	public $publishable = true;
	public $singular = 'blog';

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

	public function byTagsView ($mode, $id, $document) {
		$this->queue->add('BlurbsByTag', []);
	}
}