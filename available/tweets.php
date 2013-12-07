<?php
/*
 * @version .4
 * @link https://raw.github.com/virtuecenter/collection/master/available/tweets.php
 * @mode upgrade
 *
 * .1 initial load
 * .2 typo
 * .3 missing logic
 * .4 warm cache 
 */
namespace Collection;

class tweets {
	public $publishable = false;
	public $singular = 'tweet';
	public $path = false;

	public function byField ($collection, $field) {
		list ($field, $value) = explode('-', $field, 2);
		$collection->criteria[$field] = $value;

		list ($type, $query) = explode('-', $value, 2);
		$container = \Framework\container();
		if (empty($query)) {
			return $collection->all();
		}
		$container->twitter->tweets($query, 10, 600, $type);
		return $collection->all();
	}
}