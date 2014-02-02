<?php
/*
 * @version .1
 * @link https://raw.github.com/Opine-Org/Collection/master/available/events_tags.php
 * @mode upgrade
 *
 * .1 initial load
 */
namespace Collection;

class events_tags {
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