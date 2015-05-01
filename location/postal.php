<html>
<head>
    <title>Where am I?</title> 
    <meta charset="utf-8" />
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css" rel="stylesheet">
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>
    <script src="//code.jquery.com/jquery-1.11.2.min.js" type="text/javascript"></script>
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
    <script src="http://maps.googleapis.com/maps/api/js?key=AIzaSyCuQ3EBOM5Vl3x08FzHgzXCTPg5_4ZQaAs"></script>

   
    <style type="text/css">
     
      body, html {
        height: 100%;
        color: white;
        font-family: 'Open Sans', sans-serif;
      }
 
      .container {
        background-image: url('postalBackground.jpg');
        background-size: cover;
        background-repeat: no-repeat;
        background-position: center;
        height: 100%;
        width: 100%;
        padding-top:100px;
      }
          .center
          {
           text-align:center;
          }

          #title{
          font-size: 65px;
          font-weight: 900; 
          margin-top: -25px;
          }
 
      #findbutton {
       margin: 20px 0px 50px 0px;
      }

      #map-canvas{
        height:490px;
        margin-left: 25px;
        margin-top: -18px;
      }
 
      .lead {
        padding: 30px 0px 30px 0px;
      }
      p
          {
                  padding-top:15px;
                  padding-bottom:15px;
          }
      button
          {
                  margin-top:20px;
                  margin-bottom:20px;
          }
    .whitebackground
        {
                background-color:white;
            border-radius:20px;
                padding:20px;
        }
    .alert
        {
                margin-top:20px;
                display:none;
        }


 
 
    </style>    
 
</head>
 
<body>
 
  <div class="container">
    <div class="row">
      <div class="col-md-4 col-md-offset-1 center">
        
        <!--Application Header-->
        <h1 id="title" class="center">Where am I?</h1>
        <p class="lead center">Enter any address to <br>find the location and postcode</p>
        
        <!--Form to enter zipcode input-->
        <form>
          <div class="form-group">
            <input type="text" name="address" class="form-control" id="address" placeholder="E.g. 63 Fake Street, Faketown.." />
          </div>
            <button class="btn btn-success btn-lg" id="findbutton">Find My Location</button>
        </form>
 
        <!-- Alert User data or failure-->
        <div id="success" class ="alert alert-success">Success!</div>
        <div id="fail" class ="alert alert-danger">Could not find the postcode for that address. please try again.
                </div>
        <div id="fail2" class ="alert alert-danger">Please enter an address</div>
      </div>
      
      <!--Map--> 
      <div class="col-md-6 center " id="map-canvas"> </div>
    
    </div>
   </div>
  </div>
 
  <script>
    
    $('#findbutton').click(function(event) {
            
        $(".alert").hide();
       
        event.preventDefault();
        var result = 0;        // check if data is successfully found or not     
        var output = "";       // store information   
        var lati = 0;          // latitude 
        var longi = 0;         // longitude  
       
        // Use ajax to draw data from Google API
        $.ajax({
                type: "GET",
                url: "https://maps.googleapis.com/maps/api/geocode/xml?address="+encodeURIComponent($('#address').val())+"&sensor=false&key=AIzaSyCuQ3EBOM5Vl3x08FzHgzXCTPg5_4ZQaAs",
                dataType: "xml",
                success: processXML,
                error: error
        });
                                       
        // Ask for address again nothing is inputted
        function error() {
            $("#fail2").fadeIn();
        }
                                       
        //Output the address using Google's Maps and Geolocation API
        function processXML(xml) {
          $(xml).find("address_component").each(function() {
            if ($(this).find("type").text() == 'postal_code') {
                                                       
             output = "The post code for that address is: " + $(this).find('long_name').text() + "<br />";                                 
             result =  1;
                                                       
             }
          });

          //Look for longitude and latitude
          $(xml).find("location").each(function () {
              lati = ($(this).find("lat").text());
              longi = ($(this).find("lng").text());
          });
                                               
          //Check if Google API cannot find information               
          if (result==0) {                
              $(".alert").hide();                                        
              $("#fail").fadeIn();
          } 
          
          //Output Longitude and Latitude
          else{
             
             output += "  The Latitude is : " + lati + " and the Longitude is: " + longi;
             
             $("#success").html(output).fadeIn();
                
                //Find location on google map 
                var myLatlng = new google.maps.LatLng(lati, longi);
                var mapOptions = {
                    zoom: 16,
                    center: myLatlng
                }
                var map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
                var marker = new google.maps.Marker({
                    position: myLatlng,
                    map: map,
                    title: $("#address").val()
                });                                       
           }
         }                                   
                       
      });
                       
                       
                                       
       
  </script>
 
</body>
</html>