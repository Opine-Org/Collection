<?php
/**
 * Opine\Collection\Controller
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
use ReflectionClass;
use ReflectionMethod;

class Controller {
	private $model;
	private $view;
    private $collection;
    private $person;
    private $language;

	public function __construct ($model, $view, $collection, $person, $language) {
		$this->model = $model;
		$this->view = $view;
        $this->collection = $collection;
        $this->person = $person;
        $this->language = $language;
	}

    public function json ($collection, $method='all', $limit=20, $page=1, $sort=[], $fields=[]) {
        $collectionClass = '\Collection\\' . $collection;
        if (!class_exists($collectionClass)) {
            throw new Exception ('Collection not found: ' . $collectionClass);
        }
        $this->pathOverride($method, $limit, $page, $sort, $fields);
        $this->view->json($this->model->generate(new $collectionClass, $method, $limit, $page, $sort, $fields));
    }

    public function jsonBundle ($bundle, $collection, $method='all', $limit=20, $page=1, $sort=[], $fields=[]) {
        $collectionClass = '\\' . $bundle . '\Collection\\' . $collection;
        if (!class_exists($collectionClass)) {
            throw new Exception ('Bundled Collection not found: ' . $collectionClass);
        }
        $this->pathOverride($method, $limit, $page, $sort, $fields);
        $this->view->json($this->model->generate(new $collectionClass, $method, $limit, $page, $sort, $fields));
    }

    private function pathOverride (&$method, &$limit, &$page, &$sort, &$fields) {
        foreach (['method', 'limit', 'page', 'sort', 'fields'] as $key) {
            if (isset($_GET) && isset($_GET[$key])) {
                ${$key} = $_GET[$key];
            }
        }
    }

    public function htmlIndex ($method='all', $limit=10, $page=1, $sort=[]) {
        $path = $this->language->pathEvaluate($_SERVER['REQUEST_URI']);
        $name = explode('/', trim($path, '/'))[0];
        if ($limit === null) {
            $limit = 10;
        }
        $args = [];
        if ($limit != null) {
            $args['limit'] = $limit;
        }
        $args['method'] = $method;
        $args['page'] = $page;
        $args['sort'] = json_encode($sort);
        foreach (['limit', 'page', 'sort'] as $option) {
            $key = $name . '-' . $method . '-' . $option;
            if (isset($_GET[$key])) {
                $args[$option] = $_GET[$key];
            }
        }
        $this->view->htmlIndex($name, $args);
    }

    public function html ($slug) {
        $path = $this->language->pathEvaluate($_SERVER['REQUEST_URI']);
        $name = explode('/', trim($path, '/'))[0];
        $this->view->html($name, $slug);
    }

    public function htmlCollectionIndex () {
        $this->view->htmlCollectionIndex($this->model->collections());
    }

    public function jsonCollectionIndex () {
        $collections = $this->model->collections();
        foreach ($collections as &$collection) {
            $class = $collection['class'];
            $collectionObj = $this->collection->factory(new $class);
            $reflection = new ReflectionClass($collectionObj);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                if (in_array($method->name, ['document','__construct','totalGet','localSet','decorate','fetchAll','index','views','statsUpdate','statsSet','statsAll','toCamelCase'])) {
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
        echo $head . json_encode($collections, JSON_PRETTY_PRINT) . $tail;
    }

    public function authFilter () {
        return true;
        if ($this->person->permission(['api-all', 'api-collections', 'manager'])) {
            return true;
        }
        return 401;
    }

    public function stats ($context) {
        $this->collection->statsSet($context['dbURI']);
    }

    public function tagsCollection ($context) {
        $this->model->tagsCollection($context);
    }
}