<?php

require_once (dirname(__FILE__) . '/elastic.php');

// tile request will supply x,y and z (zoom level)

$x = 0;
$y = 0;
$zoom = 0;


if (isset($_GET['x']))
{
	$x = (Integer)$_GET['x'];
}

if (isset($_GET['y']))
{
	$y = (Integer)$_GET['y'];
}

if (isset($_GET['z']))
{
	$zoom = (Integer)$_GET['z'];
}

  
function xyz_to_lat_long($x, $y, $zoom)
{
	$lon_lat = array();
	$n = pow(2, $zoom);

	$longitude_deg = $x / $n * 360.0 - 180.0;
	$latitude_rad = atan(sinh(M_PI * (1 - 2 * $y / $n)));
	$latitude_deg = $latitude_rad * 180.0 /  M_PI;

	$lon_lat = array($longitude_deg, $latitude_deg);

	return $lon_lat;
}

function xyz_to_bounding_box($x, $y, $zoom)
{
	$obj = new stdclass;
	
	$lon_lat = xyz_to_lat_long($x, $y, $zoom);
	
	$obj->top_left = new stdclass;
	$obj->top_left->lat = $lon_lat[1];
	$obj->top_left->lon = $lon_lat[0];

	$lon_lat = xyz_to_lat_long($x + 1, $y + 1, $zoom);
	
	$obj->bottom_right = new stdclass;
	$obj->bottom_right->lat = $lon_lat[1];
	$obj->bottom_right->lon = $lon_lat[0];
	
	return $obj;

}

function lat_lon_to_xy($lon_lat, $zoom)
{
	$xy = array();
	
	$n = pow(2, $zoom);
	
	$x_pos = ($lon_lat[0] + 180)/360 * $n;
	$x = floor($x_pos);
	
	$relative_x = round(256 * ($x_pos - $x));
	
	$y_pos = (1 - log(tan($lon_lat[1] * M_PI / 180.0) + 1/cos($lon_lat[1] * M_PI / 180.0))/M_PI)/2 * $n;
	$y = floor($y_pos);
	
	$relative_y = round(256 * ($y_pos - $y));

	$xy = array($relative_x , $relative_y);
	
	return $xy;
}

		//var y_pos = (1-Math.log(Math.tan(parseFloat (doc.decimalLatitude)*Math.PI/180) + 1/Math.cos(parseFloat(doc.decimalLatitude)*Math.PI/180))/Math.PI)/2 *Math.pow(2,zoom);
		//var y_pos = (1-Math.log(Math.tan() + 1/Math.cos())/Math.PI)/2 *Math.pow(2,zoom);
		//var y = Math.floor(y_pos);


// Query Elastic to get dots on map 


// Create SVG tile
$xml = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns:xlink="http://www.w3.org/1999/xlink" 
xmlns="http://www.w3.org/2000/svg" 
width="256" height="256"


 >
   <style type="text/css">
      <![CDATA[     
      ]]>
   </style>
 <g>';
 
 //  viewBox="-10 -10 266 266" overflow="visible"
 
// Border for debugging
if (0)
{
	$xml .= '<rect id="border" x="0" y="0" width="256" height="256" style="stroke-width:1;fill:none;stroke:rgb(192,192,192);" />';			
}
	 
$marker_size = 4;
$marker_shape = 'circle';
//$marker_shape = 'square';

$bounding_box = xyz_to_bounding_box($x, $y, $zoom);

//$xml .= '<text x="10" y="20" style="font-size:6px">' . json_encode($bounding_box) . '</text>';




$query_json = '{
	"size": 1000,
	"query": {
		"bool": {
			"must": {
				"match_all": {}
			},
			"filter": []
		}
	},
	"aggs": {
		"zoom": {
			"geohash_grid": {
				"field": "geometry.coordinates",
				"precision": 0
			}
		}
	}
}';



$query = json_decode($query_json);

$geo_filter = new stdclass;
$geo_filter->geo_bounding_box = new stdclass;
$geo_filter->geo_bounding_box->{'geometry.coordinates'} = $bounding_box;

$query->query->bool->filter = $geo_filter;

$query->aggs->zoom->geohash_grid->precision = 8;

//echo json_encode($query);

$response =	$elastic->send('POST',  '_search', json_encode($query));					

//echo $response;

$response_obj = json_decode($response);


foreach ($response_obj->hits->hits as $hit)
{
	// compute place in tile
	
	// output
	
	$x_pos = 10;
	$y_pos = 10;
	
	$lon_lat = $hit->_source->geometry->coordinates;
	
	$xy = lat_lon_to_xy	($lon_lat, $zoom);
	
	$x_pos = $xy[0];
	$y_pos = $xy[1];
	
	switch ($marker_shape)
	{
		case 'square':
			$xml .= '<rect id="dot" x="' . ($x_pos - $marker_size) . '" y="' . ($y_pos - $marker_size) . '" width="' . $marker_size . '" height="' . $marker_size . '" style="stroke-width:1;"';			
			break;
	
		case 'circle':
		default:
			$radius = $marker_size / 2;
			$offset = 0;
			$xml .= '<circle id="dot" cx="' . ($x_pos - $offset) . '" cy="' . ($y_pos - $offset) . '" r="' . $radius . '" style="stroke-width:0.5;"';
			break;
	}
		
	$fill = 'rgba(0,0,0,0.5)';
	$fill = 'rgb(208,104,85)'; // Canadensys
	
	$xml .= ' fill="'. $fill . '"';
		
		
	$xml .= ' stroke="rgb(38,38,38)"'; // Canadensys
	$xml .= '/>';	


}

 
$xml .= '
      </g>
	</svg>';
	

// Serve up tile	
header("Content-type: image/svg+xml");
//header("Cache-control: max-age=3600");

echo $xml;


?>