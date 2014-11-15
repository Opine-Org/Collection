<?php
/*
 * @version .4
 */
namespace Collection;

class Carousels {
    public $publishable = false;
    public $singular = 'carousel';

    public function indexSearch ($document) {
        $depth = substr_count($document['dbURI'], ':');
        if ($depth > 1) {
            return false;
        }
        return [
            'title' => $document['title'],
            'description' => $document['description'],
            'image' => '',
            'tags' => isset($document['tags']) ? $document['tags'] : [],
            'categories' => [],
            'date' => date('c', $document['created_date']->sec)
        ];
    }
}