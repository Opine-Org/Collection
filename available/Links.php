<?php
/*
 * @version .2
 * @link https://raw.github.com/Opine-Org/Collection/master/available/Links.php
 * @mode upgrade
 */
namespace Collection;

class Links {
    public $publishable = true;
    public $singular = 'link';

    public function index ($document) {
        return [
            'title' => $document['title'], 
            'description' => $document['description'], 
            'image' => isset($document['image']) ? $document['image'] : '', 
            'tags' => [], 
            'categories' => [], 
            'date' => date('c', $document['created_date']->sec) 
        ];
    }
}