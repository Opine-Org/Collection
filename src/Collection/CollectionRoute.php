<?php
/**
 * virtuecenter\collection
 *
 * Copyright (c)2013 Ryan Mahoney, https://github.com/virtuecenter <ryan@virtuecenter.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Collection;

class CollectionRoute {
	public $cache = false;
	private $separation;
	private $slim;
	private $db;
	private $response;

	public function __construct ($collection, $slim, $db, $separation, $response) {
		$this->slim = $slim;
		$this->db = $db;
		$this->separation = $separation;
		$this->response = $response;
		$this->collection = $collection;
	}

	public function cacheSet ($cache) {
		$this->cache = $cache;
	}

	public function json ($root, $prefix='') {
		$this->slim->get($prefix . '/json-data/:collection/:method(/:limit(/:page(/:sort)))', function ($collection, $method, $limit=20, $page=1, $sort=[]) {
			if (in_array($method, ['byId', 'bySlug'])) {
				$value = $limit;
	        } else {
	        	$value = false;
	            if (substr_count($method, '-') > 0) {
	            	list($method, $value) = explode('-', urldecode($method), 2);
	            }
	        }
			if ($page == 0) {
				$page = 1;
			}
		    $collectionObj = $this->collection->factory($collection, $limit, $page, $sort);
		    if (isset($_REQUEST['Sep-local'])) {
		        $collectionObj->localSet();
		    }
		    if (!method_exists($collectionObj, $method)) {
		        exit ($method . ': unknown method.');
		    }
		    $head = '';
		    $tail = '';
		    if (isset($_GET['callback'])) {
		        if ($_GET['callback'] == '?') {
		            $_GET['callback'] = 'callback';
		        }
		        $head = $_GET['callback'] . '(';
		        $tail = ');';
		    }
		    $options = null;
		    $data = $collectionObj->$method($value);
		    $name = $collectionObj->collection();
		    if (isset($_GET['pretty'])) {
		        $options = JSON_PRETTY_PRINT;
		        $head = '<html><head></head><body style="margin:0; border:0; padding: 0"><textarea wrap="off" style="overflow: auto; margin:0; border:0; padding: 0; width:100%; height: 100%">';
		        $tail = '</textarea></body></html>';
		    }
		    if (in_array($method, ['byId', 'bySlug'])) {
		        $name = $collectionObj::$singular;
		        echo $head . json_encode([
		            $name => $data
		        ], $options) . $tail;
		    } else {
		        echo $head . json_encode([
		            $name => $data,
		            'pagination' => [
		                'limit' => $limit,
		                'total' => $collectionObj->totalGet(),
		                'page' => $page,
		                'pageCount' => ceil($collectionObj->totalGet() / $limit)
		            ]
		        ], $options) . $tail;
		    }
		});

		$this->slim->get($prefix . '/json-collections', function () use ($root) {
			if (!empty($this->cache)) {
				$collections = $this->cache;
			} else {
				$cacheFile = $root . '/../collections/cache.json';
				if (!file_exists($cacheFile)) {
					return;
				}
				$collections = (array)json_decode(file_get_contents($cacheFile), true);
			}
			if (!is_array($collections)) {
				return;
			}
		    foreach ($collections as &$collection) {
		    	$collectionObj = $this->collection->factory($collection['p']);
		    	$reflection = new \ReflectionClass($collectionObj);
				$methods = $reflection->getMethods();
				foreach ($methods as $method) {
					if (in_array($method->name, ['document','__construct','totalGet','localSet','decorate','fetchAll'])) {
						continue;
					}
					$collection['methods'][] = $method->name;
				}
		    }
		    $head = '';
		    $tail = '';
		    if (isset($_GET['callback'])) {
		        if ($_GET['callback'] == '?') {
		            $_GET['callback'] = 'callback';
		        }
		        $head = $_GET['callback'] . '(';
		        $tail = ');';
		    }
		    echo $head . json_encode($collections) . $tail;
		});
	}

	public function app ($root) {
		if (!empty($this->cache)) {
			$collections = $this->cache;
		} else {
			$cacheFile = $root . '/../collections/cache.json';
			if (!file_exists($cacheFile)) {
				return;
			}
			$collections = (array)json_decode(file_get_contents($cacheFile), true);
		}
		if (!is_array($collections)) {
			return;
		}
	    foreach ($collections as $collection) {
	        if (isset($collection['p'])) {
	            $this->slim->get('/' . $collection['p'] . '(/:method(/:limit(/:page(/:sort))))', function ($method='all', $limit=null, $page=1, $sort=[]) use ($collection, $root) {
		            if ($limit === null) {
		            	if (isset($collection['limit'])) {
		                	$limit = $collection['limit'];
		            	} else {
			            	$limit = 10;
			            }
		            }
		            $args = [];
		            if ($limit != null) {
		            	$args['limit'] = $limit;
		            }
		            $args['method'] = $method;
		            $args['page'] = $page;
		          	$args['sort'] = json_encode($sort);
		            foreach (['limit', 'page', 'sort'] as $option) {
		            	$key = $collection['p'] . '-' . $method . '-' . $option;
		            	if (isset($_GET[$key])) {
		                	$args[$option] = $_GET[$key];
		            	}
		            }
		            $this->separation->layout('collections/' . $collection['p'])->args($collection['p'], $args)->template()->write($this->response->body);
		        })->name($collection['p']);
		    }
	        if (!isset($collection['s'])) {
	        	continue;
	        }
            $this->slim->get('/' . $collection['s'] . '/:slug', function ($slug) use ($collection) {
                $this->separation->layout('documents/' . $collection['s'])->args($collection['s'], ['slug' => basename($slug, '.html')])->template()->write($this->response->body);
            })->name($collection['s']);

			/*
            if (isset($collection['partials']) && is_array($collection['partials'])) {
            	foreach ($collection['partials'] as $template) {
					$this->slim->get('/' . $collection['s'] . '-' . $template . '/:slug', function ($slug) use ($collection, $template) {
		               	$this->separation->layout($collection['s'] . '-' . $template)->template()->write($this->response->body);
        			});
        		}
            }
            */
	    }
	}

	public function build ($root, $url) {
		$cache = [];
		$dirFiles = glob($root . '/../collections/*.php');
		foreach ($dirFiles as $collection) {
			require_once($collection);
			$collection = basename($collection, '.php');
			$className = 'Collection\\' . $collection;
			$instance = new $className();
			$cache[] = [
				'p' => $collection,
				's' => $instance->singular
			];
		}
		$json = json_encode($cache, JSON_PRETTY_PRINT);
		file_put_contents($root . '/../collections/cache.json', $json);
		foreach ($cache as $collection) {
			$filename = $root . '/layouts/collections/' . $collection['p'] . '.html';
			if (!file_exists($filename)) {
				file_put_contents($filename, self::stubRead('layout-collection.html', $collection, $url, $root));
			}
			$filename = $root . '/partials/collections/' . $collection['p'] . '.hbs';
			if (!file_exists($filename)) {
				file_put_contents($filename, self::stubRead('partial-collection.hbs', $collection, $url, $root));
			}
			$filename = $root . '/layouts/documents/' . $collection['s'] . '.html';
			if (!file_exists($filename)) {
				file_put_contents($filename, self::stubRead('layout-document.html', $collection, $url, $root));
			}
			$filename = $root . '/partials/documents/' . $collection['s'] . '.hbs';
			if (!file_exists($filename)) {
				file_put_contents($filename, self::stubRead('partial-document.hbs', $collection, $url, $root));
			}
			$filename = $root . '/../app/collections/' . $collection['p'] . '.yml';
			if (!file_exists($filename)) {
				file_put_contents($filename, self::stubRead('app-collection.yml', $collection, $url, $root));
			}
			$filename = $root . '/../app/documents/' . $collection['s'] . '.yml';
			if (!file_exists($filename)) {
				file_put_contents($filename, self::stubRead('app-document.yml', $collection, $url, $root));
			}
		}
		return $json;
	}

	private static function stubRead ($name, &$collection, $url, $root) {
		$data = file_get_contents($root . '/../vendor/virtuecenter/build/static/' . $name);
		return str_replace(['{{$url}}', '{{$plural}}', '{{$singular}}'], [$url, $collection['p'], $collection['s']], $data);
	}

	public function collectionList ($root) {
		$this->slim->get('/collections', function () use ($root) {
			$collections = (array)json_decode(file_get_contents($root . '/../collections/cache.json'), true);
			echo '<html><body>';
			foreach ($collections as $collection) {
				echo '<a href="/json-data/' . $collection['p'] . '/all?pretty">', $collection['p'], '</a><br />';
			}
			echo '</body></html>';
			exit;
		})->name('collections');
	}
}