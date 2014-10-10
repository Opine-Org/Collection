<?php
/*
 * @version .5
 * @link https://raw.github.com/Opine-Org/Collection/master/available/Books.php
 * @mode upgrade
 *
 * .5 rename
 */
namespace Collection;

class Books {
    public $publishable = true;
    public $singular = 'book';

    public function index ($document) {
        return [
            'title' => $document['title'], 
            'description' => $document['description'], 
            'image' => isset($document['image']) ? $document['image'] : '', 
            'tags' => isset($document['tags']) ? $document['tags'] : [], 
            'categories' => isset($document['categories']) ? $document['categories']: [], 
            'date' => date('c', $document['created_date']->sec) 
        ];
    }

    public function tagsView ($mode, $id, $document) {
        $this->queue->add('CollectionTags', ['collection' => 'books']);
    }
}