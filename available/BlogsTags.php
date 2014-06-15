<?php
/*
 * @version .1
 * @link https://raw.github.com/Opine-Org/Collection/master/available/BlogsTags.php
 * @mode upgrade
 *
 * .1 initial load
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