<?php
 	
 	error_reporting(0);

 	//Store query in variable
 	$city = $_GET['city'];
 	
 	//Replace spaces with nothing
 	$city = str_replace(" ", "", $city);
 	
 	//Get infrormation from page 
 	$contents = file_get_contents("http://www.weather-forecast.com/locations/".$city."/forecasts/latest");
 	 	
 	//Check for match that comes between 3 Day Weather Forecast Summary
 	preg_match('/3 Day Weather Forecast Summary:<\/b><span class="read-more-small"><span class="read-more-content"> <span class="phrase">(.*?)</s', $contents, $matches);
 	
 	//Output the weather
 	echo $matches[1];

 ?>	
    