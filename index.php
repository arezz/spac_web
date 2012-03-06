<?php
	$GLOBALS['site']['path']					= '/';
	$GLOBALS['site']['locales']					= array('cs');
	$GLOBALS['site']['dsn'] 					= 'mysql://spac-os_cz:4LMxrHBD@localhost/spac-os_cz';
	$GLOBALS['site']['prefix'] 					= '';
	$GLOBALS['site']['development']				= false;
	require_once('bootstrap.php');
	require_once('Core.php');
	
	function _mistrovstvi_cr_modification($object)
	{
		$object->define('attachment', Field::TYPE_FILE, 255, '');
	}
	
	$GLOBALS['settings']['_mistrovstvi_cr']['object-options']['modifications'] = array('_mistrovstvi_cr_modification');
	
	$GLOBALS['settings']['pages_simple']['pages'] = array(
		'dalsi-zavod',
	);
	
	Core::process(0);
	require_once('web.php');
	Core::process(1);
	Core::module('pages_simple');
	Core::module('news');
	Core::module('_mistrovstvi_cr', 'news');
	Core::module('gallery');
	Core::module('_kalendar');
	Core::module('_poradi');
	Core::process(2);
	Core::process(3);
	Core::process(4);
	Core::process(5);
?>