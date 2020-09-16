<?php

error_reporting(E_ALL);

global $config;

// Date timezone--------------------------------------------------------------------------
date_default_timezone_set('UTC');


// Hosting--------------------------------------------------------------------------------

$site = 'local';
$site = 'heroku';

switch ($site)
{
	case 'heroku':
		// Server-------------------------------------------------------------------------
		$config['web_server']	= 'https://xxx.herokuapp.com'; 
		$config['site_name']	= 'DNA Barcode Browser';

		// Files--------------------------------------------------------------------------
		$config['web_dir']		= dirname(__FILE__);
		$config['web_root']		= '/';		
		break;

	case 'local':
	default:
		// Server-------------------------------------------------------------------------
		$config['web_server']	= 'http://localhost'; 
		$config['site_name']	= 'DNA Barcode Browser';

		// Files--------------------------------------------------------------------------
		$config['web_dir']		= dirname(__FILE__);
		$config['web_root']		= '/~rpage/dna-barcode-browser/';
		break;
}

// Environment----------------------------------------------------------------------------
// In development this is a PHP file that is in .gitignore, when deployed these parameters
// will be set on the server
if (file_exists(dirname(__FILE__) . '/env.php'))
{
	include 'env.php';
}

$config['platform'] = 'local';
$config['platform'] = 'cloud';

if ($config['platform'] == 'local')
{
		
	// Local Docker Elasticsearch version 6.8.0 http://localhost:32772
	$config['elastic_options'] = array(			
			'index' => 'dna',
			'protocol' => 'http',
			'host' => 'localhost',
			'port' => 32769
			);

}

if ($config['platform'] == 'cloud')
{

	// Bitnami
	$config['elastic_options'] = array(
			'index' 	=> 'dna',
			'protocol' 	=> 'http',
			'host' 		=> getenv('ELASTIC_HOST'),
			'port' 		=> getenv('ELASTIC_PORT'),
			'user' 		=> getenv('ELASTIC_USERNAME'),
			'password' 	=> getenv('ELASTIC_PASSWORD'),
			);
}



?>
