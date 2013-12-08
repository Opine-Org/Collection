<?php
/*
 * @version .3
 * @link https://raw.github.com/virtuecenter/collection/master/available/blurbs_by_tag.php
 * @mode upgrade
 *
 * .2 reshape output
 * .3 syntax
 */
namespace Collection;

class blurbs_by_tag {
	public $publishable = false;
	public $singular = 'blurb';
	public $path = false;

	public function all ($collection) {
		$documents = $collectiton->all();
		$newDocs = [];
		
		foreach ($documents as $document) {
			$newDocs[$document['_id']] = $document['value'];
		}

		return $newDocs;
	}
}