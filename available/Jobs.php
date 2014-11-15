<?php
/*
 * @version .3
 */
namespace Collection;

class Jobs {
    public $publishable = true;
    public $singular = 'job';

    public function indexSearch ($document) {
        $depth = substr_count($document['dbURI'], ':');
        if ($depth > 1) {
            return false;
        }
        return [
            'title' => $document['job_title'],
            'description' => $document['description'],
            'image' => isset($document['image']) ? $document['image'] : '',
            'tags' => isset($document['tags']) ? $document['tags'] : [],
            'categories' => isset($document['categories']) ? $document['categories']: [],
            'date' => date('c', $document['created_date']->sec)
        ];
    }
}