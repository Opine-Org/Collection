<?php
/*
 * @version .2
 * @link https://raw.github.com/Opine-Org/Collection/master/available/Programs.php
 * @mode upgrade
 */
namespace Collection;

class Programs {
    public $publishable = true;
    public $singular = 'program';

    public function index ($document) {
        return [
            'title' => $document['title'], 
            'description' => $document['description'], 
            'image' => isset($document['image']) ? $document['image'] : '', 
            'tags' => isset($document['tags']) ? $document['tags'] : [], 
            'categories' => [], 
            'date' => date('c', $document['created_date']->sec) 
        ];
    }
}