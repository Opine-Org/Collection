<?php
/*
 * @version .3
 */
namespace Collection;

class Categories {
    public $publishable = false;
    public $singular = 'category';

    public function indexSearch ($document) {
        $depth = substr_count($document['dbURI'], ':');
        if ($depth > 1) {
            return false;
        }
        return [
            'title' => $document['title'],
            'description' => '',
            'image' => isset($document['image']) ? $document['image'] : '',
            'tags' => [],
            'categories' => [],
            'date' => date('c', $document['created_date']->sec)
        ];
    }
}