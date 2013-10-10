<?php
use Collection\Collection;
use UrlId\UrlId;

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
	}
}