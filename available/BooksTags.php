<?php
/*
 * @version .1
 */
namespace Collection;

class BooksTags {
    public $publishable = false;
    public $singular = 'books_tag';
    public $path = false;

    public function document (&$document) {
        $tmp = [
            'tag' => $document['_id'],
            'count' => $document['value']
        ];
        $document = $tmp;
    }
}