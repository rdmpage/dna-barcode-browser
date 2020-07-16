/* Support maps */

var map, smallmap;
var geojsonLayer = null;



// http://gis.stackexchange.com/a/116193
// http://jsfiddle.net/GFarkas/qzdr2w73/4/
var icon = new L.divIcon({className: 'mydivicon'});	


//--------------------------------------------------------------------------------
function onEachFeature(feature, layer) {
	// does this feature have a property named popupContent?
	if (feature.properties && feature.properties.popupContent) {
		//console.log(feature.properties.popupContent);
		// content must be a string, see http://stackoverflow.com/a/22476287
		layer.bindPopup(String(feature.properties.popupContent));
	}
}	
	
var popup = L.popup();

/*
//--------------------------------------------------------------------------------
function onMapClick(e) {
	var geohash = encodeGeoHash(e.latlng.wrap().lat, e.latlng.wrap().lng);
	geohash = geohash.substring(0, 3);
	
	occurrences_with_geohash(geohash);

	popup
		.setLatLng(e.latlng)
		.setContent("You clicked the map at " + e.latlng.wrap().toString() + " geohash " + geohash)
		.openOn(map);
}
*/		
	
//--------------------------------------------------------------------------------
// The large map where we display results
function create_map() {
	map = new L.Map('map');

	// create the tile layer with correct attribution
	var osmUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
	
	/* This is where we can change the base map tiles */
	// GBIF
	// osmUrl = 'https://api.mapbox.com/v4/mapbox.outdoors/{z}/{x}/{y}.png?access_token=pk.eyJ1IjoicmRtcGFnZSIsImEiOiJjajJrdmJzbW8wMDAxMnduejJvcmEza2k4In0.bpLlN9O6DylOJyACE8IteA';
	
	var osmAttrib='Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
	var osm = new L.TileLayer(osmUrl, {minZoom: 1, maxZoom: 12, attribution: osmAttrib});		

	map.setView(new L.LatLng(0, 0),4);
	map.addLayer(osm);	
		
	/* This is where we add custom tiles, e.g. with data points */
	var dotsAttrib='RDMP';
	var dots = new L.TileLayer('tile.php?x={x}&y={y}&z={z}', 
		{minZoom: 0, maxZoom: 14, attribution: dotsAttrib});
		
	map.addLayer(dots);	
	
}

//--------------------------------------------------------------------------------
// Small map we use to create spatial queries
function create_small_map() {
	smallmap = new L.Map('smallmap');

	// create the tile layer with correct attribution
	var osmUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
	var osmAttrib='Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
	var osm = new L.TileLayer(osmUrl, {minZoom: 1, maxZoom: 12, attribution: osmAttrib});		

	smallmap.setView(new L.LatLng(0, 0), 1);
	smallmap.addLayer(osm);	
	
	/* This is where we add custom tiles, e.g. with data points */
	/*
	var dotsAttrib='BOLD';
	var dots = new L.TileLayer('couchtile.php?x={x}&y={y}&z={z}', 
		{minZoom: 0, maxZoom: 14, attribution: dotsAttrib});
		
	smallmap.addLayer(dots);	
	*/
	
	var drawnItems = new L.FeatureGroup();
	smallmap.addLayer(drawnItems);

	var drawControl = new L.Control.Draw({
		position: 'topleft',
		draw: {
			marker: false, // turn off marker
			polygon: {
				shapeOptions: {
					color: 'purple'
				},
				allowIntersection: false,
				drawError: {
					color: 'orange',
					timeout: 1000
				},
				showArea: true,
				metric: false,
				repeatMode: true
			},
			polyline: false,
			rect: {
				shapeOptions: {
					color: 'green'
				},
			},
			circle: false
		},
		edit: {
			featureGroup: drawnItems
		}
	});
	smallmap.addControl(drawControl);	
	
	smallmap.on('draw:created', function (e) {
		var type = e.layerType,
			layer = e.layer;

		drawnItems.addLayer(layer);
		
		//alert(JSON.stringify(layer.toGeoJSON()));
			
		console.log(JSON.stringify(layer.toGeoJSON()));

		do_geo_search(layer.toGeoJSON());
			
	});
						
}


//--------------------------------------------------------------------------------
// Clear data from large map
function clear_map(map) {
	if (geojsonLayer) {
		map.removeLayer(geojsonLayer);
	}
}	

//--------------------------------------------------------------------------------
// Add data to large map
function add_data(data) {

	geojsonLayer = L.geoJson(data, { 

	pointToLayer: function (feature, latlng) {
		//return L.marker(latlng);
	
		return L.marker(latlng, {
			icon: icon});
	},			
	style: function (feature) {
		return feature.properties && feature.properties.style;
	},
	onEachFeature: onEachFeature,
	}).addTo(map);
	
	// Open popups on hover
	geojsonLayer.on('mouseover', function (e) {
		e.layer.openPopup();
	});

	if (data.type) {
		if (data.type == 'Polygon') {
			for (var i in data.coordinates) {
			  minx = 180;
			  miny = 90;
			  maxx = -180;
			  maxy = -90;
		  
			  for (var j in data.coordinates[i]) {
				minx = Math.min(minx, data.coordinates[i][j][0]);
				miny = Math.min(miny, data.coordinates[i][j][1]);
				maxx = Math.max(maxx, data.coordinates[i][j][0]);
				maxy = Math.max(maxy, data.coordinates[i][j][1]);
			  }
			}
			
			bounds = L.latLngBounds(L.latLng(miny,minx), L.latLng(maxy,maxx));
			map.fitBounds(bounds);
		}
		if (data.type == 'MultiPoint') {
			minx = 180;
			miny = 90;
			maxx = -180;
			maxy = -90;				
			for (var i in data.coordinates) {
				minx = Math.min(minx, data.coordinates[i][0]);
				miny = Math.min(miny, data.coordinates[i][1]);
				maxx = Math.max(maxx, data.coordinates[i][0]);
				maxy = Math.max(maxy, data.coordinates[i][1]);
			}
			
			bounds = L.latLngBounds(L.latLng(miny,minx), L.latLng(maxy,maxx));
			map.fitBounds(bounds);
		}
		if (data.type == 'FeatureCollection') {
			minx = 180;
			miny = 90;
			maxx = -180;
			maxy = -90;				
			for (var i in data.features) {
				//console.log(JSON.stringify(data.features[i]));
			
				minx = Math.min(minx, data.features[i].geometry.coordinates[0]);
				miny = Math.min(miny, data.features[i].geometry.coordinates[1]);
				maxx = Math.max(maxx, data.features[i].geometry.coordinates[0]);
				maxy = Math.max(maxy, data.features[i].geometry.coordinates[1]);
				
			}
			
			bounds = L.latLngBounds(L.latLng(miny,minx), L.latLng(maxy,maxx));
			map.fitBounds(bounds);
		}
	}		    					
}
