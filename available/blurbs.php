<?php
/*
 * @version .5
 * @link https://raw.github.com/virtuecenter/collection/master/available/blurbs.php
 * @mode upgrade
 *
 * .5 add view for generating alternate collection of blurbs
 */
namespace Collection;

class blurbs {
	public $publishable = false;
	public $singular = 'blurb';

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

	public function tagsView ($mode, $id, $document) {
		$this->queue->add('BlurbsToTags', []);
	}
}