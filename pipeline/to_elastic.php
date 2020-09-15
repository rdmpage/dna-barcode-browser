<?php

error_reporting(E_ALL ^ E_DEPRECATED);

require_once (dirname(__FILE__) . '/adodb5/adodb.inc.php');
require_once (dirname(__FILE__) . '/elastic.php');

// Connect
$db = NewADOConnection('mysqli');
$db->Connect("localhost", 
	'root' , '' , 'challenge2020');

// Ensure fields are (only) indexed by column name
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$db->EXECUTE("set names 'utf8'"); 


$row_count = 0;

$filename = "ids_view.tsv";

$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$line = trim(fgets($file_handle));
		
	$row = explode("\t",$line);
	
	$go = is_array($row) && count($row) > 0;
	
	if ($go)
	{
		$id = trim($row[0]);
		
		$obj = new stdclass;
		
		$sql = 'SELECT * FROM dwca WHERE id= "' . $id . '"';

		$result = $db->Execute($sql);
		if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);

		while (!$result->EOF) 
		{
			$type = $result->fields['type'];

			$data = json_decode($result->fields['data']);
			
			foreach ($data as $k => $v)
			{
				switch ($k)
				{
					case 'type':
						// eat
						break;
						
					default:				
						$obj->{$k} = $v;
						break;
				}
			}
			$result->MoveNext();

		}	
		
		//print_r($obj);	
		
		if (isset($obj->id))
		{
		
			if (isset($obj->path))
			{
				unset($obj->path);
			}
		
			// for small cases we can upload this to Elastic directly...
			$elastic_doc = new stdclass;
			$elastic_doc->doc = $obj;
			$elastic_doc->doc_as_upsert = true;
		
			//print_r($elastic_doc);
		
			$elastic->send('POST',  '_doc/' . urlencode($elastic_doc->doc->id). '/_update', json_encode($elastic_doc));					
			
			echo "\n\n";
		}		
		

	}	
	
	// Give server a break every 100 items
	if (($row_count++ % 100) == 0)
	{
		$rand = rand(1000000, 3000000);
		echo "\n-- ...sleeping for " . round(($rand / 1000000),2) . ' seconds' . "\n\n";
		usleep($rand);
	}
	
	
	
}	


