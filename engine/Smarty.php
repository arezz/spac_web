<?php
	// Author: Jakub Macek, CZ; Copyright: Poski.com s.r.o.; Code is 80% my work. Rest is significantly altered code from original Smarty. Do not copy.

	class SmartyEx extends Smarty
	{
		public static			$globals							= array();
		public static			$config								= array();
		public 					$_return							= array();
		public 					$_base_vars							= array();
		public					$_cache_include_info				= null;

		public function get($key)
		{
			return $this->__get($key);
		}

		public function set($key, $value)
		{
			$this->__set($key, $value);
		}

		public function __get($key)
		{
			if (substr($key, 0, 1) == '~')
				return (isset($this->_tpl_vars[substr($key, 1)]) ? $this->_tpl_vars[substr($key, 1)] : null);
			else if (substr($key, 0, 2) == '__')
				return (isset($this->_tpl_vars[substr($key, 2)]) ? $this->_tpl_vars[substr($key, 2)] : null);
			else if (substr($key, 0, 1) == '#')
				return (isset($this->_config[0]['vars'][substr($key, 1)]) ? $this->_config[0]['vars'][substr($key, 1)] : null);
			else if (isset($this->$key))
				return $this->$key;
			else
				return null;
		}

		public function __set($key, $value)
		{
			if (substr($key, 0, 1) == '~')
				$this->_tpl_vars[substr($key, 1)] = $value;
			else if (substr($key, 0, 2) == '__')
				$this->_tpl_vars[substr($key, 2)] = $value;
			else if (substr($key, 0, 1) == '#')
				$this->_config[0]['vars'][substr($key, 1)] = $value;
			else if (isset($this->$key))
				$this->$key = $value;
		}

		public function prefilter($source, $smarty)
		{
			$source = str_replace('**}', '**}'.chr(255), $source);
			$source = str_replace('{**', chr(255).'{**', $source);
			$blocks = explode(chr(255), $source);
			$part = $this->__this->part;
			$write = true;
			$source = '';
			foreach ($blocks as $block)
			{
				if (substr($block, 0, 3) == '{**')
				{
					$block = substr($block, 4, strlen($block) - 8);
					if ($block == 'ENDBLOCK')
						$write = true;
					else if (substr($block, 0, 5) == 'BLOCK')
					{
						$temp = explode(' ', substr($block, 6));
						$write = (in_array($part, $temp));
					}
				}
				else if ($write)
					$source .= $block;
			}
			
			$source = preg_replace('~\$::([^\(\)]*\(((?>[^()]+)|(?R))*\))~', '$_SMARTY_GLOBAL_FUNCTION_->\1', $source);
			$source = preg_replace('~\$([\w\d]+?)::~', '$_SMARTY_STATIC_->\1->', $source);
			$source = preg_replace('~(\{[^\}]+)\[(["\'].*?)-(.*?["\'])\]~', '\1[\2__MINUS__\3]', $source);
			$source = str_replace(array('{php}<?php', '?>{/php}'), array('{php}', '{/php}'), $source);
			/*$source = str_replace(array('<??', '??>', '<?=', '=?>'), array('{php}', '{/php}', '{php} print(', '); {/php}'), $source);*/
			$source = str_replace(array('<?', '?>', '<?=', '=?>'), array('{php}', '{/php}', '{php} print(', '); {/php}'), $source);
			$source = str_replace("\r\n", "\n", $source);
			$source = preg_replace('~^\s+(\{/?[\w\$].*\})$~m', '\1', $source);
			return $source;
		}

		public function postfilter($source, $smarty)
		{
			$source = str_replace(
				array(
					'__MINUS__',
				),
				array(
					'-',
				),
				$source
			);
			$source = str_replace('$this->_tpl_vars[\'_SMARTY_GLOBAL_FUNCTION_\']->', '', $source);
			$source = preg_replace('~\$this->_tpl_vars\[\'_SMARTY_STATIC_\'\]->([\w\d]+)->~', '\1::', $source);
			//TODO tady menit h2, h3 ...
			return $source;
		}

		public function __construct()
		{
			parent::Smarty();

			$temp = array();
			foreach ($GLOBALS['site']['base'] as $path)
			{
				$temp[] = $path . $this->directory;
				$temp[] = $path;
			}
			$this->caching = 0;
			$this->template_dir = $temp;
			$this->config_dir = $temp;
			$this->compile_dir = $GLOBALS['site']['data'] . 'cache/';
			$this->cache_dir = $GLOBALS['site']['data'] . 'cache/';
			$this->plugins_dir = array(
				SMARTY_DIR . 'plugins-framework/',
				SMARTY_DIR . 'plugins-extra/',
				SMARTY_DIR . 'plugins/',
			);
			$this->php_handling = SMARTY_PHP_ALLOW;
			$this->register_resource('string', array($this, 'string_get_template', 'string_get_timestamp', 'string_get_secure_trusted', 'string_get_secure_trusted'));
			$this->register_prefilter(array($this, 'prefilter'));
			$this->register_postfilter(array($this, 'postfilter'));
			$this->register_function('cache', 'smarty_function_cache', false);
			if ($plugin_file = $this->_get_plugin_filepath('compiler', 'sub'))
				include_once($plugin_file);
			$this->register_compiler_function('subuse', 'smarty_compiler_subuse');
			$this->register_compiler_function('/sub', 'smarty_compiler_sub_close');
			$this->register_postfilter('smarty_postfilter_sub');
			//$this->compile_check = $GLOBALS['site']['development'];
			//$this->force_compile = $GLOBALS['site']['development'];

			foreach (self::$globals as $k => $v)
				$this->assign_by_ref($k, $v);
			foreach (self::$config as $k => $v)
				$this->{'#' . $k} = $v;
			
			//$this->__static = new SmartyStatic();
			$this->{'#path'} = PATH;
			$this->{'#lpath'} = LPATH;
			$this->{'#locale'} = $GLOBALS['page']['locale'];
			$this->assign_by_ref('G', $GLOBALS);
			$this->_base_vars = $this->_tpl_vars;
		}

		function _get_auto_filename($auto_base, $auto_source = null, $auto_id = null)
		{
			$_return = $auto_base;

			if ($auto_id !== null)
				$_return .= str_pad(U::removeJunk($auto_id), 32, '-') . '-' . md5($auto_id);

			if ($auto_source !== null)
			{
				$group = $this->__this->group;
				$part = $this->__this->part;
				if ($group)
					$_return .= '-' . $group . '-' . $part; 
				else
					$_return .= strtr($auto_source, ':/', '~~');
			}

			return $_return;
		}
		
		public function string_get_template($tpl_name, &$tpl_source, $smarty)
		{
			$tpl_source = $tpl_name;
			return true;
		}
		public function string_get_timestamp($tpl_name, &$tpl_timestamp, $smarty)
		{
			$tpl_timestamp = 2147483647;
			return true;
		}
		public function string_get_secure_trusted($tpl_name, $smarty)
		{
			return true;
		}

	}
	
	function smarty_function_cache($params, $smarty)
	{
		if (!isset($smarty->_cache_info['variables']))
			$smarty->_cache_info['variables'] = array();
			
		if (isset($params['variable']))
		{
			$v = $params['variable'];
			if (!isset($smarty->_cache_info['expires']))
				$smarty->_cache_info['variables'][$v] = eval('return ' . $v . ';');
			else
			{
				$value = $smarty->_cache_info['variables'][$v];
				eval($v . ' = $value;');
			}
		}
		else
		{
			$v = $params['name'];
			$get = $params['get'];
			$set = $params['set'];
			if (!isset($smarty->_cache_info['expires']))
				$smarty->_cache_info['variables'][$v] = eval($get);
			else
			{
				$value = $smarty->_cache_info['variables'][$v];
				eval($set);
			}
		}
	}
?>