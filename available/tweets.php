<?php
/*
 * @version .3
 * @link https://raw.github.com/virtuecenter/collection/master/available/tweets.php
 * @mode upgrade
 *
 * .1 initial load
 * .2 typo
 * .3 missing logic
 */
namespace Collection;

class tweets {
	public $publishable = false;
	public $singular = 'tweet';
	public $path = false;

	public function __construct () {
		
	}

	public function all ($instance) {
		$container = \Framework\container();
		$twitterquery = false;
		if (isset($_GET['twitterquery'])) {
			$twitterquery = $_GET['twitterquery'];
		} else {
			$config = $container->config;
			$config = $config->twitter;
			if (isset($config['default'])) {
				$twitterquery = $config['default'];
			}
		}
		if ($twitterquery === false) {
			return [];
		}	
		return $container->twitter->tweets($twitterquery);
	}
}