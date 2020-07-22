<?php


//----------------------------------------------------------------------------------------
function get($url)
{
	$opts = array(
	  CURLOPT_URL =>$url,
	  CURLOPT_FOLLOWLOCATION => TRUE,
	  CURLOPT_RETURNTRANSFER => TRUE
	);
	
	$ch = curl_init();
	curl_setopt_array($ch, $opts);
	$data = curl_exec($ch);
	$info = curl_getinfo($ch); 
	curl_close($ch);
	
	return $data;

}


//----------------------------------------------------------------------------------------
$key_to_dwca = array(

// sample
'processid' 			=> 'dwc:materialSampleID',
'sampleid'				=> 'dwc:otherCatalogNumbers',
'catalognum'			=> 'dwc:catalogNumber',
'institution_storing'	=> 'dwc:institutionCode',
'fieldnum'				=> 'dwc:fieldNumber',

// identification
'bin_uri'						=> 'dwc:taxonID',
'phylum_name'					=> 'dwc:phylum',
'class_name'					=> 'dwc:class',
'order_name'					=> 'dwc:order',
'family_name'					=> 'dwc:family',
'genus_name'					=> 'dwc:genus',
'species_name'					=> 'dwc:scientificName',
'identification_provided_by'	=> 'dwc:identifiedBy',

// not a BOLD term
'dynamicProperties'				=> 'dwc:dynamicProperties',

// type status
'typeStatus'				=> 'dwc:typeStatus', // Not a BOLD field, but type info can be in 'voucher_type'

// event
'collectors' 		=> 'dwc:recordedBy',
'collectiondate'	=> 'dwc:eventDate',
'lifestage' 		=> 'dwc:lifestage',
'sex' 				=> 'dwc:sex',
'notes' 			=> 'dwc:occurrenceRemarks',
 
// locality
'lat'					=> 'dwc:decimalLatitude',
'lon'					=> 'dwc:decimalLongitude',
'coord_source'			=> 'dwc:georeferenceSources',
'coord_accuracy'		=> 'dwc:coordinatePrecision',
'country'				=> 'dwc:country',
'province'				=> 'dwc:stateProvince',
'exactsite'				=> 'dwc:locality',

// sequence
'genbank_accession'		=> 'dwc:associatedSequences'

);

//----------------------------------------------------------------------------------------
$key_to_ggbn = array(
'processid' 			=> 'dwc:materialSampleID',
'genbank_accession'		=> 'ggbn:geneticAccessionNumber',
'markercode'			=> 'ggbn:marker',
'nucleotides'			=> 'ggbn:consensusSequence'
);

