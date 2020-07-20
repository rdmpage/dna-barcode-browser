

//----------------------------------------------------------------------------------------
function clear_list(element_id) {
	document.getElementById(element_id).innerHTML = "";
}

//----------------------------------------------------------------------------------------
function hits_on_map(results, map)
{
	var geojson = {};

	geojson.type = "FeatureCollection";
	geojson.features = [];

	var n = results.hits.hits.length;
	for (var i = 0; i < n; i++) {
		if (results.hits.hits[i]._source.geometry) {
			var feature = {};
			feature.type = "Feature";
			feature.geometry = results.hits.hits[i]._source.geometry;
	
			feature.properties = {};
	
			// Content for the popup							
			feature.properties.name = results.hits.hits[i]._source.materialSampleID;
	
			feature.properties.popupContent = '';
			if (results.hits.hits[i]._source.scientificName) {
				feature.properties.popupContent = results.hits.hits[i]._source.scientificName + '<br />';
			}
	
			if (results.hits.hits[i]._source.materialSampleID) {
				feature.properties.popupContent += results.hits.hits[i]._source.materialSampleID + '<br />';
			}
			if (results.hits.hits[i]._source.otherCatalogNumbers) {
				feature.properties.popupContent += results.hits.hits[i]._source.otherCatalogNumbers + '<br />';
			}
			if (results.hits.hits[i]._source.fieldNumber) {
				feature.properties.popupContent += results.hits.hits[i]._source.fieldNumber + '<br />';
			}
			if (results.hits.hits[i]._source.geneticAccessionNumber) {
				feature.properties.popupContent += '<b>' + results.hits.hits[i]._source.geneticAccessionNumber + '</b><br />';
			}
			geojson.features.push(feature);	
		}						
	}
	add_data(geojson);
}


//----------------------------------------------------------------------------------------
function hits_as_list(results, element_id)
{
	var hits = [];

	var n = results.hits.hits.length;
	for (var i = 0; i < n; i++) {
		
		var hit = {};
		
		hit.path = results.hits.hits[i]._source.path;
		
		if (results.hits.hits[i]._source.scientificName) {
			hit.label = results.hits.hits[i]._source.scientificName;
		} else {
			hit.label = results.hits.hits[i]._source.taxonID;
		}
	
		if (results.hits.hits[i]._source.image) {
			hit.image = results.hits.hits[i]._source.image;
		}
	
		hits.push(hit);
					
	}
	
	// simple
	var html = '';
	html += '<ul>';
	for (var i in hits) {
		html += '<li>' + hits[i].label + '</li>';
	}
	html += '</ul>';
	
	document.getElementById(element_id).innerHTML = html;
	
	
}


