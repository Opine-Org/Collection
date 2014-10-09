<?php
/*
 * @version .2
 * @link https://raw.github.com/Opine-Org/Collection/master/available/Sponsors.php
 * @mode upgrade
 */
namespace Collection;

class Sponsors {
    public $publishable = true;
    public $singular = 'sponsor';

    public function index ($document) {
        return [
            'title' => $document['title'], 
            'description' => $document['description'], 
            'image' => isset($document['image']) ? $document['image'] : '',
            'tags' => [], 
            'categories' => isset($document['categories']) ? $document['categories']: [],
            'date' => date('c', $document['created_date']->sec) 
        ];
    }
}