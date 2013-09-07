<?php
class blogs {
	use Collection;
	public $publishable = true;
	public static $singular = 'blog';

	public function document (&$document) {
		//format date
		if (isset($document['display_date'])) {
			$document['display_date__MdY'] = date('M d, Y', $document['display_date']->sec);
		}

		//lookup authors
		

		//lookup categories
	}
}