<?php
	ini_set('display_errors', true);
	error_reporting(E_ALL);
/****************************************************************************************************************/
	if (@$GLOBALS['site']['name'] === null)
		$GLOBALS['site']['name']				= 'TEST';
	if (@$GLOBALS['site']['title'] === null)
		$GLOBALS['site']['title']				= 'TEST';
	if (@$GLOBALS['site']['email'] === null)
		$GLOBALS['site']['email']				= 'jakub@poski.com';
	if (@$GLOBALS['site']['locales'] === null)
		$GLOBALS['site']['locales']				= array('cs');
	if (@$GLOBALS['site']['dsn'] === null)
		$GLOBALS['site']['dsn'] 				= '';
	if (@$GLOBALS['site']['prefix'] === null)
		$GLOBALS['site']['prefix'] 				= '';
	if (@$GLOBALS['site']['flags'] === null)
		$GLOBALS['site']['flags'] 				= array();
	if (@$GLOBALS['site']['development'] === null)
		$GLOBALS['site']['development']			= true;
//	$GLOBALS['site']['autologin']				= 'administrator';
/****************************************************************************************************************/
	$GLOBALS['site']['server']					= strtolower((isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $_SERVER['HTTP_HOST']));
	preg_match('~^(.*)\.([a-zA-Z0-9_-]+\.\w+)(:\d+)?$~', $GLOBALS['site']['server'], $server_matches);
	$GLOBALS['site']['domain']					= $server_matches[2];
	$GLOBALS['site']['virtual-domain']			= $GLOBALS['site']['domain'];
	if (@$GLOBALS['site']['path'] === null)
		$GLOBALS['site']['path']				= str_replace(strtr($_SERVER['DOCUMENT_ROOT'], '\\', '/'), '', strtr(dirname(__FILE__), '\\', '/')) . '/';
	$GLOBALS['site']['url']						= (($_SERVER['SERVER_PORT'] == '443') ? 'https://' : 'http://') . $GLOBALS['site']['server'] . $GLOBALS['site']['path'];
	$GLOBALS['site']['base']					= array(strtr(substr(__FILE__, 0, strlen(__FILE__) - strlen('bootstrap.php')), '\\', '/'));
	$GLOBALS['site']['data']					= $GLOBALS['site']['base'][0] . 'data/';

	$include_paths = array();
	$include_paths[]							= $GLOBALS['site']['base'][0];
	$include_paths[]							= $GLOBALS['site']['base'][0] . 'engine/';
	$include_paths[]							= $GLOBALS['site']['base'][0] . 'third-party/';
	$include_paths[]							= $GLOBALS['site']['base'][0] . 'third-party/PEAR/';

	if ($GLOBALS['site']['development'])
	{
		if ((strpos($GLOBALS['site']['dsn'], 'poski_cz') !== false) && ($GLOBALS['site']['domain'] != 'poski.cz'))
			die('database access error');
		if (($GLOBALS['site']['server'] == 'www.poski.cz') || ($GLOBALS['site']['server'] == 'poski.cz'))
			$GLOBALS['site']['prefix']				= '0__';
		else if (preg_match('/^.+\.poski\.cz$/', $GLOBALS['site']['server']))
		{
			$GLOBALS['site']['virtual-domain']		= $server_matches[1];
			$GLOBALS['site']['prefix']				= str_replace(array('test/', '', '/', '.', '-'), array('', '', '_', '', ''), $GLOBALS['site']['virtual-domain'] . rtrim($GLOBALS['site']['path'], '/')) . '__';
			$GLOBALS['site']['title']				.= ' (' . $GLOBALS['site']['virtual-domain'] . ')';
			if ($temp = trim($GLOBALS['site']['path'], '/'))
				$GLOBALS['site']['title']			.= ' (' . $temp . ')';
			if (strpos(ini_get('open_basedir'), 'poski.cz:') !== false)
			{
				$temp 									= '/var/www/vhosts/poski.cz/httpdocs/';
				$GLOBALS['site']['base'][]				= $temp;
				$include_paths[]						= $temp;
				$include_paths[]						= $temp . 'engine/';
				$include_paths[]						= $temp . 'third-party/';
				$include_paths[]						= $temp . 'third-party/PEAR/';
			}
		}
	}

	session_name('PHPSESSID-' . strtr($GLOBALS['site']['virtual-domain'], '.', '_'));
	session_set_cookie_params(4*60*60, $GLOBALS['site']['path'], '.'.$GLOBALS['site']['domain']);
	set_include_path(implode(PATH_SEPARATOR, $include_paths) . PATH_SEPARATOR . ini_get('include_path'));
	define('BASE', $GLOBALS['site']['base'][0]);
/****************************************************************************************************************/
	$GLOBALS['site']['developer']				= array(
		'name_first'				=> 'Jakub',
		'name_last'					=> 'Macek',
		'email'						=> 'jakub@poski.com',
		'im'						=> '',
		'test-domain'				=> 'poski.cz',
	);
	$GLOBALS['site']['test-data-url']			= 'http://www.poski.cz/.test-data/';
