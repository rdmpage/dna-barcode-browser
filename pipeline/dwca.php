<?php

//--------------------------------------------------------------------------------------------------
function uri_local_name($uri)
{
	$local = $uri;
	
	if (preg_match('/(?<prefix>(.*)[\/|#])(?<local>[A-Za-z_\.]+)$/', $uri, $matches))
	{
		$local = $matches['local'];
	}
	return $local;
}

//--------------------------------------------------------------------------------------------------
function uri_namespace($uri)
{
	$namespace = $uri;
	
	if (preg_match('/(?<prefix>(.*)[\/|#])(?<local>[A-Za-z_\.]+)$/', $uri, $matches))
	{
		$namespace = $matches['prefix'];
	}
	return $namespace;
}

//--------------------------------------------------------------------------------------------------
function data_display($data)
{
	if (isset($data->_id))
	{
		$filename = 'tmp/' . urlencode($data->_id) . '.json';
		file_put_contents($filename, json_format(json_encode($data)) );
	}
	else
	{
		// extension
		$filename = 'tmp/' . urlencode($data->_coreid) . '.json';
		if (file_exists($filename))
		{
		
			unset($data->_coreid);
			
			$json = file_get_contents($filename);
			$obj = json_decode($json);
			
			switch ($data->_type)
			{
				case "Description":
					if (!isset($obj->Description))
					{
						$obj->Description = array();
					}
					$obj->Description[] = $data;
					break;
			
				case "Distribution":
					if (!isset($obj->Distribution))
					{
						$obj->Distribution = array();
					}
					$obj->Distribution[] = $data;
					break;

				case "Document":
					if (!isset($obj->Document))
					{
						$obj->Document = array();
					}
					$obj->Document[] = $data;
					break;
			
				case "Identifier":
					if (!isset($obj->Identifier))
					{
						$obj->Identifier = array();
					}
					$obj->Identifier[] = $data;
					break;

				case "Identification":
					if (!isset($obj->Identification))
					{
						$obj->Identification = array();
					}
					$obj->Identification[] = $data;
					break;
					
				case "Image":
					if (!isset($obj->Image))
					{
						$obj->Image = array();
					}
					$obj->Image[] = $data;
					break;
					
				case "Media":
					if (!isset($obj->Media))
					{
						$obj->Media = array();
					}
					$obj->Media[] = $data;
					break;					

				case "Occurrence":
					if (!isset($obj->Occurrence))
					{
						$obj->Occurrence = array();
					}
					$obj->Occurrence[] = $data;
					break;

				case "Reference":
					if (!isset($obj->Reference))
					{
						$obj->Reference = array();
					}
					$obj->Reference[] = $data;
					break;
					
				case "SpeciesProfile":
					if (!isset($obj->SpeciesProfile))
					{
						$obj->SpeciesProfile = array();
					}
					$obj->SpeciesProfile[] = $data;
					break;	

				case "TypesAndSpecimen":
					if (!isset($obj->TypesAndSpecimen))
					{
						$obj->TypesAndSpecimen = array();
					}
					$obj->TypesAndSpecimen[] = $data;
					break;
					
				case 'VernacularName':
					// Many DWCA seem to have blank values for vernacular names
					if (isset($data->vernacularName))
					{
						if (!isset($obj->VernacularName))
						{
							$obj->VernacularName = array();
						}
						$obj->VernacularName[] = $data;
					}
					break;
					
				default:
					break;
			}
			file_put_contents($filename, json_format(json_encode($obj)) );
		}
	}		


	echo json_format(json_encode($data)) . "\n";
}


//--------------------------------------------------------------------------------------------------
// http://stackoverflow.com/a/5996888/9684
function translate_quoted($string) {
  $search  = array("\\t", "\\n", "\\r");
  $replace = array( "\t",  "\n",  "\r");
  return str_replace($search, $replace, $string);
}


//--------------------------------------------------------------------------------------------------
function is_empty_cell($value)
{
	$empty = false;
	
	if ($value == '') $empty = true;
	if ($value == '\N') $empty = true;
	
	return $empty;
}

//--------------------------------------------------------------------------------------------------
function parse_data($data, $callback_func = null)
{
	global $basedir;

	$filename = $basedir . $data->filename;
	
	$row_count = 0;
	
	$file = @fopen($filename, "r") or die("couldn't open $filename");
			
	$file_handle = fopen($filename, "r");
	while (!feof($file_handle)) 
	{
		$row = fgetcsv(
			$file_handle, 
			0, 
			translate_quoted($data->attributes['fieldsTerminatedBy']),
			(translate_quoted($data->attributes['fieldsEnclosedBy']) != '' ? translate_quoted($data->attributes['fieldsEnclosedBy']) : '"') 
			);

		$go = is_array($row);
				
		if ($go && ($row_count == 0) && ($data->attributes['ignoreHeaderLines'] == 1))
		{
			$go = false;
		}
		if ($go)
		{
			$obj = new stdclass;
			
			
			// Create a simpe key-value object with local identifiers 
			
			$column_count = 0;

			foreach ($row as $key => $value)
			{
				if (!is_empty_cell($value))
				{
					$value = trim($value);
					
					// Only interpret values that are listed in the metadata
					// (some files may ignore some columns)
					if (isset($data->keys[$column_count]))
					{					
						$k = $data->keys[$column_count];
					
						$obj->{$k} = $value;
						
						if (isset($data->id_column))
						{
							if ($column_count == $data->id_column)
							{
								if (!isset($obj->_id))
								{
									$obj->_id = $value;
								}
							}
						}
						if (isset($data->coreid_column))
						{
							if ($column_count == $data->coreid_column)
							{
								if (!isset($obj->_coreid))
								{
									$obj->_coreid = $value;
								}
							}
						}				
					}
				}
				$column_count++;
			}
			
			// add any defaults
			foreach ($data->defaults as $k => $v)
			{
				$obj->{$k} = $v;
			}
			
			// ensure object is typed
			if (!isset($obj->_type))
			{
				$obj->_type = uri_local_name($data->_type);
			}
						
			if ($callback_func)
			{
				$callback_func($obj);
			}
		}
		$row_count++;
		
		
		/*
		if ($row_count == 1000)
		{
			return;
		}
		*/
		
	}
	
	
}

