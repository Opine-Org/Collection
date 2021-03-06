<?php
/**
 * Opine\Collection
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

use Opine\Interfaces\DB as DBInterface;

class Service
{
    private $root;
    private $model;
    private $route;
    private $db;
    private $language;
    private $person;
    private $search;

    public function __construct($root, $model, $route, DBInterface $db, $language, $person, $search)
    {
        $this->root = $root;
        $this->model = $model;
        $this->route = $route;
        $this->db = $db;
        $this->language = $language;
        $this->person = $person;
        $this->search = $search;
    }

    public function factory($slug)
    {
        $metadata = $this->model->collection($slug);
        if ($metadata === false) {
            throw new Exception('Can not read collection: '.$slug);
        }
        $collection = new Collection($metadata, $this->root, $this->route, $this->db, $this->language, $this->person, $this->search);

        return $collection;
    }

    public function generate($slug, $method = 'all', $limit = 20, $page = 1, $sort = [], $fields = [])
    {
        $value = null;
        if ($method == 'byId' || $method == 'bySlug') {
            $value = $limit;
        }
        if (substr_count($method, ':') > 0) {
            $tmp = explode(':', $method);
            $value = array_pop($tmp);
            $method = implode(':', $tmp);
        }
        if ($page < 1) {
            $page = 1;
        }
        if (empty($sort)) {
            $sort = [];
        }
        if (empty($fields)) {
            $fields = [];
        }
        $metadata = $this->model->collection($slug);
        if ($metadata === false) {
            throw new Exception('Can not load collection metadata for: '.$slug);
        }
        $collection = new Collection($metadata, $this->root, $this->route, $this->db, $this->language, $this->person, $this->search);
        $collection->queryOptionsSet($limit, $page, $sort, $method, $value);

        return $collection;
    }
}
