<?php
require_once 'Phlickr/Api.php';
require_once 'Phlickr/Request.php';
require_once 'Phlickr/PhotoList.php';
require_once 'Phlickr/PhotoListIterator.php';
require_once 'Phlickr/Favorites.php';

define('FLICKR_API_KEY',    '728ccdde61aed3205c14655d0c47cb85');
define('FLICKR_API_SECRET', '8cdb9a7a50b1a52f');
define('DEFAULT_TAG_NAME',  'kosenconf');

$api = new Phlickr_Api(FLICKR_API_KEY, FLICKR_API_SECRET);
$tag = DEFAULT_TAG_NAME;
$page = 1;
if (isset($_REQUEST['tag']) ) {
    $tag = $_REQUEST['tag'];
}
if (isset($_REQUEST['page']) ) {
    $page = (int)$_REQUEST['page'];
}

$request = $api->createRequest(
    'flickr.photos.search',
    array(
        'tags' => $tag,
        'sort' => 'date-taken-desc',
        'safe_search' => '1',
    )
);

$photolist = new Phlickr_PhotoList($request, 30);
if ($page > 1) $photolist->setPage($page);
$photos = $photolist->getPhotos();

$photo_url_array = array();
foreach ($photos as $photo) {
    $weight = getWeight($api, $photo);
    $thumb_size =  ($weight == 1.0) ? Phlickr_Photo::SIZE_500PX : 
                  (($weight == 0.5) ? Phlickr_Photo::SIZE_240PX :
                                      Phlickr_Photo::SIZE_75PX );
    $photo_info = array('thumb' => $photo->buildImgUrl($thumb_size),
                        'url' => $photo->buildUrl(),
                        'weight' => $weight);
    $photo_url_array[] = $photo_info;
}

header('Content-type: application/json');
print json_encode($photo_url_array);

function getWeight($api, $photo) {
    $request = $api->createRequest(
        'flickr.photos.getFavorites',
        array(
            'photo_id' => $photo->getId(),
        )
    );

    $favorites = new Phlickr_Favorites($request);
    $count = $favorites->getTotal();
    return   ($count >= 10) ? 1.0 :
            (($count >   0) ? 0.5 : 0.0 );
}
