<!doctype html>
<html>
<head>
<title>Weather Predictor</title>
<meta charset="utf-8" />
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/
bootstrap.min.css">
<!-- Optional theme -->
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/
bootstrap-theme.min.css">
<link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
<style>

html, body {
height:100%;
font-family: 'Open Sans', sans-serif;
}

.container {
 background-image:url("background.jpg");
 width:100%;
 height:100%;
 background-size:cover;
 background-position:center;
 padding-top:100px;
}

.center {
 text-align:center;
}

.white {
color:white;
}

#title{
font-size: 75px;
font-weight: 900;	
}

p {
 padding-top:15px;
 padding-bottom:15px;
}

button {
 margin-top:20px;
 margin-bottom:20px;
}
.alert {
 margin-top:20px;
display:none;
}
</style>
</head>
<body>

<div class="container">
   <div class="row">
   
    <!-- Title and Information-->
    <div class="col-md-6 col-md-offset-3 center">
       <h1 id="title" class="center white">Weather Predictor</h1>
       <p class="lead center white">Enter your city below to get a quick forecast for the
       weather.</p>
    
    <!--Input Form-->
    <form>
       <div class="form-group">
        <input type="text" class="form-control" name="city" id="city" placeholder="Eg. 
        New York, Chicago, San Francisco..." /></div>
        <button id="findMyWeather" class="btn btn-success btn-lg">Find My Weather</button>
    </form>
    
    <!-- Corresponding alerts-->
    <div id="success" class="alert alert-success">Success!</div>
    <div id="fail" class="alert alert-danger">Sorry, could not find weather data for that
    city. Please try a different city.</div>
    <div id="noCity" class="alert alert-danger">Please enter a city!</div>
  
  </div>
 </div>
</div>

<!--JQuery-->
<script src="//code.jquery.com/jquery-1.11.0.min.js"></script>
<!-- Latest compiled and minified JavaScript -->
<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>

<script>

// Start weather search function
$("#findMyWeather").click(function(event) {

  event.preventDefault();

  $(".alert").hide();

  //Check if city is inputted
  if ($("#city").val()!="") {

     // Insert search query into scraper.php
     $.get("scraper.php?city="+$("#city").val(), function( data ) {
           
           // If no data is found for that city, output fail alert
           if (data.length<10) {
           $("#fail").fadeIn();          
           } 

           // If data is found, output data from scraper.php
           else {
           $("#success").html(data).fadeIn();
           }
     });
  } 

   //Output no city alert if no city is inputted
  else {
  $("#noCity").fadeIn();
  }

 });
</script>
</body>
</html>