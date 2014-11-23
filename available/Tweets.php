<?php
/*
 * @version .5
 */
namespace Collection;

class Tweets {
    public $publishable = false;
    public $singular = 'tweet';
    public $path = false;

    public function byField ($collection, $field) {
        list ($field, $value) = explode('-', $field, 2);
        $collection->criteria[$field] = $value;

        list ($type, $query) = explode('-', $value, 2);
        $container = \Opine\container();
        if (empty($query)) {
            return $collection->all();
        }
        $container->get('twitter')->tweets($query, 10, 600, $type);
        return $collection->all();
    }

    public function document (&$document) {
        $document['text'] = \Twitter_Autolink::create()->setNoFollow(false)->autoLink($document['text']);
    }
}