<?php
/*
 * @version .4
 * @link https://raw.github.com/virtuecenter/collection/master/available/events.php
 * @mode upgrade
 *
 * .3 tag view
 * .4 index enhancement
 */
namespace Collection;

class events {
	public $publishable = true;
	public $singular = 'event';

	public function index ($document) {
		$depth = substr_count($document['dbURI'], ':');
		if ($depth > 1) {
			return false;
		}
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