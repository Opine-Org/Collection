<?php
/*
 * @version .2
 * @link https://raw.github.com/Opine-Org/Collection/master/available/social_links.php
 * @mode upgrade
 */
namespace Collection;

class social_links {
    public $publishable = false;
    public $singular = 'social_link';

    public function index ($document) {
        return [
            'title' => [], 
            'description' => [], 
            'image' => null, 
            'tags' => [], 
            'categories' => [], 
            'date' => date('c', $document['created_date']->sec) 
        ];
    }
}