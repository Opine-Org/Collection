<?php
/*
 * @version .2
 * @link https://raw.github.com/Opine-Org/Collection/master/available/FileUploads.php
 * @mode upgrade
 */
namespace Collection;

class FileUploads {
    public $publishable = true;
    public $singular = 'book';

    public function indexSearch ($document) {
        return [
            'title' => $document['title'],
            'description' => '',
            'image' => isset($document['image']) ? $document['image'] : '',
            'tags' => [],
            'categories' => [],
            'date' => date('c', $document['created_date']->sec)
        ];
    }
}