<?php
/*
 * @version .1
 * @link https://raw.github.com/Opine-Org/Collection/master/available/Partials.php
 * @mode upgrade
 *
 */
namespace Collection;

class Partials {
    public $publishable = false;
    public $singular = 'partial';

    public function index ($document) {
        return [
            'title' => $document['title'], 
            'description' => $document['body'],
            'image' => null,
            'tags' => [],
            'categories' => [],
            'date' => date('c', $document['created_date']->sec),
            'acl' => ['manager', 'superadmin', 'manager-content-partials']
        ];
    }
}