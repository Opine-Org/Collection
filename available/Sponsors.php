<?php
/*
 * @version .2
 */
namespace Collection;

class Sponsors {
    public $publishable = true;
    public $singular = 'sponsor';

    public function indexSearch ($document) {
        return [
            'title' => $document['title'],
            'description' => $document['description'],
            'image' => isset($document['image']) ? $document['image'] : '',
            'tags' => [],
            'categories' => isset($document['categories']) ? $document['categories']: [],
            'date' => date('c', $document['created_date']->sec)
        ];
    }
}