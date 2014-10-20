<?php
/**
 * Opine\Collection\View
 *
 * Copyright (c)2013, 2014 Ryan Mahoney, https://github.com/Opine-Org <ryan@virtuecenter.com>
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
namespace Opine\Collection;

class View {
	private $layout;

	public function __construct ($layout) {
		$this->layout = $layout;
	}

	public function htmlIndex ($name, $args=[]) {
		$this->layout->
            app('app/collections/' . $name)->
            layout('collections/' . $name)->
            args($name, $args)->
            template()->
            write();
	}

	public function html ($name, $slug) {
		$this->layout->
            app('app/documents/' . $name)->
            layout('documents/' . $name)->
            args($name, ['slug' => basename($slug, '.html')])->
            template()->
            write();
	}

	public function htmlCollectionIndex ($collections) {
		echo '<html><body>';
        foreach ($collections as $collection) {
            echo '<a href="/api/collection/' . $collection['p'] . '/all?pretty">', $collection['p'], '</a><br />';
        }
        echo '</body></html>';
	}

	public function json (\Opine\Collection\Service $collection) {
        $method = $collection->method;
        $value = $collection->value;
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
        $name = $collection->collection();
        if ($method == 'byEmbeddedField') {
            $name = $collection->name;
        }      
        if (isset($_GET) && isset($_GET['pretty'])) {
            $options = JSON_PRETTY_PRINT;
            $head = '<html><head></head><body style="margin:0; border:0; padding: 0"><textarea wrap="off" style="overflow: auto; margin:0; border:0; padding: 0; width:100%; height: 100%">';
            $tail = '</textarea></body></html>';
        }
        if (in_array($method, ['byId', 'bySlug'])) {
            $name = $collection->singular;
            echo $head . json_encode([
                $name => $collection->$method($value)
            ], $options) . $tail;
        } else {
            echo $head . json_encode([
                $name => $collection->$method($value),
                'pagination' => [
                    'limit' => $collection->limit,
                    'total' => $collection->totalGet(),
                    'page' => $collection->page,
                    'pageCount' => ceil($collection->totalGet() / $collection->limit)
                ],
                'metadata' => array_merge(
                    ['display' => [
                        'collection' => ucwords(str_replace('_', ' ', $collection->collection)),
                        'document' => ucwords(str_replace('_', ' ', $collection->singular)),
                    ],
                    'method' => $method
                ], get_object_vars($collection))
            ], $options) . $tail;
        }
    }
}