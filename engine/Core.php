<?php
	// Author: Jakub Macek, CZ; Copyright: Poski.com s.r.o.; Code is 100% my work. Do not copy.

	require_once('Error.php');
	require_once('Utilities.php');
	require_once('third-party/fb.php');

	$GLOBALS['page']['log-file'] = $_SERVER['REMOTE_ADDR'] . '-' . date('Ymd-His');
	if (file_exists($file = ($GLOBALS['site']['data'] . $GLOBALS['page']['log-file'])))
		unlink($file);
	Log::$start = Log::$time = microtime(true);
	if ($GLOBALS['page']['log'] & LOG_TIME)
		U::log('core', 'time', 'start at ' . date('Y-m-d H:i:s'));

	/****************************************************************************************************************/

	class Cache
	{
		public static			$templates							= array();
		public static			$container							= array();

		public static function get()
		{
			$args = func_get_args();
			$id = implode(chr(254), $args);
			return (isset(self::$container[$id]) ? self::$container[$id] : null);
		}

		public static function set()
		{
			$args = func_get_args();
			$value = array_shift($args);
			$id = implode(chr(254), $args);
			self::$container[$id] = $value;
		}

		public static function pget()
		{
			$args = func_get_args();
			$id = implode('$', $args);
			$file = $GLOBALS['site']['data'] . 'cache/[cache]' . $id;
			return (is_readable($file) ? unserialize(file_get_contents($file)) : null);
		}

		public static function pset()
		{
			$args = func_get_args();
			$value = array_shift($args);
			$id = implode('$', $args);
			$file = $GLOBALS['site']['data'] . 'cache/[cache]' . $id;
			file_put_contents($file, serialize($value));
		}

		public static function tset($file, $ttl, $id = '')
		{
			self::$templates[$file] = array(
				'file' => $file,
				'ttl' => $ttl,
				'id' => $id,
			);
		}

		public static function tget($file)
		{
			return (isset(self::$templates[$file]) ? self::$templates[$file] : null );
		}
	}

	class Event
	{
		public static			$_									= array();

		public					$id									= '';
		public					$callbacks							= array();
		public					$options							= array();
		public					$result								= array();

		public static function invoke($id, $options = null)
		{
			if (!is_array($id))
				$id = array($id);
			foreach ($id as $temp)
				self::e($temp)->call($options);
		}

		public static function e($id)
		{
			if (!isset(self::$_[$id]))
				self::$_[$id] = new Event($id);
			return self::$_[$id];
		}

		public function __construct($id, $options = array())
		{
			$this->id = $id;
			$this->options = $options;
		}

		public function set($key, $value = null)
		{
			if ($value === null)
				unset($this->options[$key]);
			else
				$this->options[$key] = $value;
		}

		public function get($key, $default = null)
		{
			return isset($this->options[$key]) ? $this->options[$key] : $default;
		}

		public function add($callback, $order = false)
		{
			if ($order)
				$this->callbacks[$order] = $callback;
			else
				$this->callbacks[] = $callback;
			ksort($this->callbacks);
		}

		public function remove($callback)
		{
			foreach ($this->callbacks as $k => $v)
				if ($callback == $v)
					unset($this->callbacks[$k]);
			ksort($this->callbacks);
		}

		public function call($options = array(), $last = false)
		{
			$temp = $this->options;
			$this->options = array_merge($this->options, $options);
			$this->result = array();
			$last_result = null;
			foreach ($this->callbacks as $k => $v)
				$this->result[$k] = $last_result = call_user_func($v, $this, $last_result);
			$this->options = $temp;
			return ($last) ? $last_result : $this->result;
		}
	}

	class Locale
	{
		public static			$override							= false;
		public static			$current							= array();
		public static			$container							= array();
		public static			$module								= '';

		public static function initialize()
		{
			self::$current =& self::$container[$GLOBALS['page']['locale']];
			foreach ($GLOBALS['site']['locales'] as $locale)
				if ($locale != $GLOBALS['page']['locale'])
					self::$container[$locale] = array();
		}

		public static function module($module, $locale = null)
		{
			if ($locale === null)
				$locale = $GLOBALS['page']['locale'];
			if (!self::$override && !isset(self::$container[$locale][$module]))
			{
				$strings = array();
				foreach (array('%2$s', '%2$s.override', '%s/%s', '%s/%s.override') as $format)
					foreach ($GLOBALS['site']['base'] as $path)
					{
						$file = $path . 'locales/' . sprintf($format, $locale, $module);
						if (is_readable($file))
							$strings = array_merge($strings, self::stringToArray(file_get_contents($file)));
					}
				self::$container[$locale][$module] = $strings;
			}
			self::$module = $module;
		}

		public static function stringToArray($string)
		{
			$result = array();
			foreach (explode("\n", $string) as $line)
			{
				$line = trim($line);
				if ((!empty($line)) && (substr($line, 0, 1) != '#'))
				{
					if (($temp = strpos($line, "\t")) === false)
						$result[$line] = '';
					else
						$result[substr($line, 0, $temp)] = ltrim(substr($line, $temp), "\t");
				}
			}
			return $result;
		}

		public static function message($message, $module = null, $default = null)
		{
			if ($module === null)
				$module = self::$module;

			if (isset(self::$container[$GLOBALS['page']['locale']][$module]) && isset(self::$container[$GLOBALS['page']['locale']][$module][$message]))
				return self::$container[$GLOBALS['page']['locale']][$module][$message];

			if ($temp0 = m($module))
			{
				$class = strtolower(get_class($temp0));
				while ($class)
				{
					if (isset(self::$container[$GLOBALS['page']['locale']][$class]) && isset(self::$container[$GLOBALS['page']['locale']][$class][$message]))
						return self::$container[$GLOBALS['page']['locale']][$class][$message];
					$class = strtolower(get_parent_class($class));
				}
			}

			if (($module != '@core') && isset(self::$container[$GLOBALS['page']['locale']]['@core']) && isset(self::$container[$GLOBALS['page']['locale']]['@core'][$message]))
				return self::$container[$GLOBALS['page']['locale']]['@core'][$message];

			return (($default !== null) ? $default : $message);
		}

		public static function sprintf($message, $module = null, $default = null)
		{
			$args = func_get_args();
			$args = array_slice($args, 3);
			return vsprintf(self::message($message, $module, $default), $args);
		}

		public static function set($message, $module = null, $value = null)
		{
			if ($module === null)
			{
				$value = $module;
				$module = self::$module;
			}
			self::$container[$GLOBALS['page']['locale']][$module][$message] = $value;
		}

		public static function transfer($from, $to, $onlynew = true)
		{
			foreach (self::$container[$GLOBALS['page']['locale']][$from] as $k => $v)
				if (!$onlynew || !isset(self::$container[$GLOBALS['page']['locale']][$to][$k]))
					self::$container[$GLOBALS['page']['locale']][$to][$k] = $v;
		}

		public static function map1($array, $prefix = '', $module = null)
		{
			$result = array();
			foreach ($array as $item)
				$result[$item] = __($prefix.$item, $module);
			return $result;
		}
	}

	class Template
	{
		public static			$overrides							= array();
		public					$id									= null;
		public					$type								= null;
		public					$source								= null;
		public					$data								= null;
		public					$directory							= null;
		public					$output								= null;

		public					$group								= null;
		public					$part								= null;
		public					$args								= null;

		public static function instantiate($id, $data, $source = null, $type = null, $directory = null)
		{
			return new Template($id, $data, $source, $type, $directory);
		}

		public static function quick($type, $source, $data, $_x_variables = array())
		{
			$template = new Template(null, $data, $source, $type);
			return $template->process($_x_variables);
		}

		public function __construct($id, $data, $source = null, $type = null, $directory = null)
		{
			$this->id = $id;
			$this->data = $data;
			$this->type = $type;
			$this->source = $source;
			$this->directory = (string) $directory;
		}

		public function process($_x_args = array(), $try = false)
		{
			if (($this->source === null) && ($this->type === null))
				if (isset(self::$overrides[$this->data]))
					return self::$overrides[$this->data]->process($_x_args, $try);
			
			$this->args = $_x_args;

			if (($this->source === null) && ($this->type === null))
			{
				$this->data = str_replace('#locale#', $GLOBALS['page']['locale'], $this->data);
				if (substr($this->data, 0, 1) == ':')
				{
					$prefix = 'templates/';
					$files = array();
					$temp = explode(':', substr($this->data, 1));
					$this->group = $temp[0];
					if (isset($temp[1]))
						$this->part = $temp[1];
					if ($this->part)
						$files[] = $this->group . '-' . $this->part;
					$files[] = $this->group;
					if (m($this->group) && (($parent_group = get_class(m($this->group))) != $this->group))
					{
						if ($this->part)
							$files[] = $parent_group . '-' . $this->part;
						$files[] = $parent_group;
					}
				}
				else
				{
					$prefix = 'web/';
					$files = array($this->data);
				}

				foreach ($GLOBALS['site']['base'] as $base)
					foreach ($files as $file)
						foreach (array('.php' => 'php', '.tpl' => 'smarty', '.html' => 'html') as $extension => $type)
							if (is_readable($base . $prefix . $file . $extension))
							{
								$this->source = 'file';
								$this->type = $type;
								$this->data = $prefix . $file . $extension;
								break 3;
							}
				if (($this->source === null) && ($this->type === null) && $try)
					return null;
			}

			if ($this->source == 'file')
			{
				$this->data = str_replace('#locale#', $GLOBALS['page']['locale'], $this->data);
				$temp = strrpos($this->data, '.');
				if ($temp === false)
					$temp = strlen($this->data);
				$ext = substr($this->data, $temp);
				$name = substr($this->data, 0, $temp);
			}

			$prefix = 'template-' . $this->group . ($this->part ? (':' . $this->part) : '') . '-';
			foreach (array('skip', 'cut_length', 'date_format', 'datetime_format', 'pieces', '__class') as $temp)
				if (!isset($this->args[$temp]))
					$this->args[$temp] = null;
			if (($temp = Core::get($prefix . 'skip')) !== null)
				$skip = $this->args['skip'] = $temp;
			if (($this->type != 'smarty') || (strpos($this->part, 'mail-') !== false) || (strpos($this->group, '@') !== false))
				$this->args['skip'] .= ';container;';
			foreach ($GLOBALS['settings']['core'] as $k => $v)
					if (strpos($k, $prefix) === 0)
						$this->args[substr($k, strlen($prefix))] = $v;
			if ($this->args['cut_length'] === null)
				$this->args['cut_length'] = Core::get($prefix . 'cut-length', 400);
			if ($this->args['date_format'] === null)
				$this->args['date_format'] = Core::get($prefix . 'date-format', __('date-format', 'core'));
			if ($this->args['datetime_format'] === null)
				$this->args['datetime_format'] = Core::get($prefix . 'datetime-format', __('datetime-format', 'core'));
			if ($this->args['pieces'] === null)
				if (($temp = Core::get($prefix . 'pieces')) !== null)
					$this->args['pieces'] = $temp;
			$counter = (int) Core::get($prefix . 'counter');
			$counter++;
			Core::set($prefix . 'counter', $counter);

			$this->output = '';

			if (strpos($this->args['skip'], ';container;') === false)
			{
				$temp1 = str_replace(array('-', ':'), array('_', ''), $this->group);
				$temp2 = str_replace(array('-', '@'), array('_', '__'), $this->part);
				$baseclass = $temp1 . '_' . $temp2 . '* ' . $temp1 . '* ' . $temp2 . '*';
				$class = '';
				if ($this->part)
					$class .= str_replace('*', '', $baseclass);
				if ($temp = Core::get($prefix . 'counter-divisor'))
					if (($counter % $temp) == 0)
						$class .= ' ' . str_replace('*', '_division', $baseclass);
				/*else
					$class = 'page' . str_replace(array('/', '-'), array('_', '_'), $GLOBALS['page']['location']);*/
				if ($this->args['__class'])
					$class .= ' ' . $this->args['__class'];
				$this->output .= '<div class="' . $class . '">';
			}

			switch ($this->type)
			{
				case 'html':
					switch ($this->source)
					{
						case 'string':
							$this->output .= $this->data;
							break;
						case 'file':
							$f = U::firstExistingFile($GLOBALS['site']['base'], $this->directory . $name . '.override' . $ext);
							if (!$f)
								$f = U::firstExistingFile($GLOBALS['site']['base'], $this->directory . $name . $ext);
							if (!$f)
								error("template '{$this->data}' not found");
							else
								$this->output .= file_get_contents($f);
							break;
					}
					break;
				case 'php':
					switch ($this->source)
					{
						case 'string':
							ob_start();
							extract($this->args, EXTR_SKIP);
							$this->output .= eval($this->data);
							$this->output .= ob_get_clean();
							break;
						case 'callback':
							ob_start();
							$this->output .= callback($this->data, array('template' => $this, 'args' => $this->args));
							$this->output .= ob_get_clean();
							break;
						case 'file':
							if (substr($name, 0, 1) == ':')
							{
								$name = 'templates/' . strtr(substr($name, 1), ':', '-');
								$ext = '.php';
							}
							$f = U::firstExistingFile($GLOBALS['site']['base'], $this->directory . $name . '.override' . $ext);
							if (!$f)
								$f = U::firstExistingFile($GLOBALS['site']['base'], $this->directory . $name . $ext);
							if (!$f)
								error("template '{$this->data}' / '{$name}' not found");
							else
							{
								ob_start();
								extract($this->args, EXTR_SKIP);
								include($f);
								$this->output .= ob_get_clean();
							}
							break;
					}
					break;
				case 'smarty':
					require_once('Smarty/Smarty.class.php');
					require_once('Smarty.php');
					$template = new SmartyEx();
					foreach (array_keys($this->args) as $k)
						$template->assign_by_ref($k, $this->args[$k]);
					$template->assign_by_ref('this', $this);

					switch ($this->source)
					{
						case 'string':
							$this->output .= $template->fetch('string:' . $this->data);
							break;
						case 'file':
							$cache = Cache::tget($this->data);
							$cache_id = null;
							$is_cached = false;
							if ($cache)
							{
								$template->caching = 1;
								$template->cache_lifetime = $cache['ttl'];
								$cache_id = '~|' . $GLOBALS['page']['locale'] . '|' . $this->data . '|' . $cache['id']->evaluate(array('template' => $this, 'smarty' => $template));
								$is_cached = $template->is_cached($this->data, $cache_id);
							}
							if ($GLOBALS['page']['log'] & LOG_TEMPLATES)
								U::log('template', $this->data, ($is_cached ? 'cached' : ''));
							$this->output .= $template->fetch($this->data, $cache_id);
							break;
					}
					break;
			}

			if (strpos($this->args['skip'], ';container;') === false)
					$this->output .= '</div>';

			return $this->output;
		}

		public static function prepare($file, $prefix = 'templates/', $id = null)
		{
			$file = str_replace('#locale#', $GLOBALS['page']['locale'], $file);

			if (($temp = strpos($file, ':')) !== false)
			{
				$prefix = substr($file, 0, $temp + 1);
				$file = substr($file, $temp + 1);
			}
			if ($prefix == ':')
				$prefix = 'templates/';

			foreach ($GLOBALS['site']['base'] as $base)
				foreach (array('.php' => 'php', '.tpl' => 'smarty', '.html' => 'html') as $extension => $type)
					if (is_readable($base . $prefix . $file . $extension)/* && !isset($GLOBALS['templates']['main'])*/)
						return new Template($id, $prefix . $file . $extension, 'file', $type);
			return null;
		}
	}

	function __($message, $module = null, $default = null)
	{
		return Locale::message($message, $module, $default);
	}

	function l($message, $module = null, $default = null)
	{
		return Locale::message($message, $module, $default);
	}

	class Setting
	{
		public static 			$_							= array();

		public					$module								= '';
		public					$field								= null;
		public					$name								= null;

		public function __construct($module, $field)
		{
			$this->module = $module;
			$this->field = $field;
		}

		public function id()
		{
			return ($this->module . '-' . $this->field->id);
		}

		public function name()
		{
			if ($this->module == '@core')
				$module_name = __('@core', '@core');
			else
				$module_name = __('module-name', $this->module);
			return $module_name . ': ' . $this->nameShort();
		}

		public function nameShort()
		{
			if ($this->name !== null)
				return $this->name;
			else
				return __('setting-' . $this->field->id, $this->module, false);
		}

		public function get($postfix = '')
		{
			if (isset($GLOBALS['settings'][$this->module]) && isset($GLOBALS['settings'][$this->module][$this->field->id . $postfix]))
				return $GLOBALS['settings'][$this->module][$this->field->id . $postfix];
			else
				return $this->field->default;
		}

		public function set($value = null, $postfix = '')
		{
			if (isset($GLOBALS['settings'][$this->module]))
			{
				if ($value === null)
					unset($GLOBALS['settings'][$this->module][$this->field->id . $postfix]);
				else
					$GLOBALS['settings'][$this->module][$this->field->id . $postfix] = $value;
			}
		}

		public static function register($setting)
		{
			self::$_[$setting->id()] = $setting;
			return $setting;
		}
	}

	class Router
	{
		const					TYPE_EQUALS							= 0;
		const					TYPE_REGEX							= 1;
		const					TYPE_STATIC							= 2;

		public static			$routes								= array();
		public static			$unused								= array();

		public static function addS($pattern, $locale, $location, $template = null, $simple = false, $layout = null)
		{
			die('obsolete');
			if ($locale != $GLOBALS['page']['locale'])
				return;
			$args = array();
			if ($locale !== null)
				$args['page.locale'] = $locale;
			if ($location !== null)
				$args['page.location'] = $location;
			if ($simple !== null)
				$args['page.simple'] = $simple;
			if ($layout !== null)
				$args['page.layout'] = $layout;
			if ($template !== null)
				$args['template'] = $template;
			self::add(self::TYPE_STATIC, $pattern, '', $args);
		}

		public static function addE($pattern, $locale, $location, $template = null, $simple = false, $layout = null)
		{
			if ($locale != $GLOBALS['page']['locale'])
				return;
			$args = array();
			if ($locale !== null)
				$args['page.locale'] = $locale;
			if ($location !== null)
				$args['page.location'] = $location;
			if ($simple !== null)
				$args['page.simple'] = $simple;
			if ($layout !== null)
				$args['page.layout'] = $layout;
			if ($template !== null)
				$args['template'] = $template;
			self::add(self::TYPE_EQUALS, $pattern, '', $args);
		}

		public static function add($type, $pattern, $url = '', $args = array(), $options = array())
		{
			$item = array(
				'type' => $type,
				'pattern' => $pattern,
				'url' => $url,
				'args' => $args,
				'options' => $options,
			);
			self::$routes[$pattern] = $item;
			self::$unused[$pattern] = $item;
		}

		public static function process($restart = false)
		{
			if ($restart)
				self::$unused = self::$routes;
			/*else if ($GLOBALS['page']['location'])
				return;*/

			$result = null;
			foreach (self::$unused as $route)
			{
				if ($GLOBALS['page']['location'])
					$ok = ($GLOBALS['page']['location'] == @$route['args']['page.location']);
				else
				{
					$ok = false;
					$matches = array();
					if ($route['type'] == self::TYPE_STATIC)
						$ok = ($route['pattern'] == $GLOBALS['page']['url']);
					if ($route['type'] == self::TYPE_EQUALS)
						$ok = ($route['pattern'] == $GLOBALS['page']['URL']);
					if ($route['type'] == self::TYPE_REGEX)
						$ok = (boolean) preg_match('~^'.$route['pattern'].'$~', $GLOBALS['page']['URL'], $matches);
				}
				if ($ok)
				{
					foreach ($route['args'] as $k => $v)
						if ($k == 'template')
							Core::frame($v);
						else
							Core::setG($k, $v);
					foreach ($route['options'] as $k => $v)
						$_REQUEST[$v] = $matches[$k+1];
					Core::location();
					$result = $route;
					break;
				}
			}

			self::$unused = array();
			return $result;
		}

		public static function find($location, $options = array())
		{
			if ($options === null)
			{
				$result = array();
				foreach (self::$routes as $route)
					if (isset($route['args']['page.location']) && ($route['args']['page.location'] == $location))
						$result[] = $route;
				return $result;
			}
			else
				foreach (self::$routes as $route)
					if (isset($route['args']['page.location']) && ($route['args']['page.location'] == $location))
						if (array_keys($options) == $route['options'])
							return self::prepare($route, $options);
			return null;
		}

		public static function prepare($route, $options = array())
		{
			$url = ($route['url'] ? $route['url'] : $route['pattern']);
			$opts = $route['options'];
			while ($opt = array_shift($opts))
			{
				$l = strpos($url, '(');
				$r = strpos($url, ')');
				if (($l !== false) && ($r !== false))
					$url = substr_replace($url, $options[$opt], $l, $r - $l + 1);
			}
			$route['link'] = $url;
			return $route;
		}

		public static function urlRelative($location, $options = array(), $fallback = null)
		{
			$route = self::find($location, $options);
			if (!$route)
			{
				if ($fallback === null)
					$fallback = '('.$location . ')';
				return $fallback;
			}
			return $route['link'];
		}

		public static function url($location, $options = array(), $fallback = null)
		{
			$route = self::find($location, $options);
			if (!$route)
			{
				if ($fallback === null)
					$fallback = '('.$location . ')';
				return $fallback;
			}
			if ($route['type'] == self::TYPE_STATIC)
				return PATH.$route['link'];
			else
				return LPATH.$route['link'];
		}
		
		public static function linkToCurrent()
		{
			$result = explode('?', $_SERVER['REQUEST_URI']);
			if (isset($result[1]))
			{
				$result[1] = U::urlParametersToArray($result[1]);
				foreach (array('page.viewstate', 'page.panel', 'page_viewstate', 'page_panel') as $k)
					unset($result[1][$k]);
				if ($GLOBALS['page']['viewstate'])
					$result[1]['page.viewstate'] = $GLOBALS['page']['viewstate'];
				$result[1] = U::arrayToUrlParameters($result[1]);
			}
			$result = implode('?', $result);
			return $result;
		}
	}

	class SessionObject
	{
		public 				$class							= '';
		public 				$serialized						= '';

		public function __construct($object)
		{
			$this->class = get_class($object);
			$this->serialized = serialize($object);
		}
	}

	class Session
	{
		public static		$oid_ip							= '';
		public static		$oid_hash						= '';

		public static function oid($prefix)
		{
			$result = /*'-' . self::$oid_ip . */'-' . $prefix;
			return U::randomString(1, 'abcdefghijklmnopqrstuvwxyz') . U::randomString(15) . $result;
			//return substr(md5(self::$oid_hash . microtime() . mt_rand(0, 1073741824)), 0, (64 - strlen($result))) . $result;
			/*$result = $prefix . '-' . self::$oid_ip . '-' . md5(self::$oid_hash . microtime() . mt_rand(0, 1073741824));
			return substr($result, 0, 64);*/
		}

		public static function get($key, $value = null)
		{
			if (isset($_SESSION[$key]))
				return (($_SESSION[$key] instanceof SessionObject) ? unserialize($_SESSION[$key]->serialized) : $_SESSION[$key]);
			else
				return $value;
		}

		public static function set($key, $value)
		{
			if (is_array($key) || ($key === null) || is_bool($key))
				foreach ($value as $k => $v)
					$this->set($k, $v);
			if (($value === null) && isset($_SESSION[$key]))
				unset($_SESSION[$key]);
			else if ($value !== null)
			{
				if (is_object($value))
					$_SESSION[$key] = new SessionObject($value);
				else
					$_SESSION[$key] = $value;
			}
		}

		public static function initialize()
		{
			//Log::ll($GLOBALS['site']['data'] . 'session.txt', time(), $_SERVER['REQUEST_URI'], dump($_SESSION, false, false, false));
			session_start();

			self::$oid_ip = '';
			/*if (isset($_SERVER['SERVER_ADDR']) && (count($temp = explode('.', $_SERVER['SERVER_ADDR'])) == 4))
				self::$oid_ip .= dechex($temp[0]) . dechex($temp[1]) . dechex($temp[2]) . dechex($temp[3]) . '-';
			else
				self::$oid_ip .= '00000000-';*/
			if (isset($_SERVER['REMOTE_ADDR']) && (count($temp = explode('.', $_SERVER['REMOTE_ADDR'])) == 4))
				self::$oid_ip .= dechex($temp[0]) . dechex($temp[1]) . dechex($temp[2]) . dechex($temp[3]);
			else
				self::$oid_ip .= '00000000';

			self::$oid_hash .= (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '') . (isset($_SERVER['REMOTE_NAME']) ? $_SERVER['REMOTE_NAME'] : '') . session_id();

			if (in_array(Session::get('locale'), $GLOBALS['site']['locales']))
				Session::set('locale', $GLOBALS['site']['locale-default']);
		}
	}

	class ViewState
	{
		public static function get($key, $value = null)
		{
			if (isset($_SESSION['viewstate'][$GLOBALS['page']['viewstate']][$key]))
				return $_SESSION['viewstate'][$GLOBALS['page']['viewstate']][$key];
			else
				return $value;
		}

		public static function set($key, $value)
		{
			if ($value === null)
				unset($_SESSION['viewstate'][$GLOBALS['page']['viewstate']][$key]);
			else
				$_SESSION['viewstate'][$GLOBALS['page']['viewstate']][$key] = $value;
		}

		public static function initialize()
		{
			if (!isset($_SESSION['viewstate']))
				$_SESSION['viewstate'] = array();

			if ($GLOBALS['page']['viewstate'] && isset($_SESSION['viewstate'][$GLOBALS['page']['viewstate']]))
				$data = $_SESSION['viewstate'][$GLOBALS['page']['viewstate']];
			else
				$data = array();

			$_SESSION['viewstate'] = array_slice($_SESSION['viewstate'], -40, 40, true);
			$i = 1; while (isset($_SESSION['viewstate'][$i])) $i++;
			$GLOBALS['page']['viewstate'] = $i;

			$_SESSION['viewstate'][$GLOBALS['page']['viewstate']] = $data;
		}
	}


	class Core
	{
		public static			$phase								= null;

		public static function get($key, $value = null)
		{
			if (isset($GLOBALS['settings']['@core'][$key]))
				return $GLOBALS['settings']['@core'][$key];
			else
				return $value;
		}

		public static function set($key, $value)
		{
			if ($value === null)
				unset($GLOBALS['settings']['@core'][$key]);
			else
				$GLOBALS['settings']['@core'][$key] = $value;
		}

		public static function getG($key, $value = null)
		{
			$parts = explode('.', $key);
			if (count($parts) == 1)
				return Core::get($key, $value);
			else if (isset($GLOBALS[$parts[0]]) && isset($GLOBALS[$parts[0]][$parts[1]]))
				return $GLOBALS[$parts[0]][$parts[1]];
			else
				return $value;
		}

		public static function setG($key, $value)
		{
			$parts = explode('.', $key);
			if (count($parts) == 1)
				Core::set($key, $value);
			else if (isset($GLOBALS[$parts[0]]))
				$GLOBALS[$parts[0]][$parts[1]] = $value;
		}

		public static function database($dsn = null)
		{
			if (!$dsn)
				$dsn = $GLOBALS['site']['dsn'];
			$db = qconnect($dsn);
			if (!$db)
				die('database failure');
		}

		public static function location($pattern = null)
		{
			if ($pattern === null)
			{
				if (($temp = U::request('page_location')) !== null)
					$GLOBALS['page']['location'] = $temp;
				if ($GLOBALS['page']['location'] !== null)
				{
					$GLOBALS['page']['location'] = $GLOBALS['page']['location'];
					$GLOBALS['page']['location'] = ltrim($GLOBALS['page']['location'], '/');

					if (strrchr($GLOBALS['page']['location'], '/'))
					{
						$GLOBALS['page']['file'] = substr(strrchr($GLOBALS['page']['location'], '/'), 1);
						$GLOBALS['page']['directory'] = substr($GLOBALS['page']['location'], 0, strlen($GLOBALS['page']['location']) - strlen($GLOBALS['page']['file']));
					}
					else
					{
						$GLOBALS['page']['file'] = $GLOBALS['page']['location'];
						$GLOBALS['page']['directory'] = '';
					}

					if (empty($GLOBALS['page']['file']))
						$GLOBALS['page']['file'] = 'index';
					$GLOBALS['page']['location'] = $GLOBALS['page']['directory'] . $GLOBALS['page']['file'];
				}
			}
			else
			{
				if (($pattern === true) || (is_string($pattern) && empty($pattern)))
					return true;
				else if ($pattern === false)
					return false;
				if ($negative = (substr($pattern, 0, 1) == '!'))
					$pattern = substr($pattern, 1);
				if (substr($pattern, 0, 1) == '~')
					return preg_match($pattern, $GLOBALS['page']['location']);
				else
					return ($pattern == $GLOBALS['page']['location']);
			}
		}

		public static function module($id, $modulefile = null)
		{
			if ($GLOBALS['page']['simple'])
				return;

			ob_start();
			if ($modulefile === null)
				$modulefile = $id;
			$class = str_replace(array('#', '@'), array('', ''), strtolower($modulefile));
			//$id = str_replace(array('#', '@'), array('', ''), strtolower($id));

			$file = U::firstExistingFile($GLOBALS['site']['base'], 'modules/' . $modulefile . '.override.php');
			if (!$file)
				$file = U::firstExistingFile($GLOBALS['site']['base'], 'modules/' . $modulefile . '.php');
			if (!$file)
				die("module class '{$modulefile}' is missing");
			require_once ($file);

			if (!isset($GLOBALS['settings'][$id]))
				$GLOBALS['settings'][$id] = array();
			$GLOBALS['settings'][$id]['id'] = $id;

			$GLOBALS['modules'][$id] = new $class();
			Locale::module($id);
			Locale::module($modulefile);
			$GLOBALS['modules'][$id]->id = $id;
			if ($GLOBALS['page']['log'] & LOG_CORE_MODULE)
				U::log('core', 'modules: __construct', $id);

			if ($file = U::firstExistingFile($GLOBALS['site']['base'], 'modules/' . $modulefile . '.models.override.php'))
				require_once($file);
			else if ($file = U::firstExistingFile($GLOBALS['site']['base'], 'modules/' . $modulefile . '.models.php'))
				require_once($file);

			$GLOBALS['page']['content'] .= ob_get_clean();
		}

		public static function template($id, $value, $source = null, $type = null)
		{
			$GLOBALS['templates'][$id] = new Template($id, $value, $source, $type);
		}

		public static function frame($file)
		{
			$GLOBALS['templates'][0] = new Template(0, array('Core', 'html'), 'callback', 'php');
			$GLOBALS['templates']['main'] = new Template('main', $file, null, null);

			if (!isset($GLOBALS['templates']['frame']))
			{
				$frame_prefix = 'web/';
				foreach ($GLOBALS['site']['base'] as $base)
					foreach (array($GLOBALS['page']['locale'].'/', '') as $locale_part)
					{
						if (is_readable($base . $frame_prefix . $locale_part . '@frame.php'))
						{
							$GLOBALS['templates']['frame'] = new Template('frame', $frame_prefix . $locale_part . '@frame.php', 'file', 'php');
							break 2;
						}
						if (is_readable($base . $frame_prefix . $locale_part . '#frame.php')) // obsolete
						{
							$GLOBALS['templates']['frame'] = new Template('frame', $frame_prefix . $locale_part . '#frame.php', 'file', 'php');
							break 2;
						}
					}
			}
		}

		public static function flagSet($key, $value = null, $type = 'page')
		{
			if ($value === null)
				unset($GLOBALS[$type]['flags'][$key]);
			else
				$GLOBALS[$type]['flags'][$key] = $value;
		}

		public static function flagGet($key, $value = null, $type = 'page')
		{
			return (isset($GLOBALS[$type]['flags'][$key]) ? $GLOBALS[$type]['flags'][$key] : $value);
		}

		public static function plaintext0()
		{
			$result = $GLOBALS['invocation']->dispatch();
			echo Core::common('messages-output-0');
		}

		public static function html()
		{
			$body = null;
			$head = null;
			if (isset($GLOBALS['templates']['frame']) && $GLOBALS['templates']['frame'])
				$body = $GLOBALS['templates']['frame']->process();
			else if (isset($GLOBALS['templates']['main']) && $GLOBALS['templates']['main'])
				$body = $GLOBALS['templates']['main']->process();
			if (!isset($GLOBALS['templates'][0]))
			{
				echo $body;
				return;
			}
			if (isset($GLOBALS['templates']['head']) && $GLOBALS['templates']['head'])
				$head = $GLOBALS['templates']['head']->process();

			if ($body === null)
			{
				header('HTTP/1.1 410 Gone');
				header('Status: 410');
				return;
			}

			if ($GLOBALS['page']['administration'])
				echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
			else
				echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
			echo "\n".'<html xmlns="http://www.w3.org/1999/xhtml">';
			echo "\n".'	<head>';
			echo "\n".'		<meta http-equiv="content-type"     content="text/html; charset=utf-8" />';
			echo "\n".'		<meta http-equiv="content-language" content="'.$GLOBALS['page']['locale'].'" />';
			if (!self::flagGet('reduced-meta'))
			{
				echo "\n".'		<meta http-equiv="cache-control"    content="no-cache" />';
				echo "\n".'		<meta http-equiv="pragma"           content="no-cache" />';
				/*if ($GLOBALS['site']['development'])
					echo "\n".'		<meta name="robots"                 content="noindex, nofollow" />';
				else*/
					echo "\n".'		<meta name="robots"                 content="index, follow" />';
				echo "\n".'		<meta name="author"                 content="Poski.com s.r.o." />';
				echo "\n".'		<meta name="copyright"              content="Poski.com s.r.o." />';
				echo "\n".'		<meta name="owner"                  content="Poski.com s.r.o." />';
				echo "\n".'		<meta name="keywords"               content="'.$GLOBALS['page']['keywords'].'" />';
				echo "\n".'		<meta name="description"            content="'.$GLOBALS['page']['description'].'" />';
			}
			$temp = $GLOBALS['page']['title'];
			if ($GLOBALS['page']['title'] && $GLOBALS['site']['title'])
				$temp .= self::get('title-separator');
			$temp .= $GLOBALS['site']['title'];
			echo "\n".'		<title>' . HTML::e($temp) . '</title>';
			echo "\n".$head;
			if (isset($GLOBALS['page']['head']) && trim($GLOBALS['page']['head']))
				echo $GLOBALS['page']['head'];
			$temp = $GLOBALS['site']['base'][0] . 'web/head.html';
			if (is_readable($temp) && !@$GLOBALS['page']['administration'])
				echo "\n" . str_replace(
					array('{#path#}', '{#locale#}'),
					array($GLOBALS['site']['path'], $GLOBALS['page']['locale']),
					file_get_contents($temp))
				. "\n";
			echo "\n".'	</head>';
			$body_class = 'location_' . $GLOBALS['page']['location'] . ' directory_' . rtrim($GLOBALS['page']['directory'], '/') . ' file_' . $GLOBALS['page']['file'];
			$body_class = strtr($body_class, '-/', '__');
			echo "\n".'	<body class="'.$body_class.'">' . "\n";
			if (@$GLOBALS['site']['google-analytics'])
			{
				echo HTML::js('var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www."); document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));')."\n";
				foreach ($GLOBALS['site']['google-analytics'] as $tracker)
				{
					$var = str_replace('-', '', $tracker);
					echo 'var pageTracker'.$var.' = _gat._getTracker("'.$tracker.'");';
					echo 'pageTracker'.$var.'._trackPageview();';
				}
			}
			echo $body;

			echo "\n".'	</body>';
			echo "\n".'</html>';
		}

		public static function head()
		{
			$a = (bool) (($GLOBALS['page']['location'] == 'a') || ($GLOBALS['page']['location'] == 'aa'));
			$administration = (bool) ($GLOBALS['page']['location'] == 'administration');
			$apda = (bool) ($GLOBALS['page']['location'] == 'apda');
			
			if ($administration)
				$administration_version = Core::get('administration-theme', 3);

			if ($a)
				echo '
				<script type="text/javascript"><!--//--><![CDATA[//><!--
					var G = new Object();
					G.site = new Object();
					G.site.path = "'.$GLOBALS['site']['path'].'";
					G.site.url = "'.$GLOBALS['site']['url'].'";
					G.page = new Object();
					G.page.panel = "'.$GLOBALS['page']['panel'].'";
					G.page.viewstate = "'.$GLOBALS['page']['viewstate'].'";
				//--><!]]></script>
				<script type="text/javascript" src="'.$GLOBALS['site']['path'].'web/_administration/application.js"></script>
				<script type="text/javascript" src="'.$GLOBALS['site']['path'].'web/_js/base64.js"></script>';
			if ($GLOBALS['page']['administration'])
			{
				$GLOBALS['page']['flags']['tiny_mce'] = true;
				$GLOBALS['page']['flags']['jscalendar'] = true;
			}
			if (!$apda)
			{
				$GLOBALS['page']['flags']['jquery'] = true;
				$GLOBALS['page']['flags']['slimbox2'] = true;
				//$GLOBALS['page']['flags']['mootools'] = false;
			}
			if ($GLOBALS['page']['administration'] && !$apda)
				$GLOBALS['page']['flags']['thickbox'] = true;

			if ($a)
			{
				echo '		<link rel="stylesheet" type="text/css" href="'.$GLOBALS['site']['path'].'web/_administration/a.css" />'."\n";
				if (is_readable($temp = $GLOBALS['site']['base'][0] . 'web/_administration/a.override.css'))
					echo '		<link rel="stylesheet" type="text/css" href="'.$GLOBALS['site']['path'].'web/_administration/a.override.css" />'."\n";
			}	
			else if ($apda)
				echo '		<link rel="stylesheet" type="text/css" href="'.$GLOBALS['site']['path'].'web/_administration/apda.css" />'."\n";
			else if ($administration)
			{
				echo '		<link rel="stylesheet" type="text/css" href="'.$GLOBALS['site']['path'].'web/_administration/administration_v'.$administration_version.'.css" />'."\n";
				if (is_readable($temp = $GLOBALS['site']['base'][0] . 'web/_administration/administration.override.css'))
					echo '		<link rel="stylesheet" type="text/css" href="'.$GLOBALS['site']['path'].'web/_administration/administration.override.css" />'."\n";
			}
			else
			{
				echo '		<link rel="stylesheet" type="text/css" href="'.$GLOBALS['site']['path'].'web/_css/style.css" />'."\n";
				if (is_readable($temp = $GLOBALS['site']['base'][0] . 'web/' . $GLOBALS['page']['locale'] . '/_css/style.css'))
					echo '		<link rel="stylesheet" type="text/css" href="'.$GLOBALS['site']['path'].'web/'.$GLOBALS['page']['locale'].'/_css/style.css" />'."\n";
			}

			if (@$GLOBALS['page']['flags']['jquery'])
				echo '		<script type="text/javascript" src="'.$GLOBALS['site']['path'].'web/_js/jquery.js"></script>'."\n";

			if (@$GLOBALS['page']['flags']['lytebox'])
				echo '		<script type="text/javascript" src="'.$GLOBALS['site']['path'].'web/_js/lytebox.js"></script>
		<link rel="stylesheet" type="text/css" href="'.$GLOBALS['site']['path'].'web/_css/lytebox.css" media="screen" />'."\n";
		
			if (@$GLOBALS['page']['flags']['slimbox2'])
				echo '		<script type="text/javascript" src="'.$GLOBALS['site']['path'].'web/_js/slimbox2.js"></script>
		<link rel="stylesheet" type="text/css" href="'.$GLOBALS['site']['path'].'web/_css/slimbox2.css" media="screen" />'."\n";

			if (@$GLOBALS['page']['flags']['thickbox'])
				echo '		<script type="text/javascript" src="'.$GLOBALS['site']['path'].'web/_js/thickbox.js"></script>
		<link rel="stylesheet" type="text/css" href="'.$GLOBALS['site']['path'].'web/_css/thickbox.css" media="screen" />'."\n";

			if (@$GLOBALS['page']['flags']['tiny_mce'])
			{
				/*echo '<script type="text/javascript" src="'.$GLOBALS['site']['path'].'third-party/tiny_mce/tiny_mce_gzip.js"></script>
					<script type="text/javascript"><!--//--><![CDATA[//><!--
						tinyMCE_GZ.init({
							plugins : "style,layer,table,save,advhr,advimage,advlink,emotions,iespell,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras",
							themes : "simple,advanced",
							languages : "cs,en",
							disk_cache : true,
							debug : false
						});
					//--><!]]></script>';*/
				echo '<script type="text/javascript" src="'.$GLOBALS['site']['path'].'third-party/tiny_mce/tiny_mce.js"></script>';
				/*echo '<script type="text/javascript"><!--//--><![CDATA[//><!--
						function kfm_for_tiny_mce(field_name, url, type, win)
						{
							window.SetUrl = function(url, width, height, caption)
							{
								win.document.forms[0].elements[field_name].value = url;
								if(caption)
								{
									win.document.forms[0].elements["alt"].value = caption;
									win.document.forms[0].elements["title"].value = caption;
								}
							}
							window.open("' . $GLOBALS['site']['path'] . 'third-party/kfm/index.php?mode=selector&type=" + type, "kfm", "modal,width=800,height=600");
						}
					//--><!]]></script>';*/
				echo '<script type="text/javascript"><!--//--><![CDATA[//><!--
						function ajaxfilemanager(field_name, url, type, win)
						{
				            tinyMCE.activeEditor.windowManager.open({
				                url: "' . $GLOBALS['site']['path'] . 'third-party/ajaxfilemanager/ajaxfilemanager.php",
				                width: 782,
				                height: 440,
				                inline : "yes",
				                close_previous : "no"
				            },{
				                window : win,
				                input : field_name
				            });
						}
					//--><!]]></script>';
				echo '<script type="text/javascript"><!--//--><![CDATA[//><!--
						tinyMCE.init({
							mode : "none",
							editor_selector : "mceEditor",
							theme : "advanced",
							entity_encoding : "raw",
							remove_linebreaks : false,
							apply_source_formatting : true,
							plugins : "style,layer,table,save,advhr,advimage,advlink,emotions,iespell,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras",
							theme_advanced_buttons1 : "save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect",
							theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
							theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
							theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,|,visualchars,nonbreaking",
							theme_advanced_toolbar_location : "top",
							theme_advanced_toolbar_align : "left",
							theme_advanced_statusbar_location : "bottom",
							theme_advanced_resizing : true,
							extended_valid_elements : "a[name|href|target|title|onclick],img[style|class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]",
							file_browser_callback : "ajaxfilemanager",
							auto_resize : true,
							relative_urls : false,
							remove_script_host : true,
							document_base_url : "' . $GLOBALS['site']['url'] . '"
						});
					//--><!]]></script>';
			}

			if (@$GLOBALS['page']['flags']['jscalendar'])
			{
				echo '		<link rel="stylesheet" type="text/css" href="'.$GLOBALS['site']['path'].'web/_css/calendar.css" media="all" title="win2k-cold-1" />
		<script type="text/javascript" src="'.$GLOBALS['site']['path'].'web/_js/calendar.js"></script>'."\n";
				if ($GLOBALS['page']['locale'] == 'cs')
					echo '		<script type="text/javascript" src="'.$GLOBALS['site']['path'].'web/_js/calendar-cs-utf8.js"></script>'."\n";
				else
					echo '		<script type="text/javascript" src="'.$GLOBALS['site']['path'].'web/_js/calendar-en.js"></script>'."\n";
			}

			if (@$GLOBALS['page']['flags']['mootools'])
				echo '		<script type="text/javascript" src="'.$GLOBALS['site']['path'].'web/_js/mootools.js"></script>'."\n";

			if (@$GLOBALS['page']['redirect'])
				//echo '		' . HTML::js('setTimeout(\'window.location = " . ' . $GLOBALS['page']['redirect'] . ' . "\', 5000);');
				echo '		<meta http-equiv="refresh" content="3;url='.HTML::e($GLOBALS['page']['redirect']).'" />';
		}

		public static function error404()
		{
			header('HTTP/1.1 404 Not Found');
			header('Status: 404');
			echo '<h1>404 ' . __('http-404', '@core') . '</h1>';
			echo '<p>' . __('http-404-message', '@core') . '</p>';
			echo '<p><a href="' . $GLOBALS['site']['url'] . '">' . $GLOBALS['site']['url'] . '</a></p>';
		}

		public static function data_image()
		{
			$file = $_REQUEST['file'];
			$method = $_REQUEST['method'];
			$fileX = explode('-', $file);
			$methodX = explode(',', $method);

			if (!$method)
			{
				header('Location: '.$GLOBALS['site']['path'].'data/blob/' . $file);
				return;
			}

			if (in_array($methodX[0], array('scale', 'scalecrop', 'scaleexpand')))
			{
				//TODO
			}
			else if (isset($methodX[1]))
			{
				$suffix = $methodX[1];
				$file_orig = $GLOBALS['site']['data'] . 'blob/' . $file . $suffix;
				$file_trans = $GLOBALS['site']['data'] . 'blob/' . $file . $suffix;
				if (is_readable($file_trans) && (filemtime($file_trans) > filemtime($file_orig)))
				{
					header('Location: '.$GLOBALS['site']['path'].'data/blob/' . $file . $suffix);
					return;
				}
				else if (o($fileX[0]))
				{
					$object = o($fileX[0]);
					$field = $methodX[0];
					if (isset($object->$field))
					{
						$field = $object->f($field);
						$field->imageTransform($file);
						header('Location: '.$GLOBALS['site']['path'].'data/blob/' . $file . $suffix);
						return;
					}
				}
			}

			echo 'error';
		}
		
		public static function data_blob_rename()
		{
/*			$file = $_REQUEST['file'];
			$newName = $_REQUEST['new-name']; 
			$path = $GLOBALS['site']['data'] . 'blob/' . $file;

			if (is_readable($path))
			{
				if (preg_match('~^([\w_]+)-([\w_]+)-(\d+)-(.*)\.([\w\d]+)$~', $file, $matches))
				{
					//$module = $matches[1];
					$type = strtr($matches[2], '_', '/');
					//$timestamp = strtotime($matches[3]);
					//$name = $matches[4];
					//$extension = $matches[5];
					$disposition = 'attachment';
					
					$parameters = '';
					$parameters .= '; filename="'.$newName.'"';
					$parameters .= '; size="'.filesize($path).'"';
					
					header('Content-Type: ' . $type);
					header('Content-Disposition: ' . $disposition . $parameters);
					readfile($path);
					return;
				}
			}
				
			self::error404();*/
			$file = U::request('file');;
			$newName = U::request('new-name'); 
			$path = $GLOBALS['site']['data'] . 'blob/' . $file;

			if (is_readable($path))
			{
				$meta = array();
				if (is_readable($path . '.meta'))
					$meta = U::metaStringToArray(file_get_contents($path . '.meta'));
				//if (preg_match('~^([\w_]+)-([\w_]+)-(\d+)-(.*)\.([\w\d]+)$~', $file, $matches))
				{
					//$module = $matches[1];
					//$type = strtr($matches[2], '_', '/');
					//$timestamp = strtotime($matches[3]);
					//$name = $matches[4];
					//$extension = $matches[5];
					$disposition = 'attachment';
					
					$type = $meta['type'];
					if (!$newName)
						$newName = @$meta['name'];
					if (!$newName)
						$newName = 'unknown_file';
					
					$parameters = '';
					$parameters .= '; filename="'.$newName.'"';
					$parameters .= '; size="'.filesize($path).'"';
					
					header('Content-Type: ' . $type);
					header('Content-Disposition: ' . $disposition . $parameters);
					readfile($path);
					return;
				}
			}
				
			self::error404();
		}

		public static function captcha($id = null)
		{
			$var = 'captcha';
			$id = U::request('id');
			if ($id)
				$var .= '-' . $id;

			$font = self::get('captcha-font', 'fast_money');
			$font = U::fef('web/_fonts/'.$font.'/'.$font.'.ttf');
			$width = 200; $height = 150;
			$size = 50;
			$code = $_SESSION[$var] = U::randomString(6);

			$angle = mt_rand(0, 20) - 10;
			$bounding_box = imagettfbbox($size, $angle, $font, $code);
			$width_text = $bounding_box[4] - $bounding_box[0]; $height_text = $bounding_box[5] - $bounding_box[1];
			$x = (int) (($width - $width_text) / 2) + mt_rand(0, $width / 40);
			$y = (int) (($height - $height_text) / 2) + mt_rand(0, $height / 10);

			$image = imagecreate($width, $height);
			//$color_background_red = mt_rand(0, 255); $color_background_green = mt_rand(0, 255); $color_background_blue = mt_rand(0, 255);
			$color_background_red = 255; $color_background_green = 255; $color_background_blue = 255;
			$color_background = imagecolorallocate($image, $color_background_red, $color_background_green, $color_background_blue);
			$color_text = imagecolorallocate($image, 255 - $color_background_red, 255 - $color_background_green, 255 - $color_background_blue);

			imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, $color_background);
			imagettftext($image, $size, $angle, $x, $y, $color_text, $font, $code);

			header('Content-Type: image/jpeg');
			echo imagejpeg($image);
			imagedestroy($image);
		}

		public static function captchaOK($value, $id = null)
		{
			$var = 'captcha';
			if ($id === null)
				$id = U::request('id');
			if ($id)
				$var .= '-' . $id;
			$original = @$_SESSION[$var];
			if (!$original)
				return false;
			$from = 'oi';
			$to   = '01';
			$original = strtr(strtolower($original), $from, $to);
			$value = strtr(strtolower($value), $from, $to);
			return ($original == $value);
		}

		public static function process($phase)
		{
			self::$phase = $phase;
			if ($GLOBALS['page']['log'] & LOG_CORE_PROCESS)
				U::log('core', 'time', 'process ' . $phase . ' (' . Log::time(true) . ')');

			if ($phase == 0)
			{
				mb_internal_encoding('UTF-8');
				Session::initialize();
				ViewState::initialize();
				History::initialize();
				Locale::initialize();
				//History::$active = false;
				register_shutdown_function(array('History', 'save'));

				$GLOBALS['invocations'] = array();
				$GLOBALS['modules'] = array();
				$GLOBALS['templates'] = array();
				$GLOBALS['objects'] = array();
				$GLOBALS['db'] = null;

				self::location();

				Locale::module('@core');
				Locale::module('@meta');

				Router::add(Router::TYPE_EQUALS, 'captcha', '', array('page.location' => 'captcha'));
				Router::add(Router::TYPE_EQUALS, '404', '', array('page.location' => '404'));
				Router::add(Router::TYPE_REGEX, 'data/image/([^/]+)/(.*)', '', array('page.location' => 'data_image'), array('file', 'method'));
				Router::add(Router::TYPE_REGEX, 'data/blob-rename/([^/]+)/(.*)', '', array('page.location' => 'data_blob_rename'), array('file', 'new-name'));
				Router::process();
				if ($GLOBALS['page']['location'] == 'captcha')
					$GLOBALS['templates'][0] = new Template(0, array('Core', 'captcha'), 'callback', 'php');
				if ($GLOBALS['page']['location'] == 'data_image')
					$GLOBALS['templates'][0] = new Template(0, array('Core', 'data_image'), 'callback', 'php');
				if ($GLOBALS['page']['location'] == 'data_blob_rename')
					$GLOBALS['templates'][0] = new Template(0, array('Core', 'data_blob_rename'), 'callback', 'php');
					
				if (!$GLOBALS['page']['simple'])
				{
					Router::add(Router::TYPE_EQUALS, 'a/', '', array('page.location' => 'a'));
					Router::add(Router::TYPE_EQUALS, 'aa/', '', array('page.location' => 'aa'));
					Router::add(Router::TYPE_EQUALS, 'apda/', '', array('page.location' => 'apda'));
					Router::add(Router::TYPE_EQUALS, 'admin/', '', array('page.location' => 'administration')); //HACK: different location
					Router::add(Router::TYPE_EQUALS, 'administration/', '', array('page.location' => 'administration'));
					Router::process();

					if (($GLOBALS['page']['location'] == 'a') || ($GLOBALS['page']['location'] == 'aa') || ($GLOBALS['page']['location'] == 'administration') || ($GLOBALS['page']['location'] == 'apda'))
					{
						//History::$save_current = false;
						$GLOBALS['page']['administration'] = true;
						$GLOBALS['page']['title'] = __('administration', '@core');
					}
				}
			}

			if ($phase == 1)
			{
				if (!$GLOBALS['page']['simple'])
				{
					require_once('Field.php');
					require_once('Object.php');
					require_once('Form.php');
					require_once('Action.php');
					require_once('Module.php');
					require_once('ObjectModule.php');
				}

				if (!$GLOBALS['page']['simple'])
				{
					self::database();
					object::$_ = new Objects();
				}

				if (isset($GLOBALS['site']['flags']['settings']))
					Core::module('@settings');
				if (isset($GLOBALS['site']['flags']['attributes']))
				{
					Core::module('@attribute_types');
					Core::module('@attributes');
				}
				Core::module('@users');
			}

			if (($phase == 2) && !$GLOBALS['page']['simple'])
			{
				ob_start();

				/*Setting::register(new Setting('@core', new Field('textarea-html-editor', Field::TYPE_STRING, 16, 'default', array(
					'select' => true,
					'values' => array(
						'default' => __('setting-textarea-html-editor-default', '@core'),
						'' => __('setting-textarea-html-editor-none', '@core'),
						'fckeditor' => __('setting-textarea-html-editor-fckeditor', '@core'),
						'tiny_mce' => __('setting-textarea-html-editor-tiny_mce', '@core'),
					),
				))));*/

				for ($p = -3; $p <= 0; $p++)
					foreach ($GLOBALS['modules'] as $module)
					{
						if ($GLOBALS['page']['log'] & LOG_MODULE_INITIALIZE)
							U::log('core', 'modules: initialize (' . $p . ')', $module->id);
						Locale::module($module->id);
						$module->initialize($p);
					}

				$GLOBALS['page']['content'] .= ob_get_clean();
			}

			if ($phase == 3)
			{
				ob_start();

				if (!$GLOBALS['page']['simple'])
				{
					for ($p = 1; $p < 10; $p++)
						foreach ($GLOBALS['modules'] as $module)
						{
							if ($GLOBALS['page']['log'] & LOG_MODULE_INITIALIZE)
								U::log('core', 'modules: initialize (' . $p . ')', $module->id);
							Locale::module($module->id);
							$module->initialize($p);
						}
					foreach ($GLOBALS['modules'] as $module)
					{
						if ($GLOBALS['page']['log'] & LOG_MODULE_INDEX)
							U::log('core', 'modules: index', $module->id);
						Locale::module($module->id);
						$module->index();
					}
				}

				if (!isset($GLOBALS['templates']['head']))
					$GLOBALS['templates']['head'] = new Template(false, array('Core', 'head'), 'callback', 'php');

				if ($GLOBALS['page']['simple'])
				{
					if (!isset($GLOBALS['templates']['main']))
						Core::frame('#locale#/simple/' . $GLOBALS['page']['location']);
				}

				$GLOBALS['page']['content'] .= ob_get_clean();
			}

			if ($phase == 4)
			{
				ob_start();

				if (!$GLOBALS['page']['simple'])
				{
					if ($GLOBALS['page']['administration'])
					{
						foreach ($GLOBALS['modules'] as $module)
							$module->generateAdministrationMenu();
						Core::process(-1);
						$GLOBALS['templates'][0] = new Template(0, array('Core', 'html'), 'callback', 'php');
						if ($GLOBALS['page']['location'] == 'administration')
						{
							$administration_version = Core::get('administration-theme', 3);
							$template = 'web/_administration/'.$GLOBALS['page']['location'].'_v'.$administration_version.'.php';
						}
						else
							$template = 'web/_administration/'.$GLOBALS['page']['location'].'.php';
						$GLOBALS['templates']['main'] = new Template(false, $template, 'file', 'php');
						if ($GLOBALS['page']['location'] == 'apda')
							self::flagSet('reduced-meta', true);
					}
				}

				if ($GLOBALS['page']['location'] === null)
				{
					$GLOBALS['page']['location'] = '404';
					Core::location();
				}

				if (Core::location('404') || !isset($GLOBALS['templates'][0]) || !$GLOBALS['templates'][0] || (isset($GLOBALS['templates']['frame']) && !isset($GLOBALS['templates']['main'])))
				{
					$GLOBALS['page']['simple'] = true;
					$GLOBALS['page']['title'] = '404 ' . __('http-404', '@core');
					$GLOBALS['templates'][0] = new Template(0, array('Core', 'html'), 'callback', 'php');
					$GLOBALS['templates']['head'] = new Template(null, '', 'string', 'html');
					$GLOBALS['page']['head'] = '';
					unset($GLOBALS['templates']['frame']);
					$GLOBALS['templates']['main'] = new Template(null, array('Core', 'error404'), 'callback', 'php');
				}

				if (__('path*0*'.$GLOBALS['page']['location'], '@meta', false) !== false)
				{
					$counter = 0;
					while (($temp = __('path*'.$counter++.'*'.$GLOBALS['page']['location'], '@meta', false)))
					{
						$temp = explode("\t", $temp);
						$url = array_shift($temp); if ($url == '~') $url = false;
						$text = array_pop($temp); if ($text == '~') $text = false;
						if ($url)
						{
							$temp = Router::url($url, array(), false);
							if ($temp)
							{
								if (!$text)
								{
									$route = Router::find($url);
									if (isset($route['args']['page.title']))
										$text = $route['args']['page.title'];
									else
										$text = __('title*'.$url, '@meta', false);
								}
								$url = $temp;
							}
						}

						$GLOBALS['page']['path'][] = array(
							'text' => $text,
							'url' => $url,
						);
					}
					self::title();
				}
				if (($temp = __('title*'.$GLOBALS['page']['location'], '@meta', false)) !== false)
					$GLOBALS['page']['path'][] = array(
						'text' => $GLOBALS['page']['title'] = $temp,
						'url' => false,
					);
				if (($temp = __('keywords*'.$GLOBALS['page']['location'], '@meta', false)) !== false)
					$GLOBALS['page']['keywords'] = $temp;
				if (($temp = __('description*'.$GLOBALS['page']['location'], '@meta', false)) !== false)
					$GLOBALS['page']['description'] = $temp;

				$GLOBALS['page']['content'] .= ob_get_clean();
			}

			if ($phase == 5)
			{
				Log::rename($_SERVER['REMOTE_ADDR'] . '-' . date('Ymd-His') . '-' . strtr($GLOBALS['page']['location'], '/', '-'));

				ob_start();
				if (!$GLOBALS['page']['simple'])
				{
					if (!$GLOBALS['invocation'])
					{
						$action = Action::find($GLOBALS['page']['location']);
						if ($action)
						{
							$module = $action->module();
							$GLOBALS['invocation'] = new Invocation($action);
							$GLOBALS['invocation']->active = true;
							$prefix = $action->prefix();
							foreach ($action->optionsUnsafe as $k)
								if (isset($_REQUEST[$k]))
									$GLOBALS['invocation']->options[$k] = $_REQUEST[$k];

							if ($GLOBALS['page']['title-action'] === null)
								$GLOBALS['page']['title-action'] = __('module-name', $module->id) . ' - ' . __($action->id, $module->id);
						}
					}

					for ($p = 0; $p < 10; $p++)
						foreach ($GLOBALS['modules'] as $module)
						{
							if ($GLOBALS['page']['log'] & LOG_MODULE_PROCESS)
								U::log('core', 'module: process (' . $p . ')', $module->id);
							Locale::module($module->id);
							$module->process($p);
						}
				}
				$GLOBALS['page']['content'] .= ob_get_clean();

				$file = 'controllers/' . strtr($GLOBALS['page']['location'], '/-', '__') . '.php';
				if ($file = U::firstExistingFile($GLOBALS['site']['base'], $file))
				{
					include($file);
					$GLOBALS['R'] = array();
					ob_start();
					controller();
					$GLOBALS['page']['controller-content'] = ob_get_clean();
				}

				if (isset($GLOBALS['templates'][0]))
					print($GLOBALS['templates'][0]->process());
				else
					die('template error');
				print($GLOBALS['page']['content']);
			}

			if (($phase == -1) && !$GLOBALS['page']['simple'])
			{
				$action = null;
				if ($actionId = U::request('page.invocation'))
					$action = Action::find($actionId);
				if ($action)
				{
					$module = $action->module();
					$GLOBALS['invocation'] = new Invocation($action);
					$GLOBALS['invocation']->active = true;
					foreach ($action->optionsUnsafe as $k)
						if (isset($_REQUEST[$k]))
							$GLOBALS['invocation']->options[$k] = $_REQUEST[$k]; 

					if ($GLOBALS['page']['title-action'] === null)
						$GLOBALS['page']['title-action'] = __('module-name', $module->id) . ' - ' . __($action->id, $module->id);
				}
			}

			if (class_exists('Web'))
				Web::process($phase);
		}

		public static function title($part = '', $page = null, $arg0 = null)
		{
			if ($page === null)
			{
				$prepared = Core::get('title-prepared', false);
				if (!$prepared)
					foreach ($GLOBALS['modules'] as $module)
						if (!$prepared)
							$prepared = $prepared || $module->page();
				Core::set('title-prepared', $prepared);

				$page = $GLOBALS['page'];
				$modify = true;
			}

			$temp = $page['path'];
			$group = null; $title = null;
			foreach ($temp as $temp2 => $temp1)
				if (($group === null) && !isset($temp1['no-title']))
				{
					$group = $temp1['text'];
					unset($temp[$temp2]);
				}
			foreach (array_reverse($temp) as $temp2 => $temp1)
				if (($title === null) && !isset($temp1['no-title']))
					$title = $temp1['text'];

			if (isset($modify))
				$GLOBALS['page']['title'] = $title . ($title ? self::get('title-separator') : '') . $group;

			$result = '';
			if ($part == 'path-text')
			{
				$result = array();
				foreach ($page['path'] as $i => $path)
					$result[] = HTML::e($path['text']);
				$result = '<span class="path-text">' . implode(self::get('path-separator'), $result) . '</span>';
			}
			else if (($part == 'path') || ($part == 'path-header'))
			{
				$result = array();
				foreach ($page['path'] as $i => $path)
				{
					$class = 'level' . $i;
					if ($i == 0)
						$class .= ' first';
					if ($i == (count($page['path']) - 1))
						$class .= ' last';
					if ($path['url'])
						$result[] = '<a class="' . $class . '" href="' . HTML::e($path['url']) . '">' . HTML::e($path['text']) . '</a>';
					else
						$result[] = '<span class="' . $class . '">' . HTML::e($path['text']) . '</span>';
				}
				$result = implode(self::get('path-separator'), $result);
				if ($part == 'path-header')
				{
					$h2 = self::get('h2', 'h2');
					$result = '<'.$h2.'>' . $result . '</'.$h2.'>';
				}
				else
					$result = '<span class="path">' . $result . '</span>';
			}
			else
			{
				$h2 = self::get('h2', 'h2');
				$h3 = self::get('h3', 'h3');
				$title = HTML::e($title);
				$group = HTML::e($group);
				$style_with_modifiers = ($arg0 ? $arg0 : self::get('title-style'));
				$sep = ($title ? self::get('path-separator') : '');
				$style = trim($style_with_modifiers, '=0');
				switch ($style)
				{
					case 'none':
						break;
					case 'title':
						$result .= "<$h2>$title</$h2>";
						break;
					case 'separate':
						$result .= "<$h2>$group</$h2>";
						$result .= "<$h3>$title</$h3>";
						break;
					case 'plain':
						$result .= "$group$sep$title";
						break;
					case 'strong-span':
						$result .= "<$h2><strong>$group</strong><span>$sep$title</span></$h2>";
						break;
					case 'span-strong':
						$result .= "<$h2><span><strong>$group$sep</strong>$title</span></$h2>";
						break;
					case 'sprintf':
						$result .= sprintf(self::get('title-template'), $group, $title, $sep);
						break;
					case 'pseudoSmarty':
						$result .= U::pseudoSmarty(self::get('title-template'), array(
								'group' => $group,
								'title' => $title,
								'separator' => $sep,
								'group|urlize' => U::urlize($group),
								'title|urlize' => U::urlize($title),
							)); 
						break;
					case 'default':
					default:
						$result .= "<$h2>$group$sep$title</$h2>";
						break;
				}
				if (strpos($style_with_modifiers, '=') === false)
					$result = '<div class="' . $h2 . '">' . $result . '</div>';
				if (isset($modify))
					$GLOBALS['page']['h2'] = $result;
				if (strpos($style_with_modifiers, '0') !== false)
					$result = '';
			}
			return $result;
		}

		public static function common($part)
		{
			$args = func_get_args(); array_shift($args);
			$RESULT = '';
			if ($part == 'messages')
			{
				foreach ($GLOBALS['invocations'] as $invocation)
					foreach ($invocation->messages as $message)
						$RESULT .= '<div class="'.$message['type'].'">'.$message['text'].'</div>';
			}
			else if ($part == 'output')
			{
				foreach ($GLOBALS['invocations'] as $invocation)
					if ($invocation->output)
					{
						$action = $invocation->action();
						$module = $action->module();
						$RESULT .= '<div class="module_action module_'.$module->id.'_action_'.$action->id.' module_'.$module->id.' action_'.$action->id.'">' . "\n";
						foreach ($invocation->output as $block => $text)
							$RESULT .= $text;
						$RESULT .= "\n" . '</div>';
					}
			}
			else if ($part == 'messages-output')
			{
				$RESULT .= '<div class="messages"' . (isset($args[0]) ? ' id="messages"' : '') . '>';
				$RESULT .= self::common('messages');
				$RESULT .= '</div>';
				$RESULT .= self::common('output');
			}
			else if ($part == 'messages-output-0')
			{
				foreach ($GLOBALS['invocations'] as $invocation)
					foreach ($invocation->messages as $message)
						$RESULT .= $message['type'] . ': ' . $message['text'] . "\n";
				foreach ($GLOBALS['invocations'] as $invocation)
					foreach ($invocation->output as $block => $text)
						$RESULT .= $text;
			}
			else if ($part == 'pages')
			{
				$pager = $args[0];
				if ($pager && ($links = $pager->getLinks()) && $links['all'])
				{
					$RESULT .= '<div class="pages pages_' . (isset($args[1]) ? $args[1] : '') . '">';
					$RESULT .= $pager->selectBox;
					$RESULT .= $links['all'];
					$RESULT .= '</div>';
				}
			}
			else if ($part == 'thumbnail')
			{
				$image = (is_object($args[0]) ? $args[0]->image : $args[0]);
				$text = @$args[1];
				$url = @$args[2];
				$suffix = @$args[3];
				if ($suffix === null)
					$suffix = '.thumb';
				$fallback = @$args[4];
				$slimbox = (string) @$args[5];
				if (is_object($args[0]) && ($text === null) && isset($args[0]->title))
					$text = $args[0]->title;
				if (is_object($args[0]) && ($text === null))
					$text = $args[0]->otitle;
				if (($url !== false) && is_object($args[0]) && isset($args[0]->url))
					$url .= $args[0]->url;
				if (is_object($args[0]) && ($slimbox === null))
					$slimbox = '[' . md5($args[0]->oid) . ']';

				$RESULT .= '<div class="thumbnail">';
				if (!$image && $fallback)
				{
					if ($url)
						$RESULT .= '<a href="' . HTML::e($url) . '"><img src="' . $GLOBALS['site']['path'] . $fallback . '" alt="' . HTML::e($text) . '" title="' . HTML::e($text) . '" /></a>';
					else
						$RESULT .= '<img src="' . $GLOBALS['site']['path'] . $fallback . '" alt="' . HTML::e($text) . '" title="' . HTML::e($text) . '" />';
				}
				else if ($image)
				{
					if ($url)
						$RESULT .= '<a href="' . HTML::e($url) . '">';
					else
					{
						$fullsuffix = '.full';
						if (!is_readable($GLOBALS['site']['data'] . 'blob/' . $image . $fullsuffix))
							$fullsuffix = '';
						$RESULT .= '<a href="' . $GLOBALS['site']['path'] . 'data/blob/' . $image . $fullsuffix . '" rel="slimbox-'.$slimbox.'" title="' . HTML::e($text) . '">';
					}
					$RESULT .= '<img src="' . $GLOBALS['site']['path'] . 'data/blob/' . $image . $suffix . '" alt="' . HTML::e($text) . '" title="' . HTML::e($text) . '" />';
					$RESULT .= '</a>';
				}
				$RESULT .= '</div>';
			}
			else if ($part == 'images')
			{
				$images = array();
				if (is_array($args[0]))
					$images = $args[0];
				else if (is_object($args[0]) && isset($args[0]->images))
					$images = $args[0]->images;
				$id = @$args[1];
				$url = @$args[2];
				$suffix = @$args[3];
				$fallback = @$args[4];
				$slimbox = @$args[5];
				if ($slimbox === null)
					$slimbox = '[' . mt_rand(1, 1000000) . ']';
				$layout = @$args[6];
				if ($layout === null)
					$layout = 'div';
				$layout_arg = @$arg[7];
				if ($layout_arg === null)
					$layout_arg = 3;

				if ($id)
					$images = (isset($images[$id]) ? array($images[$id]) : array());
				$temp = $images; $images = array();
				foreach ($temp as $k => $image)
					if (substr($image['type'], 0, 6) == 'image/')
					{
						if (!@$image['text'.LL] && is_object($args[0]) && isset($args[0]->title))
							$text = $args[0]->title;
						else if (!@$image['text'.LL] && is_object($args[0]))
							$text = $args[0]->otitle;
						else
							$text = @$image['text'.LL];
						$image['text'] = $text;
						$images[] = $image;
					}

				$RESULT .= '<'.$layout.' class="attachments attachments_images images">';

				if ($layout == 'div')
				{
					foreach ($images as $image)
						$RESULT .= self::common('thumbnail', @$image['blob'], $image['text'], $url, $suffix, $fallback, $slimbox);
				}
				else
				{
					$table = array();
					$temp0 = (int) ceil(count($images) / $layout_arg); 
					reset($images);
					for ($y = 0; $y < $temp0; $y++)
					{
						$RESULT .= '<tr class="images">';
						for ($x = 0; $x < $layout_arg; $x++)
						{
							$RESULT .= '<td>';
							$image = @$images[$y * $layout_arg + $x];
							if ($image)
								$RESULT .= self::common('thumbnail', @$image['blob'], $image['text'], $url, $suffix, $fallback, $slimbox);
							$RESULT .= '</td>';
						}
						$RESULT .= '</tr>';
						
						$RESULT .= '<tr class="texts">';
						for ($x = 0; $x < $layout_arg; $x++)
						{
							$RESULT .= '<td>';
							$image = @$images[$y * $layout_arg + $x];
							if ($image)
								$RESULT .= $image['text'];
							$RESULT .= '</td>';
						}
						$RESULT .= '</tr>';
					}
				}

				$RESULT .= '</'.$layout.'>';
			}
			else if ($part == 'attachments')
			{
				$blobs = array();
				if (is_array($args[0]))
					$blobs = $args[0];
				else if (is_object($args[0]) && isset($args[0]->attachments))
					$blobs = $args[0]->attachments;
				/*else if (is_object($args[0]) && isset($args[0]->blobs))
					$blobs = $args[0]->blobs;*/

				$RESULT .= '<div class="attachments attachments_nonimages">';

				if ($id && isset($blobs[$id]))
					$blobs = array($blobs[$id]);
				else
					foreach ($blobs as $blobid => $blob)
						if ($id || (substr($blob['type'], 0, 6) != 'image/'))
						{
							if (!@$blob['text'.LL] && is_object($args[0]) && isset($args[0]->title))
								$text = $args[0]->title;
							else if (!@$blob['text'.LL] && is_object($args[0]))
								$text = $args[0]->otitle;
							else
								$text = @$blob['text'.LL];
							$RESULT .= '<a href="/data/blob/'.@$blob['blob'].'">' . HTML::e($text) . '</a>';
						}

				$RESULT .= '</div>';
			}
			else if ($part == 'list-tree')
			{
				extract($args[0], EXTR_SKIP);
				$RESULT .= '<ul>';
				foreach ($tree as $object)
				{
					$RESULT .= '<li>';
					$RESULT .= $list->urlOP(null, $object, $object->otitle, 7);
					if ($object->x['tree']->size())
						$RESULT .= self::common('list-tree', array('list' => $list, 'tree' => $object->x['tree']));
					$RESULT .= '</li>';
				}
				$RESULT .= '</ul>';
			}
			else if ($part == 'list')
			{
				extract($args[0], EXTR_SKIP);
				$action = $invocation->action();
				$module = $action->module();
				$object = $module->object;

				$RESULT .= '<div class="actions_top">';
				foreach ($result['actions'] as $ref)
					if (($ref->context != 'object') && $ref->prepareOP(null, $result['parent']))
					{
						$temp0 = $ref->icon;
						$ref->icon = 'x';
						$RESULT .= $ref->urlOP(null, $result['parent'], null, 7);
						$ref->icon = $temp0;
					}
				$RESULT .= '</div><!-- /actions_top -->';

				if ($object->get('tree'))
				{
					$RESULT .= '<div class="parents">';
					$RESULT .= $result['list']->urlOP(null, $object, l('top', '@core'), 7);
					$parents = $result['parent']->parents();
					$parents->add($result['parent']);
					foreach ($parents as $parent)
						if ($parent->oid)
							$RESULT .= ' &raquo; ' . $result['list']->urlOP(null, $parent, $parent->otitle, 7);
					$RESULT .= '</div>';
				}

				$RESULT .= '<table class="layout"><tr>';

				if ($object->get('tree'))
				{
					$RESULT .= '<td class="layout left" valign="top"><div class="tree">';
					if (isset($tree_content))
						$RESULT .= $tree_content;
					else
					{
						$get = new Invocation('get', $module->id);
						$groups = $get->dispatch(array('oid' => null, 'groups' => true), array(), true);
						$RESULT .= self::common('list-tree', array('list' => $result['list'], 'tree' => $groups['objects']['tree']));
					}
					$RESULT .= '</div></td>';
				}

				$RESULT .= '<td class="layout right" valign="top">';

				if ($result['filter_form'])
				{
					$RESULT .= '<div class="filter">';
					if (($GLOBALS['page']['location'] != 'a') && ($GLOBALS['page']['location'] != 'aa'))
						if ($temp = Template::prepare($module->id . '-list-filter-form'))
							$result['filter_form']->template = $temp;
					if (!$result['filter_form']->template)
						$result['filter_form']->template = new Template(null, ':@list-filter-form', null, null);
					$RESULT .= $result['filter_form']->render();
					$RESULT .= '</div>';
				}

				if (count($result['order_fields']) > 1)
				{
					$o = array_values($result['order']);
					$RESULT .= '<div class="order">';
					$RESULT .= '<form action="' . HTML::e(Router::linkToCurrent()) . '"  method="post"';
					$RESULT .= '><div>';
					for ($i = 0; $i < $invocation->get('order-count', 1); $i++)
					{
						 $RESULT .= '<select name="' . $action->prefix('order[]') . '">';
						 foreach ($result['order_fields'] as $k => $v)
						 	$RESULT .= '<option value="' . $k . '"' . ((isset($o[$i]) && ($o[$i] == $k)) ? ' selected="selected"' : '') . '>' . HTML::e($v) . '</option>';
						 $RESULT .= '</select><input type="submit" value="' . l('order-submit', '@core') . '" /></div></form>';
					}
					$RESULT .= '</div>';
				}

				$RESULT .= self::common('pages', @$result['pager'], 'top');

				if ($template_objects = $invocation->get('template-objects'))
					$RESULT .= $template_objects->process(array('invocation' => $invocation, 'result' => $result));
				else
				{
					$RESULT .= '<table>';
					$header_row = '<tr>';
					foreach ($result['columns'] as $column)
						$header_row .= '<th>' . $column->text . '</th>';
					$header_row .= '<th>' . l('actions', '@core') . '</th></tr>';
					$RESULT .= $header_row;
					$counter = 0;
					foreach ($result['objects'] as $object)
					{
						$style = $object->listStyle();
						$RESULT .= '<tr' . ($style ? (' style="'. $style . '"') : '') . ' class="' . ((++$counter % 2) ? 'odd' : 'even') . '">';
						foreach ($result['columns'] as $column)
						{
							$style = $object->listStyle($column);
							$RESULT .= '<td' . ($style ? (' style="'. $style . '"') : '') . '>';
							$RESULT .= $column->value($object);
							$RESULT .= '</td>';
						}
						$RESULT .= '<td class="actions">';
						foreach ($result['actions'] as $ref)
							if (($ref->context == 'object') && $ref->prepareOP($object, $result['parent']))
								$RESULT .= $ref->urlOP($object, $result['parent'], null, 7);
						$RESULT .= '</td>';

						$RESULT .= '</tr>';
					}
					$RESULT .= $header_row;
					$RESULT .= '</table>';
				}

				$RESULT .= self::common('pages', @$result['pager'], 'bottom');

				$RESULT .= '</td></tr></table>';
			}
			return $RESULT;
		}

		public static function panel($key, $text = null)
		{
			if (!isset($GLOBALS['panels']))
				$GLOBALS['panels'] = array();
			if ($text === null)
				return (isset($GLOBALS['panels'][$key]) ? $GLOBALS['panels'][$key] : null);
			else
				$GLOBALS['panels'][$key] = $text;
		}

		public static function load($url, $key = null)
		{
			$load = self::get('load', array());
			if ($key !== null)
				$load[$key] = $url;
			else
				$load[] = $url;
			self::set('load', $load);
		}
	}

	/****************************************************************************************************************/

	U::log('core', 'request', $_SERVER['REQUEST_URI']);

	if ($GLOBALS['site']['development'])
	{
		if ($GLOBALS['page']['log'] & LOG_TIME)
			register_shutdown_function(create_function('', "U::log('core', 'time', 'finished (' . Log::time(true) . ')');"));
		if ($GLOBALS['page']['log'] & LOG_INCLUDE)
			register_shutdown_function(create_function('', "U::log('core', 'included files', dump(get_included_files(), false, false, false));"));
		if ($GLOBALS['page']['log'] & LOG_PAGE)
			register_shutdown_function(create_function('', "U::log('core', 'page', dump(\$GLOBALS['page'], false, false, false));"));
		if ($GLOBALS['page']['log'] & LOG_SITE)
			register_shutdown_function(create_function('', "U::log('core', 'site', dump(\$GLOBALS['site'], false, false, false));"));
		if ($GLOBALS['page']['log'] & LOG_GET)
			register_shutdown_function(create_function('', "U::log('core', 'get', dump(\$_GET, false, false, false));"));
		if ($GLOBALS['page']['log'] & LOG_POST)
			register_shutdown_function(create_function('', "U::log('core', 'post', dump(\$_POST, false, false, false));"));
		if ($GLOBALS['page']['log'] & LOG_COOKIE)
			register_shutdown_function(create_function('', "U::log('core', 'cookie', dump(\$_COOKIE, false, false, false));"));
		if ($GLOBALS['page']['log'] & LOG_SERVER)
			register_shutdown_function(create_function('', "U::log('core', 'server', dump(\$_SERVER, false, false, false));"));
		if ($GLOBALS['page']['log'] & LOG_SESSION)
			register_shutdown_function(create_function('', "U::log('core', 'session', dump(\$_SESSION, false, false, false));"));
	}

	Core::set('title-separator', ' | ');
	Core::set('path-separator', '&nbsp;&raquo;&nbsp;');
?>