//----------------------------------------------------------------------------------------
// Generate meta.xml file for this data
function write_meta($filename = 'meta.xml')
{
	global $key_to_dwca;
	global $key_to_ggbn;
	
	$meta = new DomDocument('1.0', 'UTF-8');
	$meta->formatOutput = true;

	$archive = $meta->createElement('archive');
	$archive->setAttribute('xmlns', 'http://rs.tdwg.org/dwc/text/');
	//$archive->setAttribute('metadata', 'eml.xml');
	$archive = $meta->appendChild($archive);


	// core
	$core = $meta->createElement('core');
	$core->setAttribute('encoding', 'utf-8');
	$core->setAttribute('fieldsTerminatedBy', '\t');
	$core->setAttribute('linesTerminatedBy', '\n');
	$core->setAttribute('fieldsEnclosedBy', '');
	$core->setAttribute('ignoreHeaderLines', '1');
	$core->setAttribute('rowType', 'http://rs.tdwg.org/dwc/terms/MaterialSample');
	$core = $archive->appendChild($core);

	// files
	$files = $core->appendChild($meta->createElement('files'));
	$location = $files->appendChild($meta->createElement('location'));
	$location->appendChild($meta->createTextNode('occurrences.tsv'));
	
	$id = $core->appendChild($meta->createElement('id'));
	$id->setAttribute('index', '0');
	
	$count = 0;
	foreach ($key_to_dwca as $k => $term)
	{
		$field = $core->appendChild($meta->createElement('field'));
		$field->setAttribute('index', $count);

		// namespaces
		$term = str_replace('dwc:', 'http://rs.tdwg.org/dwc/terms/', $term);
		
		$field->setAttribute('term', $term);
		
		$count++;
	}	
	
	// defaults
	$field = $core->appendChild($meta->createElement('field'));
	$field->setAttribute('term', 'http://rs.tdwg.org/dwc/terms/basisOfRecord');
	//$field->setAttribute('default', 'http://rs.tdwg.org/dwc/terms/MaterialSample');
	$field->setAttribute('default', 'MaterialSample');

	
	// extension
	$extension = $meta->createElement('extension');
	$extension->setAttribute('encoding', 'utf-8');
	$extension->setAttribute('fieldsTerminatedBy', '\t');
	$extension->setAttribute('linesTerminatedBy', '\n');
	$extension->setAttribute('fieldsEnclosedBy', '');
	$extension->setAttribute('ignoreHeaderLines', '1');
	$extension->setAttribute('rowType', 'http://data.ggbn.org/schemas/ggbn/terms/Amplification');
	$extension = $archive->appendChild($extension);

	// files
	$files = $extension->appendChild($meta->createElement('files'));
	$location = $files->appendChild($meta->createElement('location'));
	$location->appendChild($meta->createTextNode('sequences.tsv'));
	
	$coreid = $extension->appendChild($meta->createElement('coreid'));
	$coreid->setAttribute('index', '0');
	
	$count = 0;
	foreach ($key_to_ggbn as $k => $term)
	{

		// namespaces
		if (preg_match('/^ggbn:/', $term))
		{
			$field = $extension->appendChild($meta->createElement('field'));
			$field->setAttribute('index', $count);
		
			$term = str_replace('ggbn:', 'http://data.ggbn.org/schemas/ggbn/terms/', $term);
			$field->setAttribute('term', $term);
		}
		
		$count++;
	}		
	
	// images
  
	// extension
	$extension = $meta->createElement('extension');
	$extension->setAttribute('encoding', 'utf-8');
	$extension->setAttribute('fieldsTerminatedBy', '\t');
	$extension->setAttribute('linesTerminatedBy', '\n');
	$extension->setAttribute('fieldsEnclosedBy', '');
	$extension->setAttribute('ignoreHeaderLines', '1');
	$extension->setAttribute('rowType', 'http://rs.gbif.org/terms/1.0/Multimedia');
	$extension = $archive->appendChild($extension);

	// files
	$files = $extension->appendChild($meta->createElement('files'));
	$location = $files->appendChild($meta->createElement('location'));
	$location->appendChild($meta->createTextNode('images.tsv'));
	
	$coreid = $extension->appendChild($meta->createElement('coreid'));
	$coreid->setAttribute('index', '0');
	
	$field = $extension->appendChild($meta->createElement('field'));
	$field->setAttribute('index', '1');
	$field->setAttribute('term', 'http://purl.org/dc/terms/title');

	$field = $extension->appendChild($meta->createElement('field'));
	$field->setAttribute('index', '2');
	$field->setAttribute('term', 'http://purl.org/dc/terms/identifier');

	$field = $extension->appendChild($meta->createElement('field'));
	$field->setAttribute('index', '3');
	$field->setAttribute('term', 'http://purl.org/dc/terms/references');

	$field = $extension->appendChild($meta->createElement('field'));
	$field->setAttribute('index', '4');
	$field->setAttribute('term', 'http://purl.org/dc/terms/format');

	$field = $extension->appendChild($meta->createElement('field'));
	$field->setAttribute('index', '5');
	$field->setAttribute('term', 'http://purl.org/dc/terms/license');
	
	// defaults
	$field = $extension->appendChild($meta->createElement('field'));
	$field->setAttribute('term', 'http://purl.org/dc/terms/type');
	$field->setAttribute('default', 'StillImage');
	

	//echo $meta->saveXML();
	file_put_contents($filename, $meta->saveXML());
}




// project 
if (0)
{
	$url = 'http://www.boldsystems.org/index.php/API_Public/combined';
	
	// GBAP GenBank amphibians
	// FFMBH Spatial heterogeneity in the Mediterranean Biodiversity Hotspot affects barcoding accuracy of its freshwater fishes
	// INLE Barcoding of fish species from Inle Lake basin in Myanmar [INLE] see also https://dx.doi.org/10.3897%2FBDJ.4.e10539
	// DSCHA see http://dx.doi.org/10.1371/journal.pone.0099546
	// DS-LIFE Lizard Island fish see https://doi.org/10.3897/BDJ.5.e12409
	// DS-NGSTYPES DNA barcodes from century-old type specimens using next-generation sequencing
	// DS-TABAC Exploring Genetic Divergence in a Species-Rich Insect Genus Using 2790 DNA Barcodes https://dx.doi.org/10.1371%2Fjournal.pone.0138993

	// DS-INFOGCOL http://dx.doi.org/10.5883/DS-INFOGCOL Indonesia beetles
	// DS-IDBMTP
	
	// BIFA BIFA Barcoding Indonesian Fishes - part I. Rainbowfishes from Papua
	
	$parameters = array(
		'container' => 'FFMBH',
		'format' => 'tsv'
		);
}
	
