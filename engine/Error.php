<?php
	// Author: Jakub Macek, CZ; Copyright: Poski.com s.r.o.; Code is taken from comments on php.net and slightly altered. Copying allowed.

	function my_error_handler_arg($arg)
	{
		switch (strtolower(gettype($arg)))
		{
			case 'string':
				$result = '"' . U::cut(str_replace(array("\n"), array(''), $arg), 200) . '"';
				break;
			case 'boolean':
				$result = var_export($arg, true);
				break;
			case 'object':
				$result =  'object(' . get_class($arg) . ')';
				break;
			case 'array':
				$result = 'array(' . count($arg) . ')';
				break;
			case 'resource':
				$result = 'resource(' . get_resource_type($arg) . ')';
				break;
			default:
				$result = var_export($arg, true);
				break;
		}
		return $result;
	}

	function error_print_backtrace($development = false)
	{
		ob_start();
		if(function_exists('debug_backtrace')){
			$basepath = $GLOBALS["site"]["base"][0];
			$backtrace = debug_backtrace();
			array_shift($backtrace);
			foreach($backtrace as $i=>$l)
			{
				if (isset($l['class']))
					echo "[$i] in function <b>{$l['class']}{$l['type']}{$l['function']}(</b>";
				else
					echo "[$i] in function <b>{$l['function']}(</b>";
				if (!empty($l['args']))
				{
					$separator = '';
					if ($development)
						foreach($l['args'] as $arg)
						{
							echo $separator . my_error_handler_arg($arg);
							$separator = ', ';
						}
				}
				echo "<b>)</b>";
				if ($development)
				{
					if (isset($l['file']))
					{
						$file = str_replace($basepath, "", $l['file']);
						$file = str_replace("/var/www/vhosts/poski.cz/httpdocs/engine/", "~/", $l['file']);
						echo " in <b>$file</b>";
					}
					if (isset($l['line'])) echo " on line <b>{$l['line']}</b>";
				}
				echo "\n";
			}
		}
		return ob_get_clean();
	}
	
	function my_error_handler($errno, $errstr, $errfile, $errline){
		$errno = $errno & error_reporting();
		if($errno == 0) return;
		if(!defined('E_STRICT'))            define('E_STRICT', 2048);
		if(!defined('E_RECOVERABLE_ERROR')) define('E_RECOVERABLE_ERROR', 4096);
		echo "\n<pre><b>";
		switch($errno){
			case E_ERROR:               echo "Error";                  break;
			case E_WARNING:             echo "Warning";                break;
			case E_PARSE:               echo "Parse Error";            break;
			case E_NOTICE:              echo "Notice";                 break;
			case E_CORE_ERROR:          echo "Core Error";             break;
			case E_CORE_WARNING:        echo "Core Warning";           break;
			case E_COMPILE_ERROR:       echo "Compile Error";          break;
			case E_COMPILE_WARNING:     echo "Compile Warning";        break;
			case E_USER_ERROR:          echo "User Error";             break;
			case E_USER_WARNING:        echo "User Warning";           break;
			case E_USER_NOTICE:         echo "User Notice";            break;
			case E_STRICT:              echo "Strict Notice";          break;
			case E_RECOVERABLE_ERROR:   echo "Recoverable Error";      break;
			default:                    echo "Unknown error ($errno)"; break;
		}
		$basepath = $GLOBALS["site"]["base"][0];
		$errfile = str_replace($basepath, "", $errfile);
		echo ":</b> <i>$errstr</i> in <b>$errfile</b> on line <b>$errline</b>\n";
		echo error_print_backtrace($GLOBALS['site']['development']);
		echo "</pre>\n";
		if(isset($GLOBALS['error_fatal'])){
			if($GLOBALS['error_fatal'] & $errno) die('fatal');
		}

		return true;
	}

	function error_fatal($mask = NULL){
		if(!is_null($mask)){
			$GLOBALS['error_fatal'] = $mask;
		}elseif(!isset($GLOBALS['die_on'])){
			$GLOBALS['error_fatal'] = 0;
		}
		return $GLOBALS['error_fatal'];
	}

	set_error_handler('my_error_handler');
	error_fatal(E_ERROR || E_WARNING);

	function error($message = '', $level = E_USER_ERROR)
	{
		//echo '<div style="background: white; border: 2px solid red; color: black;">';
		//echo '<pre>' . "\n"; debug_print_backtrace(); echo '</pre>' . "\n";
		trigger_error($message, $level);
		//echo '</div>';
	}

	function death($message)
	{
		trigger_error($message, E_USER_ERROR);
		die();
	}

	function dump($var, $label = null, $echo = true, $html = true)
	{
		$label = ($label === null) ? '' : rtrim($label) . ' ' . PHP_EOL;
		ob_start();
		var_dump($var);
		$output = ob_get_clean();
		$output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
		if ($html)
			$output = PHP_EOL . '<!--dump--><pre>' . PHP_EOL . ($label ? ('<strong>' . $label . '</strong>') : '') . htmlspecialchars($output, ENT_QUOTES) . '</pre><!--dump-->';
		else
			$output = PHP_EOL . $label . PHP_EOL . $output . PHP_EOL;
		if ($echo)
			echo $output;
		return $output;
	}

	function fbo($object)
	{
		fb(dump($object, null, false, false));
	}
?>