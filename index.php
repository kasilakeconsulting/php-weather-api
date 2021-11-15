<?php
/**
Sample code for fetching forecast weather data from the National Weather Service using their API.
It parses the results as JSON and outputs some forecast information.

References:
https://weather-gov.github.io/api/general-faqs
*/
?>

<html lang="en">
<head>
<title>Sample weather forecast fetch</title>
</head>

<body>
<p>
Sample weather forecast fetch
</p>

<?php
function curlFetch($requestURL)
{
    // Set up a curl resource handle;
    $ch = curl_init();

    // The following curl settings work for this site, other sites may require tweaking.
    // See: https://www.php.net/manual/en/function.curl-setopt.php

    curl_setopt( $ch, CURLOPT_URL, $requestURL);
    curl_setopt( $ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);        // Browser being used
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

// Retrieve the metadata for your location with:  https://api.weather.gov/points/{lat},{lon}

// Sample from faq
$latLon = '38.8894,-77.0352';
$requestURL = "https://api.weather.gov/points/" . $latLon;

print("<p>Requesting metadata... $requestURL</p>");

list($dataRaw,$response) = curlFetch($requestURL);
print("Response code: " . $response['http_code']);

if ($response['http_code'] != "200")
{
    print("<p>API call failed: " . $response['http_code'] . "</p>");
} else {
    print("<p>Response text:</p><ul>$dataRaw</ul>");

    // Parse the data into JSON format.
    // Decode JSON data to PHP associative array.
    $dataJSON = json_decode($dataRaw, true);
    print("<p>JSON text:</p><ul>");
    // Breaking up the <ul> for some reason makes the indent work correctly with var_dump.
    print(var_dump($dataJSON)."</ul>");

    // Get the URL for the general forecast
    $requestURL = $dataJSON['properties']['forecast'];

    print("<p>Forecast URL: $requestURL</p>");

    print("<p>Requesting forecast data...</p>");

    list($dataRaw,$response) = curlFetch($requestURL);

    if ($response['http_code'] != "200")
    {
        print("<p>API call failed:  " . $response['http_code'] . "</p>");
    } else {
        print("<p>Response text:</p><ul>$dataRaw</ul>");

        // Parse the data into JSON format.
        // Decode JSON data to PHP associative array.
        $dataJSON = json_decode($dataRaw, true);
        print("<p>JSON text:</p><ul>");
        // Breaking up the <ul> for some reason makes the indent work correctly with var_dump.
        print(var_dump($dataJSON)."</ul>");

        // Roll through the periods, output detailedForecast for each

        print("<p>Forecasts:</p>");

        foreach ($dataJSON['properties']['periods'] as $key=>$valueArray)
        {
            print($valueArray['name'] . ': ' . $valueArray['detailedForecast'] . "<br>");
        }

        print("<p>Done!</p>");
    }
}



?>

</body>
</html>