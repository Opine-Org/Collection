<?php
/*
 * @version .4
 * @link https://raw.github.com/Opine-Org/Collection/master/available/videos.php
 * @mode upgrade
 *
 * .3 add youtube image
 * .4 tag view
 */
namespace Collection;
use UrlId\UrlId;

class videos {
    public $publishable = true;
    public $singular = 'video';

    public function document (&$document) {
        $document['video_id'] = null;
        $document['video_type'] = null;
        if (!empty($document['video'])) {
            $document['video_id'] = UrlId::parse($document['video'], $document['video_type']);
        }
        if ($document['video_type'] == 'youtube') {
            $document['image'] = ['url' => 'http://img.youtube.com/vi/' . $document['video_id'] . '/0.jpg'];
        }
        if ($document['video_type'] == 'vimeo') {
            
        }
        $document['category_titles'] = [];
        if (isset($document['categories']) && is_array($document['categories'])) {
            foreach ($document['categories'] as $id) {
                $category = $this->db->collection('categories')->findOne(['_id' => $this->db->id($id)], ['title']);
                if (!isset($category['_id'])) {
                    continue;
                }
                $document['category_titles'][] = $category['title'];
            }
        }
    }

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
        $this->queue->add('CollectionTags', ['collection' => 'videos']);
    }
}