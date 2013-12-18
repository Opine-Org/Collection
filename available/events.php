<?php
/*
 * @version .3
 * @link https://raw.github.com/virtuecenter/collection/master/available/events.php
 * @mode upgrade
 *
 * .3 tag view
 */
namespace Collection;

class events {
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

	public function tagsView ($mode, $id, $document) {
		$this->queue->add('CollectionTags', ['collection' => 'events']);
	}
}