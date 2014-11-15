<?php
/*
 * @version .1
 */
namespace Collection;

class EventsTags {
    public $publishable = false;
    public $singular = 'events_tag';
    public $path = false;

    public function document (&$document) {
        $tmp = [
            'tag' => $document['_id'],
            'count' => $document['value']
        ];
        $document = $tmp;
    }
}