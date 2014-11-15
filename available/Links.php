<?php
/*
 * @version .2
 */
namespace Collection;

class Links {
    public $publishable = true;
    public $singular = 'link';

    public function indexSearch ($document) {
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