<?php
namespace Collection;

use ReflectionClass, Exception;

$collections = ['Advertisements',
'AdvertisingZones',
'BlogsTags',
'BlurbsByTag',
'Blurbs',
'Books',
'BooksTags',
'Carousels',
'CarouselsReportByTag',
'Categories',
'ContactRequests',
'Departments',
'Events',
'EventsTags',
'FileUploads',
'Films',
'FundingCampaigns',
'Initiatives',
'Jobs',
'Keywords',
'Languages',
'Links',
'MembershipCampaigns',
'MembershipLevels',
'Menus',
'News',
'Notices',
'Pages',
'PagesTags',
'Partials',
'PhotoGalleries',
'PhotoGalleriesTags',
'Podcasts',
'PracticeAreas',
'Profiles',
'Programs',
'Publications',
'PublicationsTags',
'Quotes',
'Resources',
'SocialLinks',
'SocialSharing',
'Sponsors',
'SponsorsTags',
'Testimonials',
'Tweets',
'UserGroups',
'Users',
'VideoPlaylists',
'VideoSeries',
'Videos',
'VideosTags'];

foreach ($collections as $collection) {
    $name = toUnderscore($collection);
    require_once(__DIR__ . '/available/' . $collection . '.php');
    $class = 'Collection\\' . $collection;
    $obj = new $class();
    $reflect = new ReflectionClass($obj);
    $singular = $reflect->getProperty('singular')->getValue($obj);
    $publishable = false;
    try {
        $publishable = $reflect->getProperty('publishable')->getValue($obj);
    } catch (Exception $e) {}
    if ($name == 'blogs') {
        continue;
    }
    $data = 'collection:
    name: ' . $name. '
    plural_slug: ' . $name . '
    singular_slug: ' . $singular . '
    publishable: ' . ($publishable == 1 ? 'true' : 'false');
    file_put_contents(__DIR__ . '/available/' . $name . '.yml', $data);
}

function toUnderscore ($value) {
    return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $value));
}