//--------------------------------------------------------------------------------------------------
// Parse DWCA metadata for specified tag
function parse_meta($xpath, $tag, $callback_func = null)
{

	$nodeCollection = $xpath->query ($tag);
	foreach ($nodeCollection as $core)
	{		
		$data = new stdclass;
	
		// attributes of //core
		if ($core->hasAttributes()) 
		{ 
			$data->attributes = array();
			$attrs = $core->attributes; 
			
			foreach ($attrs as $i => $attr)
			{
				$data->attributes[$attr->name] = $attr->value; 
			}
		}
		
		//print_r($attributes);
		
		// file
		$files = $xpath->query ('dwc_text:files/dwc_text:location', $core);
		foreach ($files as $file)
		{		
			$data->filename = $file->firstChild->nodeValue;
		}
		
		// type
		$data->_type = 'unknown';
		if (isset($data->attributes['rowType']))
		{
			$data->_type = $data->attributes['rowType'];
		} 		
	
		$data->keys = array();
		$data->fields = array();
		$data->defaults = new stdclass;
		$data->namespaces = array();
		$data->coreid = 0;
	
		// id
		$ids = $xpath->query ('dwc_text:id', $core);
		foreach ($ids as $id)
		{		
			$attributes = array();
			$attrs = $id->attributes; 
			
			foreach ($attrs as $i => $attr)
			{
				$attributes[$attr->name] = $attr->value; 
			}
			
			if (isset($attributes['index']))
			{
				$data->fields[$attributes['index']] = 'id';
				$data->keys[$attributes['index']] = 'id';
				
				$data->id_column = $attributes['index'];
			}
		}
		
		// coreid (id used to crosslink tables)
		$ids = $xpath->query ('dwc_text:coreid', $core);
		foreach ($ids as $id)
		{		
			$attributes = array();
			$attrs = $id->attributes; 
			
			foreach ($attrs as $i => $attr)
			{
				$attributes[$attr->name] = $attr->value; 
			}
			
			if (isset($attributes['index']))
			{
				$data->fields[$attributes['index']] = 'coreid';
				$data->keys[$attributes['index']] = 'coreid';
				
				$data->coreid_column = $attributes['index'];
			}
		}
		
		// fields
		
		$data->context = new stdclass;
		
		$fields = $xpath->query ('dwc_text:field', $core);
		foreach ($fields as $field)
		{		
			// attributes
			if ($field->hasAttributes()) 
			{ 
				$attributes = array();
				$attrs = $field->attributes; 
				
				foreach ($attrs as $i => $attr)
				{
					$attributes[$attr->name] = $attr->value; 
				}
			}
			
			
			// listed values
			if (isset($attributes['index']) && isset($attributes['term']))
			{
				// grab simple key
				$simple_key = uri_local_name($attributes['term']);
				
				$data->keys[$attributes['index']]  = $simple_key;			
				$data->fields[$attributes['index']] = $attributes['term'];
				
				// build context
				$namespace = uri_namespace($attributes['term']);
				if (!isset($data->context->{$simple_key}))
				{
					$data->context->{$simple_key} = $attributes['term'];
				}
				if (!in_array($namespace, $data->namespaces))
				{
					$data->namespaces[] = $namespace;
				}
			}
			
			// default values (e.g., msw3)
			if (isset($attributes['default']) && isset($attributes['term']))
			{
				// simple key
				$k = uri_local_name($attributes['term']);
				$data->defaults->{$k} = $attributes['default'];
				
				// context
				if (!isset($data->context->{$k}))
				{
					$data->context->{$k} = $attributes['default'];
				}						
			}
			
						
			
		}
		
		//print_r($data);exit();
		
		// process file
		parse_data($data, $callback_func);
	}
}

//--------------------------------------------------------------------------------------------------
// Parse DWCA metadata for this archive
function parse_eml($xpath)
{	
	global $basedir;

	$nodeCollection = $xpath->query ('//dwc_text:archive/@metadata');
	foreach ($nodeCollection as $node)
	{		
		$eml_filename = $basedir . $node->firstChild->nodeValue;
		
		$xml = simplexml_load_file($eml_filename);
		$json = json_encode($xml);

		//echo json_format($json);
		
	}
}

?>
