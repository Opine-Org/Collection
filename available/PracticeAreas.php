<?php
/*
 * @version .2
 */
namespace Collection;

class PracticeAreas {
    public $publishable = false;
    public $singular = 'practice_area';

    public function indexSearch ($document) {
        return [
            'title' => $document['title'],
            'description' => $document['description'],
            'image' => null,
            'tags' => [],
            'categories' => [],
            'date' => date('c', $document['created_date']->sec)
        ];
    }
}