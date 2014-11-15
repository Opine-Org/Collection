<?php
/*
 * @version .1
 */
namespace Collection;

class PublicationsTags {
    public $publishable = false;
    public $singular = 'publications_tag';
    public $path = false;

    public function document (&$document) {
        $tmp = [
            'tag' => $document['_id'],
            'count' => $document['value']
        ];
        $document = $tmp;
    }
}