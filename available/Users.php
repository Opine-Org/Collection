<?php
/*
 * @version .2
 */
namespace Collection;

class Users {
    public $publishable = true;
    public $singular = 'user';

    public function indexSearch ($document) {
        return [
            'title' => $document['first_name'] . ' ' . $document['last_name'],
            ///'description' => $document['description'],
            //'image' => isset($document['image']) ? $document['image'] : '',
            //'tags' => isset($document['tags']) ? $document['tags'] : [],
            //'categories' => isset($document['categories']) ? $document['categories']: [],
            'date' => date('c', $document['created_date']->sec)
        ];
    }
}