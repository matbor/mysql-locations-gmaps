<?php
// by Matthew Bordignon 2015 @bordignon on twitter
//
// Use at your own risk, I run this on my private server and not on a public servcie, so not sure how good the code is against
// mysql injection. I am happy to take pull requests that fix any  problems.

//location of the database connection information, mysqlserver, username, password
$path = $_SERVER['DOCUMENT_ROOT'];
$path .= "dbinfo.php";
require($path);

$con = mysqli_connect($mysqlserver, $username, $password);

if (!$con) {
    die('Could not connect: ' . mysqli_error($con));
}
mysqli_select_db($con, $database);

//url options ie; locations.php?interval=30&user=matbor if none is specified all data is returned.
$interval = intval($_GET['interval']);
$user = $_GET['user'];
?>

<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<meta name=viewport content="user-scalable=no,width=device-width" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<meta charset="utf-8">

<title>Owntracks Locations on Google Maps</title>

	<style>
	      html, body, #map {
	        margin: 0;
	        padding: 0;
	        height: 99%;
	      }
	      #legend {
	        font-family: Arial, sans-serif;
	        background: #fff;
	        padding: 5px;
	        margin: 5px;
	        border: 1px solid #000;
	      }
	      #legend h3 {
	        margin-top: 0;
	      }
	      #legend img {
	        vertical-align: middle;
	      }
	</style>

<script src="http://maps.google.com/maps/api/js?key=xxxxxxxxxxxxxxxxxxxxxxxxxxxxx&sensor=false" type="text/javascript"></script>

<script type="text/javascript">
	//Borrowed the original code from http://stackoverflow.com/questions/15633604/php-mysql-google-map but now highly modifed for my use case

	var center = null;
	var map = null;
	var currentPopup;
	var bounds = new google.maps.LatLngBounds();
	var personArray = new Array();
	var pinColourArray = new Array('red','blue','yellow','pink','green','lightblue','orange','purple','red-dot','blue-dot','yellow-dot','pink-dot','green-dot','lightblue-dot','orange-dot','purple-dot');
	

	function addMarker(lat, lng, person, info) {
		var pinColour = 'blue' //default colour of pin
		
		//check the array if the person is already added to the array, if not add it.
		if (personArray.indexOf(person) < 0){

			personArray.push(person); //add person to the array
			// add the new person to the legend on the map
			pinColour = pinColourArray[personArray.indexOf(person)];
			var div = document.createElement('div');
			div.innerHTML = '<img src="http://maps.google.com/mapfiles/ms/micons/' + pinColour + '.png"> ' + person;
			legend.appendChild(div);
			
		}

		//check the pincolour for the person
		pinColour = pinColourArray[personArray.indexOf(person)];

		//assign the marker colour
		var icon = new google.maps.MarkerImage("http://maps.google.com/mapfiles/ms/micons/" + pinColour +".png",
			   new google.maps.Size(32, 32), new google.maps.Point(0, 0),
			   new google.maps.Point(16, 32));
		
		var pt = new google.maps.LatLng(lat, lng);
		bounds.extend(pt);
		var marker = new google.maps.Marker({
			position: pt,
			icon: icon,
			//animation: google.maps.Animation.DROP,
			map: map
		});
		var popup = new google.maps.InfoWindow({
			content: info,
			maxWidth: 300
		});
		google.maps.event.addListener(marker, "click", function() {
			if (currentPopup != null) {
				currentPopup.close();
				currentPopup = null;
			}
			popup.open(map, marker);
			currentPopup = popup;
		});
		google.maps.event.addListener(popup, "closeclick", function() {
			//map.panTo(center);
			currentPopup = null;
		});
	}   


	function initMap() {
		map = new google.maps.Map(document.getElementById("map"), {
			center: new google.maps.LatLng(0, 0),
			zoom: 14,
			mapTypeId: google.maps.MapTypeId.ROADMAP,
			mapTypeControl: true,
			mapTypeControlOptions: {
				style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR
			},
			navigationControl: true,
			navigationControlOptions: {
				style: google.maps.NavigationControlStyle.ZOOM_PAN
			}
		});
		
        var legend = document.getElementById('legend');

    	map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(legend);

        
		<?php
		//connection info at the top of the page
		
		// my mysql database has the following fields all data is obtaining using owntracks.org apps and mqtt broker
		// lat - latitude (VARCHAR)
		// lon - longitude (VARCHAR)
		// username - (VARCHAR)
		// device - (VARCHAR)
		// tstLocal - (DATETIME)
		
		//queries
		if (empty($user) && $interval > 0)
		{ // if username has not been spedified and a interval has
			$sql = "SELECT tstLocal, lat, lon, username, device FROM  owntracks WHERE FROM_UNIXTIME(tst) BETWEEN DATE_SUB(NOW(),INTERVAL '".$interval."' DAY) AND NOW()";	
		} 
		else if (!empty($user) && $interval > 0)
		{ // if username is specified and a interval has as well
			$sql = "SELECT tstLocal, lat, lon, username, device FROM  owntracks WHERE FROM_UNIXTIME(tst) BETWEEN DATE_SUB(NOW(),INTERVAL '".$interval."' DAY) AND NOW() AND username = '".$user."'";	
		}
		else if(!empty($user) && $interval = 0)
		{ // display all records for that usename and 0 as interval -> 0 means ALL records
			$sql = "SELECT tstLocal, lat, lon, username, device FROM  owntracks WHERE username = '".$user."'";	
		} 
		else
		{ // display all records
			$sql = "SELECT tstLocal, lat, lon, username, device FROM  owntracks ";	
		}



		$result = mysqli_query($con,$sql);


		while($row = mysqli_fetch_array($result))
		{
		  $time = $row['tstLocal'];

		  $lat = $row['lat'];
		  $lon = $row['lon'];

		  $device = $row['device'];
		  $person = $row['username'];

		  //$full = $row['full'];  //enabling this causes the table to be too large and slow to display when you have a large interval ie. > 200 days --> need to enable it in the select querry
		  //echo("addMarker($lat, $lon, '$person','<b>$dateInLocal </b><br />$person - $device<br/>$full');\n");  //enabling this with "$FULL" causes the table to be too large and slow to display when you have a large interval ie. > 200 days
		
		  // addMarker(lat, lng, person, info)
		  // lat - latitude (VARCHAR)
		  // lon - longitude	
		  // person - used to group the markers in different colours
		  // info - html, marker popup when clicked so basically text
		  echo("addMarker($lat, $lon, '$person','<b>$time </b><br />$person - $device<br/>');\n");

		}

		?>

		center = bounds.getCenter();
		map.fitBounds(bounds);

 	}	
 </script>
 
 </head>

 <body onload="initMap()" style="margin:0px; border:0px; padding:0px;">
 <div id="map"></div>
 <div id="legend"><h3>User/Device</h3></div>
 </body>
 
 </html>
