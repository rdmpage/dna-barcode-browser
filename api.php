<?php

// Geographic search

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/config.inc.php');
require_once(dirname(__FILE__) . '/elastic.php');
require_once(dirname(__FILE__) . '/api_utils.php');


//----------------------------------------------------------------------------------------
// https://gist.github.com/jwage/11193216

// 5-mer
$attributeValues 
	= array(
		array('A','C','G','T'), 
		array('A','C','G','T'), 
		array('A','C','G','T'), 
		array('A','C','G','T'), 
		array('A','C','G','T')
	);		

class Cartesian
{
    public static function build($set)
    {
        if (!$set) {
            return array(array());
        }
        $subset = array_shift($set);
        $cartesianSubset = self::build($set);
        $result = array();
        foreach ($subset as $value) {
            foreach ($cartesianSubset as $p) {
                array_unshift($p, $value);
                $result[] = $p;
            }
        }
        return $result;        
    }
}

$tuples = Cartesian::build($attributeValues);


//----------------------------------------------------------------------------------------
function default_display()
{
	echo "hi";
}

//----------------------------------------------------------------------------------------
function elastic_to_nexus($response_obj)
{
	global $tuples;

	$labels 			= array();
	$cleaned_sequences 	= array();
	$sequence_tuples 	= array();

	$taxa				= array();
	$label_to_taxon		= array();

	$max_label_length = 0;

	$count = 1;
	
	foreach ($response_obj->hits->hits as $hit)
	{
		$label 		= $hit->_id;
		$labels[] 	= $label;
	
		$max_label_length = max($max_label_length, strlen($label));
	
		$seq_tuples = array();
	
		foreach($tuples as $tuple)
		{
			$k = join('', $tuple);
			$seq_tuples[$k] = 0;	
		}	
	
		$seq = $hit->_source->consensusSequence;
		$seq = preg_replace('/[-RYN]/', '', strtoupper($seq));
	
		$len = strlen($seq);
	
		for ($j = 0; $j < $len - 5; $j++)
		{
			$k = substr($seq, $j, 5);
			
			// ignore tuples with ambiguity codes
			if (isset($seq_tuples[$k]))
			{
				$seq_tuples[$k]++;				
			}
		}
	
		$cleaned_sequences[$label] = $seq;
		$sequence_tuples[$label] = $seq_tuples;
	
		$taxonID = $hit->_source->taxonID;
	
		if (!isset($taxa[$taxonID]))
		{
			$taxa[$taxonID] = array();
		}
		$taxa[$taxonID][] = "'" . $label . "'";
		//$taxa[$taxonID][] = $count;
		//$label_to_taxon[] = "'" . $taxonID . "'";
		
		$count++;

	}

	// distance matrix

	$n = count($response_obj->hits->hits);

	// distance matrix
	$D = array();
	for ($i = 0; $i < $n; $i++)
	{
		$D[$i] = array();
		for ($j = 0; $j < $n; $j++)
		{
			$D[$i][$j] = 0;
		}
	}

	// tuples 
	for ($i = 1; $i < $n; $i++)
	{
		for ($j = 0; $j < $i; $j++)
		{
			$f = 0;
				
			$label_i = $labels[$i];
			$label_j = $labels[$j];
		
			foreach($tuples as $tuple)
			{
				$k = join('', $tuple);

				$na = $sequence_tuples[$label_i][$k];
				$nb = $sequence_tuples[$label_j][$k];
						
				$f += min($na, $nb) / (min(strlen($cleaned_sequences[$label_i]), strlen($cleaned_sequences[$label_j])) - 5 + 1);
			}
	
			$d = (log(0.1 + $f) - log(1.1))/log(0.1);

			$D[$i][$j] = $d;
			$D[$j][$i] = $d;
	
		}
	}

	// print_r($D);


	// output

	$nexus = '';

	$nexus .= "#NEXUS\n\n";

	$nexus .= "BEGIN TAXA;\n";
	$nexus .= "	DIMENSIONS ntax=$n;\n";
	$nexus .= "	TAXLABELS ";

	for ($i = 0; $i < $n; $i++)
	{
		//echo preg_replace('/\.\d+$/', '', $obj->rows[$i]->value->id) . ' '; 
		$nexus .= "'" . $labels[$i] . "' "; 
	}

	$nexus .= ";\n";
	$nexus .= "	END;\n\n";

	// distances


	$nexus .= "[k-mer distances]\n";
	$nexus .= "BEGIN DISTANCES;\n"; 
	$nexus .= "FORMAT TRIANGLE = LOWER;\n"; 
	$nexus .= "Matrix \n"; 
	for ($i = 0; $i < $n; $i++)
	{
	//	echo str_pad(preg_replace('/\.\d+$/', '', $obj->rows[$i]->value->id), 30, ' ', STR_PAD_RIGHT);

		$label = "'" . $labels[$i] . "'";

		$nexus .= str_pad($label, $max_label_length + 2, ' ', STR_PAD_RIGHT);

		for ($j = 0; $j <= $i; $j++)
		{
			$nexus .= ' ' . number_format($D[$i][$j], 3);
		}
		$nexus .= "\n";
	}
	$nexus .= ";\n";
	$nexus .= "END;\n";

	// sets
	$nexus .= "\n";
	$nexus .= "[BINS for sequences]\n";
	$nexus .= "BEGIN SETS;\n";
	
	/*
	$nexus .= "   TAXPARTITION * BINS = ";
	
	$partition = array();
	foreach ($taxa as $bin => $members)
	{
		$partition[] = '"' . $bin . '" : ' . join(' ', $members);
	}
	$nexus .= join(",", $partition);
	$nexus .= ";\n";
	*/
	
	foreach ($taxa as $bin => $members)
	{
		$nexus .= "TAXSET '" . $bin . "' = " . join(' ', $members) . ";\n";
	}

	$nexus .= "END;\n";
	


/*
BEGIN LABELS;
	TAXAGROUPLABEL x COLOR = (RGB 1 1 0.36862745) ;
	TAXAGROUPLABEL y COLOR = (RGB 1 0.41176471 0.26666667) ;
END;
*/

	/*
	// notes
	$nexus .= "\n";
	$nexus .= "[BINS as alternative names]\n";
	$nexus .= "BEGIN NOTES;\n";
	$nexus .= "ALTTAXNAMES * BINS  = ";
	$nexus .= join(" ", $label_to_taxon);
	$nexus .= ";\n";
	$nexus .= "END;\n";
	*/
	

	// make a tree
	$nexus .= "\n";
	$nexus .= "[PAUP block]\n";
	$nexus .= "BEGIN PAUP;\n";
	$nexus .= "   [root trees at midpoint]\n";
	$nexus .= "   set rootmethod=midpoint;\n";
	$nexus .= "   set outroot=monophyl;\n";
	$nexus .= "   [construct tree using neighbour-joining]\n";
	$nexus .= "   nj;\n";
	$nexus .="   [ensure branch lengths are output as substituions per nucleotide]\n";
	$nexus .="   set criterion=distance;\n";
	//$nexus .="   [write rooted trees in Newick format with branch lengths]\n";
	//$nexus .="   savetrees format=nexus root=yes brlen=yes replace=yes;\n";
	//$nexus .="   quit;\n";	
	$nexus .= "END;\n";	
	
	return $nexus;
}


	
//-----------------------------------------------------------------------------------------
// Geo search
function display_geo ($geojson, $format = 'json', $callback = '')
{
	global $elastic;
	
	$obj = null;
	$status = 404;

	$geo = json_decode($geojson);
	
	$query = new stdclass;
	$query->size = 100;
	$query->query = new stdclass;
	$query->query->bool = new stdclass;
	$query->query->bool->must = new stdclass;
	$query->query->bool->must->match_all = new stdclass;
	
	$query->query->bool->filter = new stdclass;
	$query->query->bool->filter->geo_polygon = new stdclass;
	$query->query->bool->filter->geo_polygon->{'geometry.coordinates'} = new stdclass;
	$query->query->bool->filter->geo_polygon->{'geometry.coordinates'}->points = array();
	
	if (isset($geo->geometry->coordinates))
	{
		$query->query->bool->filter->geo_polygon->{'geometry.coordinates'}->points = $geo->geometry->coordinates[0];
	}
	
	$response = $elastic->send('POST',  '_search?pretty', json_encode($query));					
	$obj = json_decode($response);
	
	if ($obj)
	{
		$status = 200;
	}
	
	if ($format == 'nexus')
	{
		$obj = elastic_to_nexus($obj);
	}
		
	api_output($obj, $callback, $format, $status);
}	


