<?php
class CollectionRoute {
	public static function json ($app) {
		$app->get('/json/:collection/:method(/:limit(/:skip(/:sort)))', function ($collection, $method, $limit=20, $skip=0, $sort=[]) {
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
}