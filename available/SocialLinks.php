<?php
/*
 * @version .2
 */
namespace Collection;

class SocialLinks {
    public $publishable = false;
    public $singular = 'social_link';

    public function indexSearch ($document) {
        return [
            'title'       => [],
            'description' => [],
            'image'       => null,
            'tags'        => [],
            'categories'  => [],
            'date'        => date('c', $document['created_date']->sec)
        ];
    }
}