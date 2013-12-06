<?php
/*
 * @version .1
 * @link https://raw.github.com/virtuecenter/collection/master/available/tweets.php
 * @mode upgrade
 *
 * .1 initial load
 */
namespace Collection;

class tweets {
	public $publishable = false;
	public $singular = 'tweet';
	public $path = false;

	public function all ($instance) {
		$container = \Framework::container();
		$tweets = $container->twitter->tweets();
	}
}