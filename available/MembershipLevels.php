<?php
/*
 * @version .2
 * @link https://raw.github.com/Opine-Org/Collection/master/available/MembershipLevels.php
 * @mode upgrade
 */
namespace Collection;

class MembershipLevels {
    public $publishable = true;
    public $singular = 'membership_level';

    public function index ($document) {
        return [
            'title' => $document['title'], 
            'description' => $document['description'], 
            'image' => [], 
            'tags' => [], 
            'categories' => [], 
            'date' => date('c', $document['created_date']->sec) 
        ];
    }
}