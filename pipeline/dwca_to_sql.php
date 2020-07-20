<?php

// Import DwCA with sequences, simplify and send to SQL

require_once(dirname(__FILE__) . '/dwca.php');

//--------------------------------------------------------------------------------------------------
function data_store_sql($data)
{
	global $config;
	
	if (isset($data->_id))
	{
		switch ($data->_type)
		{
			// Occurrences and Material samples
			case "MaterialSample":
			case "Occurrence":	

				// For now just accept all fields
				
				$obj = new stdclass;
				
				$obj->id = $data->_id;
				$obj->type = $data->_type;

				if (isset($data->taxonID))
				{
					$obj->taxonID = $data->taxonID;
					$obj->taxonID = str_replace('http://www.boldsystems.org/index.php/Public_BarcodeCluster?clusteruri=', '', $obj->taxonID);
				}
				
				if (isset($data->dynamicProperties))
				{
					$obj->dynamicProperties = json_decode($data->dynamicProperties);
				}				
								
				$obj->lineage = array();

				if (isset($data->phylum))
				{
					$obj->lineage[] = $data->phylum;
				}	
													
				if (isset($data->class))
				{
					$obj->lineage[] = $data->class;
				}	
													
				if (isset($data->order))
				{
					$obj->lineage[] = $data->order;
				}	
													
				if (isset($data->family))
				{
					$obj->lineage[] = $data->family;
				}	
													
				if (isset($data->genus))
				{
					$obj->lineage[] = $data->genus;
				}	
													
				if (isset($data->species))
				{
					$obj->lineage[] = $data->species;
				}																				
														
				if (isset($data->scientificName))
				{
					$obj->scientificName = $data->scientificName;
					$obj->scientificName = preg_replace('/\s+\(BOLD:.*\)$/', '', $obj->scientificName);
					//$obj->lineage[] = $obj->scientificName;
				}	
				
				// lineage is an array of tags, we also want a path string so we can
				// do path searches
				
				$obj->path = join('/', $obj->lineage);
					
				
				// Genbank?
				/*
				if (isset($data->associatedSequences))
				{
					$obj->associatedSequences = $data->associatedSequences;
				}
				*/
				
				// GeoJSON
				if (isset($data->decimalLatitude) && isset($data->decimalLongitude))
				{
					$obj->geometry = new stdclass;
					$obj->geometry->type = "Point";
					$obj->geometry->coordinates = array(
						(float) $data->decimalLongitude,
						(float) $data->decimalLatitude
						);			
				}

				if (1)
				{
					//print_r($data);
				
					//print_r($obj);
					
					$keys = array();
					$values = array();
					
					$keys[] = "id";
					$values[] = '"' . addcslashes($obj->id, '"') . '"';

					$keys[] = "type";
					$values[] = '"' . addcslashes($obj->type, '"') . '"';

					$keys[] = "data";
					$values[] = '"' . addcslashes(json_encode($obj), '"') . '"';
					
					echo "REPLACE INTO dwca(" . join(",", $keys) . ") VALUES (" . join(",", $values) . ");\n";

				}
				
				break;
				
			default:
				break;
		}
	}
	else
	{
		// extension, so add to existing data object
		switch ($data->_type)
		{
			case 'Amplification':
				$obj = new stdclass;
				
				$obj->id = $data->_coreid;
				$obj->type = $data->_type;
				
				if (isset($data->geneticAccessionNumber))
				{
					$obj->geneticAccessionNumber = $data->geneticAccessionNumber;
				}
				if (isset($data->marker))
				{
					$obj->marker = $data->marker;
				}
				if (isset($data->consensusSequence))
				{
					$obj->consensusSequence = $data->consensusSequence;
				}
				
				if (1)
				{
					//print_r($obj);					
					
					$keys = array();
					$values = array();
					
					$keys[] = "id";
					$values[] = '"' . addcslashes($obj->id, '"') . '"';

					$keys[] = "type";
					$values[] = '"' . addcslashes($obj->type, '"') . '"';
				
					$keys[] = "data";
					$values[] = '"' . addcslashes(json_encode($obj), '"') . '"';
						
					echo "REPLACE INTO dwca(" . join(",", $keys) . ") VALUES (" . join(",", $values) . ");\n";
					
				}
				
				break;
				
			case 'Multimedia':
				$obj = new stdclass;
				
				$obj->id = $data->_coreid;
				$obj->type = $data->_type;
				
				$obj->image = $data->identifier;
				
				if (1)
				{
					// print_r($obj);
										
					$keys = array();
					$values = array();
					
					$keys[] = "id";
					$values[] = '"' . addcslashes($obj->id, '"') . '"';

					$keys[] = "type";
					$values[] = '"' . addcslashes($obj->type, '"') . '"';
				
					$keys[] = "data";
					$values[] = '"' . addcslashes(json_encode($obj), '"') . '"';
						
					echo "REPLACE INTO dwca(" . join(",", $keys) . ") VALUES (" . join(",", $values) . ");\n";
					
				}
				break;
				
				
			default:
				break;
		}
	}
}


//--------------------------------------------------------------------------------------------------

// Archive to parse

if (1)
{
	$archive = "dwca.zip";

	$basedir = dirname(__FILE__) . '/tmp/';

	$zip = new ZipArchive;
	if ($zip->open($archive) === TRUE) {
		$zip->extractTo($basedir);
		$zip->close();
	}

	$basedir .= 'dwca/';
}

// Indobiosys
if (0)
{
	$basedir .= 'Indobiosys/';
}

// iBOL
if (0)
{
	$basedir .= '/Volumes/Samsung_T5/ibol/';
}

// meta.xml tells us how to interpret archive
$filename = $basedir . 'meta.xml';
$xml = file_get_contents($filename);

// Read details of source file(s) and extract data
$dom= new DOMDocument;
$dom->loadXML($xml);
$xpath = new DOMXPath($dom);
$xpath->registerNamespace('dwc_text', 'http://rs.tdwg.org/dwc/text/');


// set a custom function to determine how we post-process the data
parse_meta($xpath, '//dwc_text:core', 'data_store_sql');
parse_meta($xpath, '//dwc_text:extension', 'data_store_sql');


?>
