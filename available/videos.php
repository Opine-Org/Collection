<?php
use UrlId\UrlId;

class videos {
	public $publishable = true;
	public $singular = 'video';

	public function document (&$document) {
		$document['video_id'] = null;
		$document['video_type'] = null;
		if (!empty($document['video'])) {
			$document['video_id'] = UrlId::parse($document['video'], $document['video_type']);
		}
		$document['category_titles'] = [];
		if (isset($document['categories']) && is_array($document['categories'])) {
			foreach ($document['categories'] as $id) {
				$category = $this->db->collection('categories')->findOne(['_id' => $this->db->id($id)], ['title']);
				if (!isset($category['_id'])) {
					continue;
				}
				$document['category_titles'][] = $category['title'];
			}
		}
	}
}