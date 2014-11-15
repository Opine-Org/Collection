<?php
/*
 * @version .1
 */
namespace Collection;

class PhotoGalleriesTags {
    public $publishable = false;
    public $singular = 'photo_galleries_tag';
    public $path = false;

    public function document (&$document) {
        $tmp = [
            'tag' => $document['_id'],
            'count' => $document['value']
        ];
        $document = $tmp;
    }
}