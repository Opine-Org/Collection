<?php
/*
 * @version .3
 */
namespace Collection;

class Menus {
    public $publishable = false;
    public $singular = 'menu';

    public function indexSearch ($document) {
        $depth = substr_count($document['dbURI'], ':');
        if ($depth > 1) {
            return false;
        }
        return [
            'title' => $document['label'],
            'description' => '',
            'image' => null,
            'tags' => [],
            'categories' => [],
            'date' => date('c', $document['created_date']->sec)
        ];
    }
}