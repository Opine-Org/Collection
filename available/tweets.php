<?php
/*
 * @version .5
 * @link https://raw.github.com/Opine-Org/Collection/master/available/tweets.php
 * @mode upgrade
 *
 *
 *  Use like this:  /json-data/tweets/byField-key-TYPE-QUERY/1/1/{"created_date":-1}
 *  where type is either "user" or "search" 
 * 
 * .1 initial load
 * .2 typo
 * .3 missing logic
 * .4 warm cache
 * .5 add html links
 */
namespace Collection;

class tweets {
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
        $container->twitter->tweets($query, 10, 600, $type);
        return $collection->all();
    }

    public function document (&$document) {
        $document['text'] = \Twitter_Autolink::create()->setNoFollow(false)->autoLink($document['text']);
    }
}