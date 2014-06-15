<?php
/*
 * @version .5
 * @link https://raw.github.com/Opine-Org/Collection/master/available/BlurbsByTag.php
 * @mode upgrade
 *
 * .2 reshape output
 * .3 syntax
 * .4 typo
 * .5 infinite loop
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