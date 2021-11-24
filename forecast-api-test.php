<html lang="en">
<head>
    <title>Test page for forecast-api.php</title>
    <style>
        /* Format form labels. */
        label {
            display: inline-block;
            float: left;
            clear: left;
            width: 100px;
            padding: 0px 10px;
            text-align: right;
        }

        input {
            display: inline-block;
            float: left;
        }

        #submit {
            width: 100px;
            margin-left: 120px;
        }

        table, th, td {
            border: 1px solid;
            border-spacing: 0;
        }

        table th, table td {
            padding: 5px;
        }
    </style>
</head>

<?php
// Be sure to set your forecast-api.php path here.
$remoteApiPath = "https://" . $_SERVER['HTTP_HOST'] . "/phptest/php-weather-api/forecast-api.php";

$lat = "";
$lon = "";
$latVal = "";
$lonVal = "";
$city = "";
$errorMessage = "";
$processFlag = false;

$cities = [
    "barrow" => ["Barrow, AK", 71.2905, -156.7886],
    "houston" => ["Houston, TX", 29.7499, -95.3584],
    "miami" => ["Miami, FL", 25.7616, -80.1917],
    "neworleans" => ["New Orleans, LA", 29.9546, -90.0750],
    "dc" => ["Washington, DC", 38.8894, -77.0352]
];

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_POST['method'] == 'manual') {
        if (isset($_POST['lat'])) {
            $lat = trim($_POST['lat']);
            $latVal = $lat;
        }
        if (isset($_POST['lon'])) {
            $lon = trim($_POST['lon']);
            $lonVal = $lon;
        }

        if (strlen($lat) == 0 || strlen($lon) == 0) {
            $errorMessage = "Please provide both latitude and longitude values.";
        } else {
            $processFlag = true;
        }
    } else {
        $city = trim($_POST['city']);
        $data = $cities[$city];
        $lat = $data[1];
        $lon = $data[2];
        $processFlag = true;
    }
}
?>

<body>
<p>Test page for forecast-api.php</p>

<p>Provide a US-based latitude/longitude, or select a city from the list.</p>
<br>

<?php
if (strlen($errorMessage) > 0) print("<p style=\"color: red\">$errorMessage</p>")
?>

<form method="post" action="forecast-api-test.php">
    <input type="hidden" name="method" value="manual">
    <div>
        <label>Latitude:</label>
        <input type="text" name="lat" value="<?php print $latVal; ?>" size="20">
        <br>
        <label>Longitude:</label>
        <input type="text" name="lon" value="<?php print $lonVal; ?>" size="20">
    </div>
    <br><br>
    <input type="submit" name="submit" value="Submit" id="submit">
</form>
<br><br>

<form method="post" action="forecast-api-test.php">
    <div>
        <input type="hidden" name="method" value="list">
        <label>City:</label>
        <select name="city">
            <?php
            foreach ($cities as $key => $value) {
                if ($city == $key) $SELECTED = 'SELECTED';
                else $SELECTED = '';
                print("<option value=\"$key\" $SELECTED>$value[0]</option>");
            }
            ?>
        </select>
    </div>
    <br>
    <input type="submit" name="submit" value="Submit" id="submit">
</form>
<br>

<?php
if ($processFlag == true) {
    print("<p><hr>Submitting $lat,$lon ...</p>");

    // Set up a curl resource handle;
    $ch = curl_init($remoteApiPath);

    // Tell curl you want to send a POST request.
    curl_setopt($ch, CURLOPT_POST, true);

    // Attach the POST fields.
    $postFields = [
        "lat" => "$lat",
        "lon" => "$lon"
    ];

    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);                       // POST values to send.
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);        // Browser being used
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);                    // Follow any "Location: " header that the server sends
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);                            // Max redirects to follow
    curl_setopt($ch, CURLOPT_ENCODING, "");                            // Accept encoded responses
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                    // Get string of the return value via curl_exec() instead of outputting it directly
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);                       // Automatically set Referer: field in requests where it follows Location: redirect
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);                   // Stop cURL from verifying the peer's certificate
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);                      // Seconds to wait for connection
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);                             // Seconds to wait for cURL functions to execute

    //Execute the request.
    $dataRaw = curl_exec($ch);
    $response = curl_getinfo($ch);
    curl_close($ch);

    if ($response['http_code'] != "200") {
        print("<p>API call failed with http code: " . $response['http_code'] . "</p>");
    } else {
        // Parse the data into JSON format.
        // Decode JSON data to PHP associative array.
        $dataJSON = json_decode($dataRaw, true);

        if ($dataJSON['result'] !== 'ok') {
            print("<p>The information could not be obtained: " . $dataJSON['message'] . "</p>");
        } else {
            $forecasts = $dataJSON['forecasts'];

            print("
                <table>
                <tr>
                <th align='center'>When</th>
                <th align='center'>Forecast</th>
                </tr>
            ");

            foreach ($forecasts as $key => $forecastPair) {
                foreach ($forecastPair as $when => $forecast)
                    print("<tr><td>$when</td><td>$forecast</td></tr>");
            }

            print("</table>");
        }
    }

}

?>


</body>
</html>