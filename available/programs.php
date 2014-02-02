<?php
/*
 * @version .2
 * @link https://raw.github.com/Opine-Org/Collection/master/available/programs.php
 * @mode upgrade
 */
namespace Collection;

class programs {
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