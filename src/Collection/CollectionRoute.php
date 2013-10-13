<?php
namespace Collection;

class CollectionRoute {
	public static $cache = false;
	private $separation;
	private $slim;
	private $db;

	public function __construct ($slim, $db, $separation) {
		$this->slim = $slim;
		$this->db = $db;
		$this->separation = $separation;
	}

	public function cacheSet ($cache) {
		self::$cache = $cache;
	}

	public function json ($root) {
		$this->slim->get('/json-data/:collection/:method(/:limit(/:page(/:sort)))', function ($collection, $method, $limit=20, $page=1, $sort=[]) use ($root) {
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
		    $collectionClass = $root . '/collections/' . $collection . '.php';
		    if (!file_exists($collectionClass)) {
		        exit ($collection . ': unknown file.');
		    }
		    require_once($collectionClass);
		    if (!class_exists($collection)) {
		        exit ($collection . ': unknown class.');
		    }
		    $collectionObj = new $collection($this->db, $limit, $page, $sort);
		    if (isset($_REQUEST['Sep-local'])) {
		        $collectionObj->localSet();
		    }
		    if (!method_exists($collection, $method)) {
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
		    $name = $collectionObj->collection;
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

		$this->slim->get('/json-collections', function () use ($root) {
			if (!empty(self::$cache)) {
				$collections = self::$cache;
			} else {
				$cacheFile = $root . '/collections/cache.json';
				if (!file_exists($cacheFile)) {
					return;
				}
				$collections = (array)json_decode(file_get_contents($cacheFile), true);
			}
			if (!is_array($collections)) {
				return;
			}
		    foreach ($collections as &$collection) {
		    	$collectionClass = $root . '/collections/' . $collection['p'] . '.php';
		    	if (!file_exists($collectionClass)) {
		        	exit ($collection['p'] . ': unknown file.');
		    	}
			    require_once($collectionClass);
		    	$reflection = new \ReflectionClass($collection['p']);
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

	public function pages ($root) {
		if (!empty(self::$cache)) {
			$collections = self::$cache;
		} else {
			$cacheFile = $root . '/collections/cache.json';
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
		            $this->separation->layout($collection['p'])->set([
		            	['id' => $collection['p'], 'args' => $args]
		            ])->template()->write();
		        })->name($collection['p']);
		    }
	        if (!isset($collection['s'])) {
	        	continue;
	        }
            $this->slim->get('/' . $collection['s'] . '/:slug', function ($slug) use ($collection) {
                $separation = $this->separation->layout($collection['s'])->set([
                	['id' => $collection['p'], 'args' => ['slug' => basename($slug, '.html')]]
                ])->template()->write();
            })->name($collection['s']);
            if (isset($collection['partials']) && is_array($collection['partials'])) {
            	foreach ($collection['partials'] as $template) {
					$this->slim->get('/' . $collection['s'] . '-' . $template . '/:slug', function ($slug) use ($collection, $template) {
		               	$separation = $this->separation->layout($collection['s'] . '-' . $template)->template()->write();
        			});
        		}
            }
	    }
	}

	public function build ($root, $url) {
		$cache = [];
		$dirFiles = glob($root . '/collections/*.php');
		foreach ($dirFiles as $collection) {
			require_once($collection);
			$class = basename($collection, '.php');
			$cache[] = [
				'p' => $class,
				's' => $class::$singular
			];
		}
		$json = json_encode($cache, JSON_PRETTY_PRINT);
		file_put_contents($root . '/collections/cache.json', $json);
		foreach ($cache as $collection) {
			$filename = $root . '/layouts/' . $collection['p'] . '.html';
			if (!file_exists($filename)) {
				file_put_contents($filename, self::stubRead('layout-collection.html', $collection, $url, $root));
			}
			$filename = $root . '/partials/' . $collection['p'] . '.hbs';
			if (!file_exists($filename)) {
				file_put_contents($filename, self::stubRead('partial-collection.hbs', $collection, $url, $root));
			}
			$filename = $root . '/layouts/' . $collection['s'] . '.html';
			if (!file_exists($filename)) {
				file_put_contents($filename, self::stubRead('layout-document.html', $collection, $url, $root));
			}
			$filename = $root . '/partials/' . $collection['s'] . '.hbs';
			if (!file_exists($filename)) {
				file_put_contents($filename, self::stubRead('partial-document.hbs', $collection, $url, $root));
			}
			$filename = $root . '/sep/' . $collection['p'] . '.js';
			if (!file_exists($filename)) {
				file_put_contents($filename, self::stubRead('sep-collection.js', $collection, $url, $root));
			}
			$filename = $root . '/app/' . $collection['p'] . '.json';
			if (!file_exists($filename)) {
				file_put_contents($filename, self::stubRead('app-collection.json', $collection, $url, $root));
			}
			$filename = $root . '/sep/' . $collection['s'] . '.js';
			if (!file_exists($filename)) {
				file_put_contents($filename, self::stubRead('sep-document.js', $collection, $url, $root));
			}
			$filename = $root . '/app/' . $collection['s'] . '.json';
			if (!file_exists($filename)) {
				file_put_contents($filename, self::stubRead('app-document.json', $collection, $url, $root));
			}
		}
		return $json;
	}

	private static function stubRead ($name, &$collection, $url, $root) {
		$data = file_get_contents($root . '/vendor/virtuecenter/build/static/' . $name);
		return str_replace(['{{$url}}', '{{$plural}}', '{{$singular}}'], [$url, $collection['p'], $collection['s']], $data);
	}
}