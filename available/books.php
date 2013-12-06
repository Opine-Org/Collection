<?php
/*
 * @version .4
 * @link https://raw.github.com/virtuecenter/collection/master/available/books.php
 * @mode upgrade
 *
 * .4 tag view
 */
namespace Collection;

class books {
	public $publishable = true;
	public $singular = 'book';

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

	public function tagsView ($mode, $id, $document) {
		$this->queue->add('CollectionTags', ['collection' => 'books']);
	}
}