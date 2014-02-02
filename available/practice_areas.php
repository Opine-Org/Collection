<?php
/*
 * @version .2
 * @link https://raw.github.com/Opine-Org/Collection/master/available/practice_areas.php
 * @mode upgrade
 */
namespace Collection;

class practice_areas {
    public $publishable = false;
    public $singular = 'practice_area';

    public function index ($document) {
        return [
            'title' => $document['title'], 
            'description' => $document['description'], 
            'image' => null,
            'tags' => [], 
            'categories' => [], 
            'date' => date('c', $document['created_date']->sec) 
        ];
    }
}