<?php
/*
 * @version .2
 */
namespace Collection;

class Programs {
    public $publishable = true;
    public $singular = 'program';

    public function indexSearch ($document) {
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