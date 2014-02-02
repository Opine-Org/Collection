<?php
/*
 * @version .3
 * @link https://raw.github.com/Opine-Org/Collection/master/available/jobs.php
 * @mode upgrade
 *
 * .3 indexind
 */
namespace Collection;

class jobs {
    public $publishable = true;
    public $singular = 'job';

    public function index ($document) {
        $depth = substr_count($document['dbURI'], ':');
        if ($depth > 1) {
            return false;
        }
        return [
            'title' => $document['job_title'], 
            'description' => $document['description'], 
            'image' => isset($document['image']) ? $document['image'] : '', 
            'tags' => isset($document['tags']) ? $document['tags'] : [], 
            'categories' => isset($document['categories']) ? $document['categories']: [],
            'date' => date('c', $document['created_date']->sec) 
        ];
    }
}