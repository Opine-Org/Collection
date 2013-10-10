<?php
use Collection\Collection;
use UrlId\UrlId;
use DB\Mongo;

class videos {
	use Collection;
	public $publishable = true;
	public static $singular = 'video';

	public function document (&$document) {
		$document['video_id'] = null;
		$document['video_type'] = null;
		if (!empty($document['video'])) {
			$document['video_id'] = UrlId::parse($document['video'], $document['video_type']);
		}
		$document['category_titles'] = [];
		if (isset($document['categories']) && is_array($document['categories'])) {
			foreach ($document['categories'] as $id) {
				$category = Mongo::collection('categories')->findOne(['_id' => Mongo::id($id)], ['title']);
				if (!isset($category['_id'])) {
					continue;
				}
				$document['category_titles'][] = $category['title'];
			}
		}
	}
}