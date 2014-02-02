<?php
/*
 * @version .3
 * @link https://raw.github.com/Opine-Org/Collection/master/available/departments.php
 * @mode upgrade
 */
namespace Collection;

class departments {
    public $publishable = true;
    public $singular = 'event';

    public function index ($document) {
        return [
            'title' => $document['title'], 
            'description' => $document['description'], 
            'image' => isset($document['image']) ? $document['image'] : '', 
            'tags' => isset($document['tags']) ? $document['tags'] : [], 
            'categories' => isset($document['categories']) ? $document['categories']: [],
            'date' => date('c', $document['created_date']->sec) 
        ];
    }
}