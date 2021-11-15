# php-weather-api
 Sample code for fetching forecast weather data from the National Weather Service using their API. It parses the results as JSON and outputs some forecast information.

This code follows, step by step, the same types of calls made by the Python version. This PHP code uses cURL to fetch the data.

Developed with PHP 7.3.24 on macOS. Tested on macOS running local Apache and PHP 7.3.24.

The NWS API information is located at: https://weather-gov.github.io/api/general-faqs

The code uses the sample lat-lon provided by the faq (the location of the Washington Monument); you can change this to any other appropriate location.

Note that once you get back the forecast URL (for the sample it's https://api.weather.gov/gridpoints/LWX/96,70/forecast) you can use this in a browser to see what the actual forecast elements are.

---

11/15/21

Added forecast-api.php, which is an API version of the code in index.php. You may use post or get to provide the lat/lon.

Also added a tester page forecast-api-test.php. Be sure to adjust the $remoteApiPath in this file to provide the API path.



