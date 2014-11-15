<?php
/*
 * @version .5
 */
namespace Collection;

class BlurbsByTag {
    public $publishable = false;
    public $singular = 'blurb';
    public $path = false;

    public function all ($collection) {
        $documents = $collection->fetch();
        $newDocs = [];
        foreach ($documents as $document) {
            $newDocs[$document['_id']] = $document['value'];
        }

        return $newDocs;
    }
}