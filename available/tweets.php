<?php
/*
 * @version .1
 * @link https://raw.github.com/virtuecenter/collection/master/available/tweets.php
 * @mode upgrade
 */
namespace Collection;

class blurbsReportByTag {
	public $publishable = false;
	public $singular = 'tweet';
	public $path = false;

	public function all ($instance) {
		$container = \Framework::container();
		$tweets = $container->twitter->tweets();
	}
}