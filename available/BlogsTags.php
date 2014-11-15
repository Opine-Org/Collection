<?php
/*
 * @version .1
 */
namespace Collection;

class BlogsTags {
    public $publishable = false;
    public $singular = 'blogs_tag';
    public $path = false;

    public function document (&$document) {
        $tmp = [
            'tag' => $document['_id'],
            'count' => $document['value']
        ];
        $document = $tmp;
    }
}