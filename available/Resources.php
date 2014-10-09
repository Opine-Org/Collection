<?php
/*
 * @version .3
 * @link https://raw.github.com/Opine-Org/Collection/master/available/Resources.php
 * @mode upgrade
 */
namespace Collection;

class Resources {
    public $publishable = true;
    public $singular = 'resource';
    public function index ($document) {
        return [
            'title' => $document['title'],
            'description' => [''],
            'image' => isset($document['image']) ? $document['image'] : '', 
            'tags' => isset($document['tags']) ? $document['tags'] : [], 
            'categories' => isset($document['categories']) ? $document['categories']: [],
            'date' => date('c', $document['created_date']->sec) 
        ];
    }

    public function tagsView ($mode, $id, $document) {
        $this->queue->add('CollectionTags', ['collection' => 'resources']);
    }
}