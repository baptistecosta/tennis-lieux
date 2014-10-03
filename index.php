<?php

function request($method, $url, array $queryParams = [], array $postParams = [])
{
    $queryString = empty($queryParams) ? '' : '?' . http_build_query($queryParams);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_URL => $url . $queryString,
        CURLOPT_POSTFIELDS => $postParams,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_HEADER => 0
    ]);
    $result = curl_exec($curl);
    if (curl_errno($curl)) {
        throw new Exception(curl_error($curl));
    }
    return $result;
}

function get($url, array $queryParams = [], array $postParams = [])
{
    return request('GET', $url, $queryParams, $postParams);
}

function searchNearbyPlace($location)
{
    echo __FUNCTION__ . "\n";
    return get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', [
        'key' => 'AIzaSyDbJurRkujBxXxNxCNDW1LJ3c9HtQJ6yY8',
        'radius' => 50000,
        'name' => 'tennis',
        'keyword' => 'tennis',
        'language' => 'fr',
        'location' => $location
    ]);
}

function searchNearbyPlaceWithToken($token)
{
    echo __FUNCTION__ . "\n";
    return get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', [
        'key' => 'AIzaSyDbJurRkujBxXxNxCNDW1LJ3c9HtQJ6yY8',
        'pagetoken' => $token
    ]);
}

function unserializeJsonResults($data)
{
    echo __FUNCTION__ . "\n";
    return json_decode($data, true);
}

function requestTennisPlaces($location, $token = null, Callable $onPlacesFound)
{
    echo __FUNCTION__ . "\n";
    if ($token) {
        $res = searchNearbyPlaceWithToken($token);
    } else {
        $res = searchNearbyPlace($location);
    }

    $data = unserializeJsonResults($res);
    if ($data['status'] != 'OK') {
        echo $data['status'] . "\n";
        return;
    }

    $onPlacesFound($data['results']);

    static $shield = 0;
    if (!empty($data['next_page_token']) && $shield++ <= 10) {
        sleep(8);
        requestTennisPlaces($location, $data['next_page_token'], $onPlacesFound);
    }
}

function onTennisPlacesFound(&$tennisPlaces, $places)
{
    echo __FUNCTION__ . "\n";
    echo 'places count: ' . count($places) . "\n";
    foreach ($places as $place) {
        $tennisPlaces[] = [
            'name' => $place['name'],
            'lat' => $place['geometry']['location']['lat'],
            'lng' => $place['geometry']['location']['lng'],
            'types' => $place['types']
        ];
    }
}

function write($filename, $string)
{
    $f = fopen($filename, 'w');
    fwrite($f, $string);
    fclose($f);
}

$tennisPlaces = [];
$location = '43.128946,5.851886';
requestTennisPlaces($location, null, function($places) use (&$tennisPlaces) {
    onTennisPlacesFound($tennisPlaces, $places);
});

echo 'tennis places count: ' . count($tennisPlaces) . "\n";

$filename = $location . '.json';
write($filename, json_encode($tennisPlaces, JSON_PRETTY_PRINT));