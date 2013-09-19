<?php
class blogs {
	use Collection;
	public $publishable = true;
	public static $singular = 'blog';
	public $tagCacheCollection = 'blogsTags';

	public function document (&$document) {
		//format date
		if (isset($document['display_date'])) {
			$document['display_date__MdY'] = date('M d, Y', $document['display_date']->sec);
		}

		//lookup authors
		

		//lookup categories
	}

	public function documentTags (&$document) {
		$document['tag'] = $document['_id'];
		$document['count'] = $document['value'];
		unset($document['_id']);
		unset($document['value']);
	}
}