// taxon 
if (1)
{
	$url = 'http://www.boldsystems.org/index.php/API_Public/combined';

	$parameters = array(
		//'taxon' => 'Agnotecous',
		//'taxon' => 'Limnonectes',
		//'taxon' => 'Xenopus',
		//'taxon' => 'Biomphalaria',
		//'taxon' => 'Pristimantis',
		//'taxon' => 'Oreobates',
		//'taxon' => 'Pingasa',
		//'taxon' => 'Camaenidae',
		//'taxon' => 'Stylommatophora',
		//'taxon' => 'Heraclides',
		//'taxon' => 'Trachycystis',
		
		//'taxon' => 'Pteropodidae',
		//'taxon' => 'Chiroptera',
		
		'taxon' => 'Molidae',
		
		'marker' => 'COI-5P',
		'format' => 'tsv'
		);	
}

// BIN
if (0)
{
	$url = 'http://www.boldsystems.org/index.php/API_Public/combined';

	$parameters = array(
		'bin' => 'BOLD:ADG2629', //'BOLD:ADD2285', //'BOLD:AAD6226',
		'marker' => 'COI-5P',
		'format' => 'tsv'
		);	
}

// geo
if (0)
{
	$url = 'http://www.boldsystems.org/index.php/API_Public/combined';

	$parameters = array(
		//'geo' => 'New Caledonia',
		'geo' => 'Sulawesi',
		'marker' => 'COI-5P',
		'format' => 'tsv'
		);	
}
	
$url .= '?' . http_build_query($parameters);

$cache_file_name = 'cache.json';

if (isset($parameters->container))
{
	$cache_file_name = $parameters->container . '.json';
}

if (isset($parameters->geo))
{
	$cache_file_name = $parameters->geo . '.json';
}

if (isset($parameters->bin))
{
	$cache_file_name = str_replace(':', '-', $parameters->bin) . '.json';
}



//echo $url . "\n";

write_meta();

// clean up any existing files
if (file_exists('occurrences.tsv'))
{
	unlink('occurrences.tsv');
}
if (file_exists('sequences.tsv'))
{
	unlink('sequences.tsv');
}
if (file_exists('images.tsv'))
{
	unlink('images.tsv');
}
if (file_exists('references.tsv'))
{
	unlink('references.tsv');
}

$zip = new ZipArchive();
$filename = "dwca.zip";

if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
    exit("cannot open <$filename>\n");
}

$zip->addEmptyDir('dwca');
$zip->addFile(dirname(__FILE__) . '/meta.xml', 'dwca/meta.xml');


$data = get($url);

