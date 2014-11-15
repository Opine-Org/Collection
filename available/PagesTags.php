<?php
/*
 * @version .1
 */
namespace Collection;

class PagesTags {
    public $publishable = false;
    public $singular = 'pages_tag';
    public $path = false;

    public function document (&$document) {
        $tmp = [
            'tag' => $document['_id'],
            'count' => $document['value']
        ];
        $document = $tmp;
    }
}