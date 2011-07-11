<?php
require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Service_Flickr');

define('FLICKR_API_KEY',    '728ccdde61aed3205c14655d0c47cb85');
define('FLICKR_API_SECRET', '8cdb9a7a50b1a52f');
define('DEFAULT_TAG_NAME',  'kosenconf');

$tag = DEFAULT_TAG_NAME;
if (isset($_REQUEST['tag']) ) {
    $tag = $_REQUEST['tag'];
}
$page = 1;
if (isset($_REQUEST['page']) ) {
    $page = (int)$_REQUEST['page'];
}

$flickr = new Zend_Service_Flickr(FLICKR_API_KEY);
$results = $flickr->tagSearch(
    $tag,
    array(
        'per_page' => 20,
        'sort' => 'date-taken-desc',
        'safe_search' => '1',
    )
);

$photo_url_array = array();
foreach ($results as $result) {
    $weight = getWeight($flickr, $result->id);
	$thumb = null;
	if ($weight == 1.0) {
	    $thumb = $result->Medium;
	} else if ($weight == 0.5) {
	    $thumb = $result->Small;
	} else {
	    $thumb = $result->Square;
	}
	$url = Zend_Service_Flickr::URI_BASE . '/photos/' . $result->owner . '/' . $result->id;
    $photo_info = array(
	    'thumb'  => $thumb->uri,
		'height' => $thumb->height,
		'width'  => $thumb->width,
        'url'    => $url,
        'weight' => $weight
    );
    //var_dump($photo_info);

    $photo_url_array[] = $photo_info;
}

header('Content-type: application/json');
print json_encode($photo_url_array);

function getWeight($flickr, $id) {
    static $method = 'flickr.photos.getFavorites';

    if (empty($id)) {
        /**
         * @see Zend_Service_Exception
         */
        require_once 'Zend/Service/Exception.php';
        throw new Zend_Service_Exception('You must supply a photo ID');
    }

    $options = array('api_key' => $flickr->apiKey, 'method' => $method, 'photo_id' => $id);

    $restClient = $flickr->getRestClient();
    $restClient->getHttpClient()->resetParameters();
    $response = $restClient->restGet('/services/rest/', $options);
	//var_dump($response->getBody());

    $dom = new DOMDocument();
    $dom->loadXML($response->getBody());
	//todo check
    $xpath = new DOMXPath($dom);
    $count = (int) $xpath->query('//photo')->item(0)->getAttribute('total');
    return   ($count >= 10) ? 1.0 :
            (($count >   0) ? 0.5 : 0.0 );
}
