<?php
/*
 * @version .5
 */
namespace Collection;

class PhotoGalleries {
    public $publishable = true;
    public $singular = 'photo_gallery';

    public function indexSearch ($document) {
        $depth = substr_count($document['dbURI'], ':');
        if ($depth > 1) {
            return false;
        }
        return [
            'title' => $document['title'], 
            'description' => $document['description'], 
            'image' => isset($document['image']) ? $document['image'] : '',
            'tags' => [], 
            'categories' => [],
            'date' => date('c', $document['created_date']->sec) 
        ];
    }

    public function tagsView ($mode, $id, $document) {
        $this->queue->add('CollectionTags', ['collection' => 'photo_galleries']);
    }
}