<?php
/*
 * @version .6
 */
namespace Collection;

class Blurbs {
    public $publishable = false;
    public $singular = 'blurb';

    public function indexSearch ($document) {
        return [
            'title' => $document['title'],
            'description' => $document['body'],
            'image' => null,
            'tags' => isset($document['tags']) ? $document['tags'] : [],
            'categories' => [],
            'date' => date('c', $document['created_date']->sec)
        ];
    }

    public function tagsView ($mode, $id, $document) {
        $this->queue->add('BlurbsByTag', []);
    }
}