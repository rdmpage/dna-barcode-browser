var results ={};

var label_to_id = {};
var label_to_bin = {};

//----------------------------------------------------------------------------------------
// output distance matrix
function hits_as_dist(D)
{
	var html = '';
		
	var n = D.length;
	
	html += '<div style="position:relative;">';
	
	var min_d = 1.0;
	var max_d = 0;
	
	for (var i = 1; i < n; i++)
	{
		for (var j = 0; j < i; j++)
		{
			min_d = Math.min(min_d, D[i][j]);
			max_d = Math.max(max_d, D[i][j]);
		}
	}	
	var range = max_d - min_d;

	for (var i = 1; i < n; i++)
	{
		//html += '';
		
		for (var j = 0; j < i; j++)
		{
			var c = (D[i][j] - min_d)/range * 255;
			html += '<div style="position:absolute;width:5px;height:5px;top:' + (i * 5) + 'px;left:' + (j * 5) + 'px;background:rgb(' + c + ',' + c + ',' + c + ')"></div>';
		
			//html += D[i][j].toFixed(3) + ' ';
		}
		
		//html += '<br />';
	}
	
	html += '</div>';
	
	$('#matrix').html(html);
}

//----------------------------------------------------------------------------------------
function compute_distances (hits) {
	// Number of sequences
	var n = hits.length;
	
	//console.log(n);

	var cp = 
		cartesianProduct(
			[
				['A','C','G','T'], 
				['A','C','G','T'], 
				['A','C','G','T'], 
				['A','C','G','T'], 
				['A','C','G','T']
			]
		);

	//console.log(JSON.stringify(cp, null, 2));

	// encode sequences as 5-tuples
	var tuples = [];

	for (var i = 0; i < n; i++) 
	{
		tuples[i] = {};
	
		// intialise
		for (var j in cp)
		{		
			tuples[i][cp[j].join("")] = 0;
		}

		var seq = hits[i]._source.consensusSequence;
		var len = seq.length;
	
		for (var j = 0; j < len - 5; j++)
		{
			var tuple = seq.substring(j, j + 5);
			//document.write(tuple + '<br/>');
		
			if (tuple in tuples[i]) {
				tuples[i][tuple]++;
			}
		}
	}

	// compute distances
	var D = [];
	for (var i = 0; i < n; i++) {
		D[i] = [];
		for (var j = 0; j < n; j++) {
			D[i][j] = 0;
		}
	}

	for (var i = 1; i < n; i++) {
		for (var j = 0; j < i; j++) {
			var f = 0.0;
			
			// compare tuples
			for (var k in tuples[i]) {
				f += Math.min(tuples[i][k], tuples[j][k]) / (Math.min(hits[i]._source.consensusSequence.length, hits[j]._source.consensusSequence.length) - 5 + 1);
				
			}
			var d = (Math.log(0.1 + f) - Math.log(1.1))/Math.log(0.1);
		
			D[i][j] = d;
			D[j][i] = d;
		}
	}

	return D;
}


//----------------------------------------------------------------------------------------
function buildtree(results)
{
	var data = {};
	
	data.otu_index = {};
	data.otu_label = [];
	data.otu_taxon = {};
	data.tree = '';



	// filter any results that don't have a sequence(!)

	var hits = [];
	
	for (var i in results.hits.hits) {
		if (results.hits.hits[i]._source.consensusSequence) {
			hits.push(results.hits.hits[i]);
		}
	}
	
	// Number of sequences
	var n = hits.length;

	// labels
	var otus = [];

	for (var i = 0; i < n; i++) {
		var label = hits[i]._source.id;
		
		label = label.replace("-", "_"); 
	
		if (hits[i]._source.scientificName) {
			label += ' ' + hits[i]._source.scientificName;
			label = label.replace(/\s+/g, "_");
			label = label.replace(/\(/g, "_");
			label = label.replace(/\)/g, "_");
			label = label.replace(":", "");
		}
		
		// reverse map
		label_to_id[label] = i;

		// map sequences to BIN		
		//label_to_bin[label] = hits[i]._source.taxonID;
		
		
		data.otu_index[label] = i;
		data.otu_label.push(label);
		
		if (!data.otu_taxon[hits[i]._source.taxonID]) {
			data.otu_taxon[hits[i]._source.taxonID] = [];
		}
		data.otu_taxon[hits[i]._source.taxonID].push(label);
	
		otus.push({name:label});
		//taxa.push({name:i});
	}
	
	// Distance matrix (this will be destroyed by the tree building)
	var D = compute_distances(hits);
		
	var RNJ = new RapidNeighborJoining(D, otus);
	RNJ.run();
	//console.log('run');
	var treeObject = RNJ.getAsObject();
	var treeNewick = RNJ.getAsNewick();
	//console.log(treeNewick);
	
	showtree('svg', treeNewick);

}