/****************************************************************************************************************/
	define('LOG_TIME',							1);
	define('LOG_INCLUDE',						2);
	define('LOG_PAGE',							4);
	define('LOG_SITE',							8);
	define('LOG_GET',							16);
	define('LOG_POST',							32);
	define('LOG_COOKIE',						64);
	define('LOG_SERVER',						128);
	define('LOG_SESSION',						256);
	define('LOG_TEMPLATES',						512);
	define('LOG_CORE_MODULE',					1024);
	define('LOG_CORE_PROCESS',					2048);
	define('LOG_MODULE_CONTRUCT',				4096);
	define('LOG_MODULE_INITIALIZE',				8192);
	define('LOG_MODULE_INDEX',					16384);
	define('LOG_MODULE_PROCESS',				32768);
	define('LOG_FIELD',							65536);
	define('LOG_STORAGE',						131072);
	define('LOG_STORAGE_FIREPHP',				262144);
/****************************************************************************************************************/
	$GLOBALS['page']['title']					= '';
	$GLOBALS['page']['h2']						= '';
	$GLOBALS['page']['title-action']			= null;
	$GLOBALS['page']['path']					= array();
	$GLOBALS['page']['keywords']				= '';
	$GLOBALS['page']['description']				= '';
	$GLOBALS['page']['head']					= '';
	$GLOBALS['page']['content']					= '';
	$GLOBALS['page']['panel']					= (isset($_REQUEST['page_panel']) ? $_REQUEST['page_panel'] : null);
	$GLOBALS['page']['ppanel']					= (isset($_REQUEST['page_ppanel']) ? $_REQUEST['page_ppanel'] : null);
	$GLOBALS['page']['debug']					= false;
	$GLOBALS['page']['locale']					= null;
	$GLOBALS['page']['locale-url']				= '';
	$GLOBALS['page']['administration']			= false;
	$GLOBALS['page']['location']				= null;
	$GLOBALS['page']['directory']				= null;
	$GLOBALS['page']['file']					= null;
	$GLOBALS['page']['log']						= LOG_TIME | LOG_PAGE | LOG_CORE_MODULE | LOG_STORAGE;
	$GLOBALS['page']['flags']					= array();
	$GLOBALS['page']['simple']					= (isset($_SERVER['REDIRECT_page_simple']) ? ((bool) $_SERVER['REDIRECT_page_simple']) : false);
	$GLOBALS['page']['layout']					= 'default';
	$GLOBALS['page']['viewstate']				= (isset($_REQUEST['page_viewstate']) ? $_REQUEST['page_viewstate'] : null);
/****************************************************************************************************************/
	$GLOBALS['invocation']						= null;
	$GLOBALS['result']							= null;
	$GLOBALS['D']								= array();
	$GLOBALS['R']								= array();
/****************************************************************************************************************/
	$GLOBALS['settings'] = array('core' => array());
	if (is_readable($GLOBALS['site']['data'] . 'settings.php'))
		include_once($GLOBALS['site']['data'] . 'settings.php');

	$temp = strpos($_SERVER['REQUEST_URI'], '?');
	$_SERVER['REQUEST_PATH'] = (($temp === false) ? $_SERVER['REQUEST_URI'] : substr($_SERVER['REQUEST_URI'], 0, $temp));
	$_SERVER['REQUEST_PATH_RELATIVE'] = substr($_SERVER['REQUEST_PATH'], strlen($GLOBALS['site']['path']));
	$GLOBALS['page']['uri'] = /*strtolower*/($_SERVER['REQUEST_PATH_RELATIVE']);
	$GLOBALS['page']['url'] = $GLOBALS['page']['uri'];

	if (strpos($GLOBALS['page']['url'], 'data/') === false)
	{
		while (preg_match('~^(.*)\.([a-zA-Z_-]+)\.(.*)$~', $GLOBALS['page']['url'], $matches))
		{
			$GLOBALS['page']['url'] = $matches[1];
			$_REQUEST[$matches[2]] = $matches[3];
			$_GET[$matches[2]] = $matches[3];
		}
	}

	list($GLOBALS['site']['locale-default']) = array_slice($GLOBALS['site']['locales'], 0, 1);
	if ($GLOBALS['page']['locale'] === null)
		$GLOBALS['page']['locale'] = $GLOBALS['site']['locale-default'] ;
	$GLOBALS['page']['URL'] = $GLOBALS['page']['url'];
	if (substr($GLOBALS['page']['url'], 2, 1) == '/')
	{
		$temp = substr($GLOBALS['page']['url'], 0, 2);
		if (in_array($temp, $GLOBALS['site']['locales']))
		{
			$GLOBALS['page']['locale'] = $temp;
			$GLOBALS['page']['URL'] = (string) substr($GLOBALS['page']['url'], 3);
		}
	}
	if (in_array($temp = @$_REQUEST['page_locale'], $GLOBALS['site']['locales']))
		$GLOBALS['page']['locale'] = $temp;
	if ($GLOBALS['page']['locale'] != $GLOBALS['site']['locale-default'] )
		$GLOBALS['page']['locale-url'] = $GLOBALS['page']['locale'] . '/';

	define('L', $GLOBALS['page']['locale']);
	define('LL', '_' . L);
	define('PATH', $GLOBALS['site']['path']);
	define('LPATH', $GLOBALS['site']['path'] . $GLOBALS['page']['locale-url']);
/****************************************************************************************************************/
	if (/*$GLOBALS['site']['development'] && */(@$_SERVER['REDIRECT_page_location'] == '300'))
	{
		foreach ($GLOBALS['site']['base'] as $base)
			if (is_readable($file = ($base . $_SERVER['REQUEST_PATH_RELATIVE'])))
			{
				$dir = dirname($file);
				chdir($dir);
				include($file);
				die();
			}
		die('300 redirect: file not found');
	}

	function __autoload($class)
	{
		$file = str_replace('_', '/', $class) . '.php';
		@include_once ($file);
	}
?>