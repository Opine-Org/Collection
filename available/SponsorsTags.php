<?php
/*
 * @version .1
 */
namespace Collection;

class SponsorsTags {
    public $publishable = false;
    public $singular = 'sponsors_tag';
    public $path = false;

    public function document (&$document) {
        $tmp = [
            'tag' => $document['_id'],
            'count' => $document['value']
        ];
        $document = $tmp;
    }
}