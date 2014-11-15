<?php
/*
 * @version .2
 */
namespace Collection;

class MembershipLevels {
    public $publishable = true;
    public $singular = 'membership_level';

    public function indexSearch ($document) {
        return [
            'title' => $document['title'],
            'description' => $document['description'],
            'image' => [],
            'tags' => [],
            'categories' => [],
            'date' => date('c', $document['created_date']->sec)
        ];
    }
}