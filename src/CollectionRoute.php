<?php
class CollectionRoute {
	public static function json ($app) {
		$app->get('/json-data/:collection/:method(/:limit(/:skip(/:sort)))', function ($collection, $method, $limit=20, $skip=0, $sort=[]) {
		    $collectionClass = $_SERVER['DOCUMENT_ROOT'] . '/collections/' . $collection . '.php';
		    if (!file_exists($collectionClass)) {
		        exit ($collection . ': unknown file.');
		    }
		    require_once($collectionClass);
		    if (!class_exists($collection)) {
		        exit ($collection . ': unknown class.');
		    }
		    $collectionObj = new $collection($limit, $skip, $sort);
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
		    $name = $collectionObj->collection;
		    if (isset($_GET['pretty'])) {
		        $options = JSON_PRETTY_PRINT;
		        $head = '<html><head></head><body style="margin:0; border:0; padding: 0"><textarea wrap="off" style="overflow: auto; margin:0; border:0; padding: 0; width:100%; height: 100%">';
		        $tail = '</textarea></body></html>';
		    }
		    if (in_array($method, ['byId', 'bySlug'])) {
		        $name = $collectionObj::$singular;
		        echo $head . json_encode([
		            $name => $collectionObj->$method($limit)
		        ], $options) . $tail;
		    } else {
		        echo $head . json_encode([
		            $name => $collectionObj->$method(),
		            'pagination' => [
		                'limit' => $limit,
		                'skip'  => $skip,
		                'total' => $collectionObj->totalGet()
		            ]
		        ], $options) . $tail;
		    }
		});
	}

	public static function pages (&$app, &$route) {
		$cacheFile = $_SERVER['DOCUMENT_ROOT'] . '/collections/cache.json';
		if (!file_exists($cacheFile)) {
			return;
		}
		$collections = (array)json_decode(file_get_contents($cacheFile), true);
		if (!is_array($collections)) {
			return;
		}
	    foreach ($collections as $collection) {
	        if (isset($collection['p'])) {
	            $app->get('/' . $collection['p'] . '(/:method(/:limit(/:skip(/:sort))))', function ($method='all', $limit=null, $skip=0, $sort=[]) use ($collection) {
		            if ($limit === null) {
		            	if (isset($collection['limit'])) {
		                	$limit = $collection['limit'];
		            	} else {
			            	$limit = 10;
			            }
		            }
		            foreach (['limit', 'skip', 'sort'] as $option) {
		            	$key = $collection['p'] . '-' . $method . '-' . $option;
		            	if (isset($_GET[$key])) {
		                	${$option} = $_GET[$key];
		            	}
		            }
		            $separation = Separation::layout($collection['p'] . '.html')->template()->write();
		        });
		    }
	        if (!isset($collection['s'])) {
	        	continue;
	        }
            $app->get('/' . $collection['s'] . '/:slug', function ($slug) use ($collection) {
                $separation = Separation::layout($collection['s'] . '.html')->set([
                	['Sep' => $collection['p'], 'a' => ['slug' => basename($slug, '.html')]]
                ])->template()->write();
            });
            if (isset($collection['partials']) && is_array($collection['partials'])) {
            	foreach ($collection['partials'] as $template) {
					$app->get('/' . $collection['s'] . '-' . $template . '/:slug', function ($slug) use ($collection, $template) {
		               	$separation = Separation::html($collection['s'] . '-' . $template . '.html')->template()->write();
        			});
        		}
            }
	    }
	}
}