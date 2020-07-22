

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
			feature.properties.name = results.hits.hits[i]._source.id;
	
			feature.properties.popupContent = '';
			if (results.hits.hits[i]._source.scientificName) {
				feature.properties.popupContent = results.hits.hits[i]._source.scientificName + '<br />';
			}
	
			if (results.hits.hits[i]._source.id) {
				feature.properties.popupContent += results.hits.hits[i]._source.id + '<br />';
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
function get_taxid (hit, label) {
	var taxid = 0;
	
	if (hit.dynamicProperties) {
		if (hit.dynamicProperties[label]) {
			if (hit.dynamicProperties[label].id) {
				taxid = hit.dynamicProperties[label].id;
			}
		}	
	}
	
	return taxid;
	
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
			if (results.hits.hits[i]._source.taxonID) {
				hit.label = results.hits.hits[i]._source.taxonID;
			} else {
				hit.label = results.hits.hits[i]._source.id;			
			}
		}
	
		if (results.hits.hits[i]._source.image) {
			hit.image = results.hits.hits[i]._source.image;
		}
	
		hits.push(hit);
					
	}
	
	// simple
	if (0) {
		var html = '';
		html += '<ul>';
		for (var i in hits) {
			html += '<li>' + hits[i].label + '</li>';
		}
		html += '</ul>';
	}
	
	// nice with images
	if (0) {
		var html = '';
		for (var i in hits) {
			html += '<div class="result">';		
			html += hits[i].label;	
		
			if (hits[i].image) {
				html += '<img src="http://exeg5le.cloudimg.io/s/height/40/' + hits[i].image + '">';
			}
			
			html += '</div>';
		}
	}
	
	// as classification
	if (1)
	{
		var t = new Tree();
		var node_list = [];
		var first = true;

		var n = results.hits.hits.length;
		for (var i = 0; i < n; i++) {

			var path = results.hits.hits[i]._source.lineage;
			path.unshift('root');
	
			var taxon_label = '';
			var taxid = 0;
	
	
	
			if (results.hits.hits[i]._source.scientificName) {
				taxon_label = results.hits.hits[i]._source.scientificName;
			} else {
				if (results.hits.hits[i]._source.taxonID) {
					taxon_label = results.hits.hits[i]._source.taxonID;
				}
			}
	
			if (taxon_label != '') {
				path.push(taxon_label);
			}
	
			path.push(results.hits.hits[i]._source.id);
	
			var m = path.length;
	
			if (first) {
				first = false;
		
				var label = path[0];
				var curnode = t.NewNode();
				curnode.label = label;
		
				taxid = get_taxid(results.hits.hits[i]._source, label);
				if (taxid != 0) {
					curnode.taxid = taxid;
				}

				node_list[path[0]] = curnode;
				t.root = curnode;
		
				for (var j = 1; j < m; j++) {
					label = path[j];
					node = t.NewNode();
					node.label = label;
			
					taxid = get_taxid(results.hits.hits[i]._source, label);
					if (taxid != 0) {
						node.taxid = taxid;
					}
			
			
					node_list[path[j]] = node;
					curnode.child = node;
					node.ancestor = curnode;
					curnode = node;
				}		

			} else {
				// Add remaining paths
		
				for (var j = 0; j < m; j++) {
					if (node_list[path[j]]) {
					} else {
						var anc = path[j - 1];
						curnode = node_list[anc];
						var q = curnode.child;
						label = path[j];
				
						if (q) {
							while (q.sibling) {
								q = q.sibling;
							}
							node = t.NewNode();
							node.label = label;
					
							taxid = get_taxid(results.hits.hits[i]._source, label);
							if (taxid != 0) {
								node.taxid = taxid;
							}
				
							node_list[path[j]] = node;
							q.sibling = node;
							node.ancestor = curnode;
					
						}
						else
						{
							node = t.NewNode();
							node.label = label;
					
							taxid = get_taxid(results.hits.hits[i]._source, label);
							if (taxid != 0) {
								node.taxid = taxid;
							}
				
							node_list[path[j]] = node;
							curnode.child = node;
							node.ancestor = curnode;						
						}
					}		
				}		
			}
	
	
			node_list[path[m - 2]].bin = '';
	
			curnode = node_list[path[m - 1]];
	
			if (results.hits.hits[i]._source.image) {
				curnode.image = results.hits.hits[i]._source.image;
			}
		}


		var html = '<div class="tree">';
		html += '<ul>';

		var stack = [];
		curnode = t.root;

		while (curnode != null) {

			html += '<li';

			if (curnode.IsLeaf()) {
				html += ' class="leaf"';
			}
	
			html += '>';
			
			if (curnode.IsLeaf()) {
				html += '<a href="https://v4.boldsystems.org/index.php/Public_RecordView?processid=' + curnode.label + '" target=_new">';
			}			
			
			html += curnode.label;
			
			if (curnode.IsLeaf()) {
				html += '</a>';
			}			
			
	
			console.log(curnode.label);
	
			if (curnode.IsLeaf()) {
				if (curnode.image) {
					html += '<img src="http://exeg5le.cloudimg.io/s/height/40/' + curnode.image + '">';
				}
			}
	
			if (curnode.child) {
				html += '<ul>';
				stack.push(curnode);
				curnode = curnode.child;
			} else {
				while (stack.length > 0 && (curnode.sibling == null)) {
					curnode = stack.pop();	
					html += '</li>';
					html += '</ul>';		
				}
		
				if (stack.length == 0) {
					curnode = null;
				} else {
					html += '</li>';
					curnode = curnode.sibling;
				}
	
			}

		}
		html += '</ul>';	
		html += '</div>';	
	}
	
	
	document.getElementById(element_id).innerHTML = html;
	
	
}


