<?php


//----------------------------------------------------------------------------------------
// 
function api_get($url, $format='application/json')
{
	$data = '';
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: " . $format));

	$response = curl_exec($ch);
	if($response == FALSE) 
	{
		$errorText = curl_error($ch);
		curl_close($ch);
		die($errorText);
	}
	
	$info = curl_getinfo($ch);
	$http_code = $info['http_code'];
	
	if ($http_code == 200)
	{
		$data = $response;
	}
	
	curl_close($ch);
	
	return $data;
}

//----------------------------------------------------------------------------------------
function api_output($obj, $callback = '', $format='json', $status = 400)
{
	
	// $obj may be array (e.g., for citeproc)
	if (is_array($obj))
	{
		if (isset($obj['status']))
		{
			$status = $obj['status'];
		}
	}
	
	// $obj may be object
	if (is_object($obj))
	{
		if (isset($obj->status))
		{
			$status = $obj->status;
		}
	}

	switch ($status)
	{
		case 303:
			header('HTTP/1.1 303 See Other');
			break;

		case 404:
			header('HTTP/1.1 404 Not Found');
			break;
			
		case 410:
			header('HTTP/1.1 410 Gone');
			break;
			
		case 500:
			header('HTTP/1.1 500 Internal Server Error');
			break;
			 			
		default:
			break;
	}
	
	
	//header("Cache-control: max-age=3600");
	
	switch ($format)
	{
		case 'json':		
			header("Content-type: text/plain");
			if ($callback != '')
			{
				echo $callback . '(';
			}
			echo json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);		
			if ($callback != '')
			{
				echo ')';
			}
			break;
			
		case 'fasta':
			// https://github.com/eligrey/FileSaver.js/wiki/Saving-a-remote-file#using-http-header
			header("Content-type: application/octet-stream");
			header("Content-Disposition: attachment; filename=\"sequences.fasta\"; filename*=\"sequences.fasta\"");
			echo $obj;		
			break;
		
		case 'nexus':
			// https://github.com/eligrey/FileSaver.js/wiki/Saving-a-remote-file#using-http-header
			header("Content-type: application/octet-stream");
			header("Content-Disposition: attachment; filename=\"distances.nexus\"; filename*=\"distances.nex\"");
			echo $obj;		
			break;
			
		default:
			echo $obj;
			break;
	}
}

?>