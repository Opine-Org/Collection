<?php
/**
 * Opine\Collection\Route
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

use Opine\Interfaces\Route as RouteInterface;

class Route {
    private $route;
    private $model;

    public function __construct (RouteInterface $route, $model) {
        $this->route = $route;
        $this->model = $model;
    }

    public function paths () {
        $this->route->get('/api/collection/{collection}', 'collectionController@json', ['before' => 'collectionController@authFilter']);
        $this->route->get('/api/collection/{collection}/{method}', 'collectionController@json', ['before' => 'collectionController@authFilter']);
        $this->route->get('/api/collection/{collection}/{method}/{limit}', 'collectionController@json', ['before' => 'collectionController@authFilter']);
        $this->route->get('/api/collection/{collection}/{method}/{limit}/{page}', 'collectionController@json', ['before' => 'collectionController@authFilter']);
        $this->route->get('/api/collection/{collection}/{method}/{limit}/{page}/{sort}', 'collectionController@json', ['before' => 'collectionController@authFilter']);
        $this->route->get('/api/collection/{collection}/{method}/{limit}/{page}/{sort}/{fields}', 'collectionController@json', ['before' => 'collectionController@authFilter']);

        $this->route->get('/{bundle}/api/collection/{collection}', 'collectionController@jsonBundle', ['before' => 'collectionController@authFilter']);
        $this->route->get('/{bundle}/api/collection/{collection}/{method}', 'collectionController@jsonBundle', ['before' => 'collectionController@authFilter']);
        $this->route->get('/{bundle}/api/collection/{collection}/{method}/{limit}', 'collectionController@jsonBundle', ['before' => 'collectionController@authFilter']);
        $this->route->get('/{bundle}/api/collection/{collection}/{method}/{limit}/{page}', 'collectionController@jsonBundle', ['before' => 'collectionController@authFilter']);
        $this->route->get('/{bundle}/api/collection/{collection}/{method}/{limit}/{page}/{sort}', 'collectionController@jsonBundle', ['before' => 'collectionController@authFilter']);
        $this->route->get('/{bundle}/api/collection/{collection}/{method}/{limit}/{page}/{sort}/{fields}', 'collectionController@jsonBundle', ['before' => 'collectionController@authFilter']);

        $collections = $this->model->collections();
        $routed = [];
        foreach ($collections as $collection) {
            if (isset($collection['p']) && !isset($routed[$collection['p']])) {
                $this->route->get('/' . $collection['p'], 'collectionController@htmlIndex', ['before' => 'collectionController@authFilter']);
                $this->route->get('/' . $collection['p'] . '/{method}', 'collectionController@htmlIndex', ['before' => 'collectionController@authFilter']);
                $this->route->get('/' . $collection['p'] . '/{method}/{limit}', 'collectionController@htmlIndex', ['before' => 'collectionController@authFilter']);
                $this->route->get('/' . $collection['p'] . '/{method}/{limit}/{page}', 'collectionController@htmlIndex', ['before' => 'collectionController@authFilter']);
                $this->route->get('/' . $collection['p'] . '/{method}/{limit}/{page}/{sort}', 'collectionController@htmlIndex', ['before' => 'collectionController@authFilter']);
                $routed[$collection['p']] =  true;
            }
            if (!isset($collection['s']) || isset($routed[$collection['s']])) {
                continue;
            }
            $this->route->get('/' . $collection['s'] . '/{slug}', 'collectionController@html', ['before' => 'collectionController@authFilter']);
            $this->route->get('/' . $collection['s'] . '/id/{id}', 'collectionController@html', ['before' => 'collectionController@authFilter']);
            $routed[$collection['s']] = true;
        }
        $this->route->get('/collections', 'collectionController@htmlCollectionIndex', ['before' => 'collectionController@authFilter']);

        $this->route->get('/api/collections', 'collectionController@jsonCollectionIndex', ['before' => 'collectionController@authFilter']);
    }
}