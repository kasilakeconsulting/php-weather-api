<?php
/**
 * Provide a lat/lon pair via post or get, and the weather forecast from the National Weather Service will be returned as JSON.
 *
 * Successful fetch JSON:
 *   'result' => 'ok',
 *   'forecasts' => $forecasts
 *
 * Unsuccessful fetch JSON:
 *
 *   'result' => 'error',
 *   'message' => $message
 *
 * Sample forecast return:
 *   {
 *   "result":"ok",
 *   "forecasts":[
 *   {"This Afternoon":"Sunny. High near 55, with temperatures falling to around 46 in the afternoon. West wind 13 to 16 mph, with gusts as high as 23 mph."},
 *   {"Tonight":"Clear, with a low around 34. West wind 3 to 9 mph."},
 *   {"Sunday":"Cloudy, with a high near 51. Southwest wind 3 to 8 mph."},
 *   {"Sunday Night":"Mostly cloudy, with a low around 39. Southwest wind around 7 mph."},
 *   {"Monday":"Mostly sunny, with a high near 51. West wind 7 to 16 mph, with gusts as high as 24 mph."},
 *   {"Monday Night":"Mostly clear, with a low around 33."},
 *   {"Tuesday":"Sunny, with a high near 54."},
 *   {"Tuesday Night":"Mostly clear, with a low around 43."},
 *   {"Wednesday":"Mostly sunny, with a high near 67."},
 *   {"Wednesday Night":"Mostly cloudy, with a low around 50."},
 *   {"Thursday":"A chance of rain showers after 7am. Partly sunny, with a high near 63. Chance of precipitation is 30%."},
 *   {"Thursday Night":"A chance of rain showers. Partly cloudy, with a low around 38. Chance of precipitation is 30%."},
 *   {"Friday":"A slight chance of rain showers before 7am. Mostly sunny, with a high near 53."},
 *   {"Friday Night":"Mostly cloudy, with a low around 37."}
 *   ]
 *   }
 *
 *
 * USDA weather API information:
 * https://weather-gov.github.io/api/general-faqs
 */


//-----------------------------------------------------
// FUNCTIONS
//-----------------------------------------------------


/**
 * Return an error message as JSON and end the program.
 *
 * @param $message
 */
function returnError($message)
{
    $message = [
        'result' => 'error',
        'message' => $message
    ];

    print json_encode($message);
    exit;
}

/**
 * @param $requestURL
 * @return array
 */
function curlFetch($requestURL)
{
    // Set up a curl resource handle;
    $ch = curl_init();

    // The following curl settings work for this site, other sites may require tweaking.
    // See: https://www.php.net/manual/en/function.curl-setopt.php

    curl_setopt( $ch, CURLOPT_URL, $requestURL);
    curl_setopt( $ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);  // Browser being used
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true);                    // Follow any "Location: " header that the server sends
    curl_setopt( $ch, CURLOPT_MAXREDIRS, 3);                            // Max redirects to follow
    curl_setopt( $ch, CURLOPT_ENCODING, "");                            // Accept encoded responses
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);                    // Get string of the return value via curl_exec() instead of outputting it directly
    curl_setopt( $ch, CURLOPT_AUTOREFERER, true);                       // Automatically set Referer: field in requests where it follows Location: redirect
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);                   // Stop cURL from verifying the peer's certificate
    curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5);                       // Seconds to wait for connection
    curl_setopt( $ch, CURLOPT_TIMEOUT, 5);                              // Seconds to wait for cURL functions to execute

    $dataRaw = curl_exec( $ch);
    $response = curl_getinfo( $ch);
    curl_close ($ch);

    return array($dataRaw,$response);
}


//-----------------------------------------------------
// MAIN
//-----------------------------------------------------

// Retrieves the metadata for the location with:  https://api.weather.gov/points/{lat},{lon}

$lat = "";
$lon = "";
$forecasts  = array();

if (!isset($_SERVER['REQUEST_METHOD'])) returnError("Post or get request is required");

if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
    if (isset($_POST['lat']) && isset($_POST['lon']))
    {
        $lat = $_POST['lat'];
        $lon = $_POST['lon'];
    }
} else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['lat']) && isset($_GET['lon'])) {
        $lat = $_GET['lat'];
        $lon = $_GET['lon'];
    }
} else {
    returnError("Post or get request expected, type used: " . $_SERVER['REQUEST_METHOD']);
}

if (strlen($lat) == 0 || strlen($lon) == 0) returnError("Both lat and lon must be provided");

$latLon = "$lat,$lon";
$requestURL = "https://api.weather.gov/points/" . $latLon;

// Requesting metadata.

list($dataRaw,$response) = curlFetch($requestURL);

if ($response['http_code'] != "200") {
    returnError("Weather API metadata call failed: " . $response['http_code']);
} else {

    // Parse the data into JSON format.
    // Decode JSON data to PHP associative array.
    $dataJSON = json_decode($dataRaw, true);

    // Get the URL for the general forecast
    $requestURL = $dataJSON['properties']['forecast'];

    // Requesting forecast data.
    list($dataRaw,$response) = curlFetch($requestURL);

    if ($response['http_code'] != "200")
    {
        returnError("Weather API forecast call failed: " . $response['http_code']);
    } else {

        // Parse the data into JSON format.
        // Decode JSON data to PHP associative array.
        $dataJSON = json_decode($dataRaw, true);

        $i = 0;

        foreach ($dataJSON['properties']['periods'] as $key=>$valueArray)
        {
            $forecasts[$i++] = array($valueArray['name'] => $valueArray['detailedForecast']);
        }
    }

    // Done.

    $message = [
        'result' => 'ok',
        'forecasts' => $forecasts
    ];

    print json_encode($message);
}
?>