//-----------------------------------------------------------------------------------------
// BLAST-like search
function display_blast ($sequence, $format = 'json', $callback = '')
{
	global $elastic;
	
	$obj = null;
	$status = 404;

	$query = new stdclass;
	$query->size = 100;
	$query->query = new stdclass;
	$query->query->multi_match = new stdclass;
	$query->query->multi_match->query = $sequence;
	$query->query->multi_match->fields = array("consensusSequence");
	
	$response = $elastic->send('POST',  '_search?pretty', json_encode($query));					
	$obj = json_decode($response);
	
	if ($obj)
	{
		$status = 200;
	}
	
	if ($format == 'nexus')
	{
		$obj = elastic_to_nexus($obj);
	}
		
	api_output($obj, $callback, $format, $status);
}	


//----------------------------------------------------------------------------------------
function main()
{
	global $config;

	$callback = '';
	$handled = false;
		
	// If no query parameters 
	if (count($_GET) == 0)
	{
		default_display();
		exit(0);
	}
	
	if (isset($_GET['callback']))
	{	
		$callback = $_GET['callback'];
	}
	
	// Submit job
	if (!$handled)
	{
		if (isset($_GET['geo']) && ($_GET['geo'] != ''))
		{	
			$geo = $_GET['geo'];
			
			$format = 'json';
			
			if (isset($_GET['format']))
			{
				$format = $_GET['format'];
			}						
			
			if (!$handled)
			{
				display_geo($geo, $format, $callback);
				$handled = true;
			}
			
		}
	}
	
	if (!$handled)
	{
		if (isset($_GET['seq']) && ($_GET['seq'] != ''))
		{	
			$seq = $_GET['seq'];
			
			$format = 'json';
			
			if (isset($_GET['format']))
			{
				$format = $_GET['format'];
			}						
			
			if (!$handled)
			{
				display_blast($seq, $format, $callback);
				$handled = true;
			}
			
		}
	}	
	
	if (!$handled)
	{
		default_display();
	}

}


main();



?>
