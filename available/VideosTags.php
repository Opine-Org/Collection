<?php
/*
 * @version .1
 */
namespace Collection;

class VideosTags {
    public $publishable = false;
    public $singular = 'videos_tag';
    public $path = false;

    public function document (&$document) {
        $tmp = [
            'tag' => $document['_id'],
            'count' => $document['value']
        ];
        $document = $tmp;
    }
}