if ($data)
{
	file_put_contents($cache_file_name, $data);

	$lines = explode("\n", $data);

	$keys = array();
	$terms_to_keys = array();
	
	$row_count = 0;
	
	foreach ($lines as $line)
	{
		if ($line == '') break;
		
		echo $line . "\n";
		//exit();
		
		$row = explode("\t", $line);
		
		if ($row_count == 0)
		{
			$keys = $row;
			
			//print_r($keys);
			
			$terms_to_keys = array_flip($keys);
			
			//print_r($terms_to_keys);
			//exit();
			
			// export column headings
			//echo join("\t", array_keys($key_to_dwca));
			//echo join("\t", $key_to_dwca);
			//echo "\n";
			
			file_put_contents('occurrences.tsv', join("\t", $key_to_dwca) . "\n");
			file_put_contents('sequences.tsv', join("\t", $key_to_ggbn) . "\n");
			
			file_put_contents('images.tsv', "dwc:materialSampleID\tdcterms:title\tdcterms:identifier\tdcterms:references\tdcterms:format\tdcterms:license\n");
		}
		else
		{			
			$occurrence = new stdclass;
			
			$occurrence->{'dwc:typeStatus'}  = '';
			
			$occurrence->{'dwc:dynamicProperties'}  = new stdclass;
			
			
			$sequence = new stdclass;
			
			$image_urls = array();
			$copyright_licenses = array();
			
			// get Darwin Core terms
			$n = count($row);
			for ($i = 0; $i < $n; $i++)
			{			
				if (trim($row[$i]) != '')
				{
					// Darwin Core terms
					if (isset($key_to_dwca[$keys[$i]]))
					{
						$occurrence->{$key_to_dwca[$keys[$i]]} = $row[$i];
					}
					
					// GGBN
					if (isset($key_to_ggbn[$keys[$i]]))
					{
						$sequence->{$key_to_ggbn[$keys[$i]]} = $row[$i];
					}
					
					if ($keys[$i] == 'image_urls')
					{
						$image_urls = explode("|", $row[$i]);
					}
					if ($keys[$i] == 'copyright_licenses')
					{
						$copyright_licenses  = explode("|", $row[$i]);
					}
					
					// handle type specimens
					if ($keys[$i] == 'voucher_type')
					{
						if (preg_match('/Type:\s*/', $row[$i]))
						{
							$occurrence->{'dwc:typeStatus'} = preg_replace('/Type:\s*/', '', $row[$i]);
						}
					}
					
				}
			}
			
			// dynamicProperties
			// special handling of BOLD classification so we preserve names and ids	
			// this enables us to add these values to any output, useful for linking externally		
			$ranks = array('phylum', 'class', 'order', 'family', 'subfamily', 'genus', 'species', 'subspecies');
			
			foreach ($ranks as $rank)
			{
				$k1 = $rank . '_name';
				$k2 = $rank . '_taxID';
				
				$v1 = $row[$terms_to_keys[$k1]];
				$v2 = $row[$terms_to_keys[$k2]];
								
				if ($v1 != '' && $v2 != '')
				{
					$occurrence->{'dwc:dynamicProperties'}->{$v1} = new stdclass;
					$occurrence->{'dwc:dynamicProperties'}->{$v1}->rank = $rank;
					$occurrence->{'dwc:dynamicProperties'}->{$v1}->id = $v2;
				}						
			}
			
			//print_r($occurrence);
			
			// dump as row of data for Darwin Core...
			// or can we just dump TSV file and use meta.xml cleverly?
			// having images, traces, etc. probably means we need to dump several rows of different types

			$values = array();
			foreach ($key_to_dwca as $k => $v)
			{
				if (isset($occurrence->{$v}))
				{
					switch ($v)
					{
						// dump dynamicProperties as JSON
						case 'dwc:dynamicProperties':
							$values[] = json_encode($occurrence->{$v});
							break;
						
					
						default:
							$values[] = $occurrence->{$v};
							break;
					}
				}
				else
				{
					$values[] = '';
				}
			}
			file_put_contents('occurrences.tsv', join("\t", $values) . "\n", FILE_APPEND);			

			// sequences?
			if (isset($sequence->{'ggbn:consensusSequence'}))
			{
				$values = array();
				foreach ($key_to_ggbn as $k => $v)
				{
					if (isset($sequence->{$v}))
					{
						$values[] = $sequence->{$v};
					}
					else
					{
						$values[] = '';
					}
				}
				file_put_contents('sequences.tsv', join("\t", $values) . "\n", FILE_APPEND);			
			}
			
			// images?	
			if (count($image_urls))
			{
				$n = count($image_urls);
				for ($i =0; $i < $n; $i++)
				{
					$media = new stdclass;
				
					$media->occurrenceID = $occurrence->{'dwc:materialSampleID'};
					$media->title = $occurrence->{'dwc:materialSampleID'};
			
					$media->identifier = $image_urls[$i];
					// some URLs have # symbol (why?)
					$media->identifier = str_replace('#', '%23', $media->identifier);
					// encode '+' otherwise GBIF breaks
					$media->identifier = str_replace('+', '%2B', $media->identifier);

					// encode '[' otherwise GBIF breaks
					$media->identifier = str_replace('[', '%5B', $media->identifier);
					// encode ']' otherwise GBIF breaks
					$media->identifier = str_replace(']', '%5D', $media->identifier);
				
					// URL of barcode page 
					$media->references =  'http://bins.boldsystems.org/index.php/Public_RecordView?processid=' . $occurrence->{'dwc:materialSampleID'};
				
					$media->format = '';
					if (preg_match('/\.(?<extension>[a-z]{3,4})$/i', $image_urls[$i], $m))
					{
						switch (strtolower($m['extension']))
						{
							case 'gif':
								$media->format = 'image/gif';
								break;
							case 'jpg':
							case 'jpeg':
								$media->format = 'image/jpeg';
								break;
							case 'png':
								$media->format = 'image/png';
								break;
							case 'tif':
							case 'tiff':
								$media->format = 'image/tiff';
								break;
							default:
								break;
						}
					}
					$media->license = $copyright_licenses[$i]; //  dcterms.license
				
					// Convert to URL if possible
					switch ($media->license)
					{
						case 'CreativeCommons - Attribution':
							$media->license = 'https://creativecommons.org/licenses/by/3.0/';
							break;
						
						default:
							break;
					}
				
					$values = array();
					foreach ($media as $k => $v)
					{
						$values[] = $v;
					}

					//echo join("\t", $values) . "\n";
					file_put_contents('images.tsv', join("\t", $values) . "\n", FILE_APPEND);
				}								
			
			}			
					
		}
		$row_count++;
	}
}

$zip->addFile(dirname(__FILE__) . '/occurrences.tsv', 'dwca/occurrences.tsv');
$zip->addFile(dirname(__FILE__) . '/sequences.tsv', 'dwca/sequences.tsv');
$zip->addFile(dirname(__FILE__) . '/images.tsv', 'dwca/images.tsv');
$zip->close();


?>
