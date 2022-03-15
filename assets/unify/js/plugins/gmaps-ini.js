// https://developers.google.com/maps/documentation/javascript/examples/
function initMap() {
  var customMapType = new google.maps.StyledMapType([
      {
        stylers: [
          {'saturation': -100},
          {'lightness': 51},
          {'visibility': 'simplified'}
        ]
      },
      {
        elementType: 'labels',
        stylers: [{visibility: 'on'}]
      },
      {
        featureType: 'water',
        stylers: [{color: '#bac6cb'}]
      }
    ], {
      name: 'GINGIN'
  });

  var image = new google.maps.MarkerImage(
  	'assets/img/marker.png',
  	new google.maps.Size(48,54),
  	new google.maps.Point(0,0),
  	new google.maps.Point(24,54)
  	);    var image2 = new google.maps.MarkerImage(    	'assets/img/marker2.png',    	new google.maps.Size(48,54),    	new google.maps.Point(0,0),    	new google.maps.Point(24,54)    	);

  var customMapTypeId = 'custom_style';

  var map = new google.maps.Map(document.getElementById('map'), {
    zoom: 11,
    scrollwheel: false,
    center: {lat: 19.380, lng: -99.18},  // Mexico.
   // center: {lat: 19.36736, lng: -99.259280},
    mapTypeControlOptions: {
      mapTypeIds: [google.maps.MapTypeId.ROADMAP, customMapTypeId]
    }
  });

  var infowindow = new google.maps.InfoWindow;
  infowindow.setContent('<b>GINGIN</b>');

  var marker = new google.maps.Marker({
  	map: map,
  	clickable: false,
  	icon: image,
  	 title:"GinGin Roma",
  	position: {lat: 19.419018, lng: -99.167409}
  });
  
  var marker = new google.maps.Marker({
	  	map: map,
	  	clickable: false,
	  	icon: image,
	  	position: {lat: 19.432480, lng: -99.19917}
	  });
  
  
  var marker = new google.maps.Marker({
	  	map: map,
	  	clickable: false,
	  	icon: image,
	  	title:"GinGin Santa Fe",
	  	position: {lat: 19.36675, lng: -99.259750}	    
	  });    var marker = new google.maps.Marker({  	map: map,  	clickable: false,  	icon: image,  	title:"GinGin Interlomas",  	position: {lat: 19.397199, lng: -99.280902},  	  });    var marker = new google.maps.Marker({  	map: map,  	clickable: false,  	icon: image2,  	title:"GinGin Álvaro Obregón",  	position: {lat: 19.418252, lng: -99.158869}  });

  map.mapTypes.set(customMapTypeId, customMapType);
  map.setMapTypeId(customMapTypeId);
/*
  //Roma
  $("#1").on('click', function ()
      {
  	  newLocation(19.419018,-99.167409);
  	});
  //Polanco
  $("#2").on('click', function ()
      {
  	  newLocation(19.432480,-99.19917);
  	});
  //Santa Fe
  $("#3").on('click', function ()
   {
  	  newLocation(19.36675, -99.259750);
  	});
  //Interlomas
  $("#6").on('click', function ()
      {
  	  newLocation(19.397199,-99.280902);
  	});    $("#5").on('click', function ()      {  	  newLocation(19.418252,-99.158869);  	});    */
  
  //google.maps.event.addDomListener(window, 'load', initialize);

  
  /*
  function newLocation(newLat,newLng)
  {
  
  	map.setCenter({
  		lat : newLat,
  		lng : newLng
  	});
  	
  	map.setZoom(17);
  }*/
  
}
