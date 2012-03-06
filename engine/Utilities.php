<?php
	// Author: Jakub Macek, CZ; Copyright: Poski.com s.r.o.; Code is 95% my work. Rest is significantly altered code from web. Do not copy.

	class E
	{
		public					$expression							= null;

		public function __construct($expression)
		{
			$this->expression = $expression;
		}

		public function evaluate($options = array())
		{
			return callback($this->expression, $options);
		}
	}

	class Log
	{
		public static			$start								= 0;
		public static			$time								= 0;

		public static function l($section, $path = null, $message = null)
		{
			if (!$GLOBALS['site']['development'])
				return;
			if (isset($GLOBALS['site']['log-ip']) && !in_array($_SERVER['REMOTE_ADDR'], $GLOBALS['site']['log-ip']))
				return;
			self::ll($GLOBALS['site']['data'] . 'logs/' . $GLOBALS['page']['log-file'], $section, $path, $message);
		}

		public static function ll($file, $section, $path = null, $message = null)
		{
			$f = fopen($file, 'a');
			if ($message === null)
				$text = $section . "\n";
			else
			{
				//$text = date('Y-m-d H:i:s') . ' [' . strtoupper(str_pad($section, 16)) . '] (' . str_pad($path, 50) . ') ' . trim(U::indent($message, str_repeat(' ', 93))) . "\n";
				if (strpos($message, "\n") !== false)
					$text = date('Y-m-d H:i:s') . ' [' . strtoupper(str_pad($section, 16)) . '] (' . str_pad($path, 50) . ') ' . "\n" . U::indent(trim($message), "\t\t") . "\n";
				else
					$text = date('Y-m-d H:i:s') . ' [' . strtoupper(str_pad($section, 16)) . '] (' . str_pad($path, 50) . ') ' . $message . "\n";
			}

			fwrite($f, $text);
			fclose($f);
			chmod($file, 0666);
		}

		public static function time($string = false)
		{
			if (!$string)
			{
				self::$time = microtime(true);
				return (self::$time - self::$start);
			}
			else
			{
				$temp = microtime(true);
				$result = round(($temp - self::$start), 1) . ' (+ ' . round(($temp - self::$time), 1) . ')';
				self::$time = $temp;
				return $result;
			}
		}

		public static function rename($new)
		{
			$old = $GLOBALS['site']['data'] . 'logs/' . $GLOBALS['page']['log-file'];
			if (!is_readable($old))
				return;
			rename($old, $GLOBALS['site']['data'] . 'logs/' . $new);
			$GLOBALS['page']['log-file'] = $new;
		}
	}

	class ContainerIterator implements Iterator
	{
		private					$container							= null;
		private					$method								= null;
		private					$keys								= array();

		public function __construct($container, $keys = null, $method = null)
		{
			$this->container = $container;
			$this->method = $method;
			if ($keys === null)
				$this->keys = $this->container->keys();
			else
				$this->keys = $keys;
		}

		public function size()						{ return count($this->keys); }
		public function rewind()					{ reset($this->keys); }
		public function current()					{ $key = current($this->keys); if ($key === false) return false; if ($this->method) return $this->container->{$this->method}($key); else return $this->container->$key; }
		public function key()						{ return current($this->keys); }
		public function next()						{ $key = next($this->keys); if ($key === false) return false; if ($this->method) return $this->container->{$this->method}($key); else return $this->container->$key; }
		public function valid()						{ return ($this->current() !== false); }
		public function keys()						{ return $this->keys; }
	}

	class HTML
	{
		public static function attributes($attrs)
		{
			if (is_array($attrs))
			{
				$temp = ' ';
				foreach ($attrs as $k => $v)
					if (is_int($k))
						$temp .= htmlentities($v) . ' ';
					else
						$temp .= htmlentities($k) . '="' . htmlentities($v) . '" ';
				$temp .= ' ';
				$attrs = $temp;
			}
			return ' ' . trim($attrs) . ' ';
		}

		public static function e($string)
		{
			if (!isset($GLOBALS['HTML_e_table']))
				$GLOBALS['HTML_e_table'] = array(
				'¡' => '&iexcl;', '¢' => '&cent;', '£' => '&pound;', '¤' => '&curren;', '¥' => '&yen;', '¦' => '&brvbar;',
				'§' => '&sect;', '¨' => '&uml;', '©' => '&copy;', 'ª' => '&ordf;', '«' => '&laquo;', '¬' => '&not;', '­' => '&shy;',
				'®' => '&reg;', '¯' => '&macr;', '°' => '&deg;', '±' => '&plusmn;', '²' => '&sup2;', '³' => '&sup3;', '´' => '&acute;',
				'µ' => '&micro;', '¶' => '&para;', '·' => '&middot;', '¸' => '&cedil;', '¹' => '&sup1;', 'º' => '&ordm;',
				'»' => '&raquo;', '¼' => '&frac14;', '½' => '&frac12;', '¾' => '&frac34;', '¿' => '&iquest;', '×' => '&times;',
				'÷' => '&divide;', 'ƒ' => '&fnof;', '•' => '&bull;', '…' => '&hellip;', '′' => '&prime;', '″' => '&Prime;',
				'‾' => '&oline;', '⁄' => '&frasl;', '℘' => '&weierp;', 'ℑ' => '&image;', 'ℜ' => '&real;', '™' => '&trade;',
				'ℵ' => '&alefsym;', '←' => '&larr;', '↑' => '&uarr;', '→' => '&rarr;', '↓' => '&darr;', '↔' => '&harr;',
				'↵' => '&crarr;', '⇐' => '&lArr;', '⇑' => '&uArr;', '⇒' => '&rArr;', '⇓' => '&dArr;', '⇔' => '&hArr;',
				'∀' => '&forall;', '∂' => '&part;', '∃' => '&exist;', '∅' => '&empty;', '∇' => '&nabla;', '∈' => '&isin;',
				'∉' => '&notin;', '∋' => '&ni;', '∏' => '&prod;', '∑' => '&sum;', '−' => '&minus;', '∗' => '&lowast;',
				'√' => '&radic;', '∝' => '&prop;', '∞' => '&infin;', '∠' => '&ang;', '∧' => '&and;', '∨' => '&or;', '∩' => '&cap;',
				'∪' => '&cup;', '∫' => '&int;', '∴' => '&there4;', '∼' => '&sim;', '≅' => '&cong;', '≈' => '&asymp;',
				'≠' => '&ne;', '≡' => '&equiv;', '≤' => '&le;', '≥' => '&ge;', '⊂' => '&sub;', '⊃' => '&sup;', '⊄' => '&nsub;',
				'⊆' => '&sube;', '⊇' => '&supe;', '⊕' => '&oplus;', '⊗' => '&otimes;', '⊥' => '&perp;', '⋅' => '&sdot;',
				'◊' => '&loz;', '♠' => '&spades;', '♣' => '&clubs;', '♥' => '&hearts;', '♦' => '&diams;', '"' => '&quot;',
				'&' => '&amp;', '<' => '&lt;', '>' => '&gt;', 'ˆ' => '&circ;', '˜' => '&tilde;', ' ' => '&ensp;', ' ' => '&emsp;',
				' ' => '&thinsp;', '‌' => '&zwnj;', '‍' => '&zwj;', '‎' => '&lrm;', '‏' => '&rlm;', '–' => '&ndash;',
				'—' => '&mdash;', '‘' => '&lsquo;', '’' => '&rsquo;', '‚' => '&sbquo;', '“' => '&ldquo;', '”' => '&rdquo;',
				'„' => '&bdquo;', '†' => '&dagger;', '‡' => '&Dagger;', '‰' => '&permil;', '‹' => '&lsaquo;', '›' => '&rsaquo;',
				'€' => '&euro;');
			return strtr($string, $GLOBALS['HTML_e_table']);
		}

		public static function pager($options)
		{
			require_once('third-party/PEAR/Pager.php');
			$opts = array(
				'perPage' => $options['page-size'],
				'delta' => (isset($options['delta']) ? $options['delta'] : 5),
				'mode' => (isset($options['mode']) ? $options['mode'] : 'Sliding'),
				'currentPage' => $options['page'],
				'urlVar' => (isset($options['key']) ? $options['key'] : 'page'),
				'curPageLinkClassName' => 'selected',
				'clearIfVoid' => true,
				'useSessions' => false,
				'excludeVars' => array('page.panel', 'page.viewstate', 'page_panel', 'page_viewstate', '_'),
				'extraVars' => array(),
			);

			if (isset($options['page-count']))
				$opts['totalItems'] = $options['page-count'] * $options['page-size'];
			else
				$opts['itemData'] = $options['pages'];

			foreach (array('altFirst', 'altPrev', 'altNext', 'altLast', 'altPage', 'prevImg', 'nextImg', 'separator',
				'spacesBeforeSeparator', 'spacesAfterSeparator', 'firstLinkTitle', 'prevLinkTitle', 'nextLinkTitle',
				'lastLinkTitle', 'curPageSpanPre', 'curPageSpanPost', 'firstPagePre', 'firstPageText', 'firstPagePost',
				'lastPagePre', 'lastPageText', 'lastPagePost') as $key)
				if (($temp = __('pager-'.$key, '@core', false)) !== false)
					$opts[$key] = $temp;

			if (isset($options['url']))
			{
				$opts['append'] = false;
				$opts['fileName'] = $options['url'];
			}

			if ($GLOBALS['page']['panel'])
				$opts['onclick'] = "application.loadPanel(this.href, '{$GLOBALS['page']['panel']}'); return false;";
			if ($GLOBALS['page']['viewstate'])
				$opts['extraVars']['page.viewstate'] = $GLOBALS['page']['viewstate'];
				
			$result = Pager::factory($opts);
			if (isset($options['url']))
			{
				$result->_path = '/';
				$result->_url = '/';
				$result->build();
			}

			$temp = $result->getPageSelectBox(array('autoSubmit' => true));
			if ($GLOBALS['page']['panel'])
				$temp = preg_replace('~onchange="document\.location\.href=([^"]+)"~', 'onchange="application.loadPanel(\1, \'' . $GLOBALS['page']['panel'] . '\')"', $temp);
			$result->selectBox = $temp;

			return $result;
		}

		public static function js($type)
		{
			if ($type === 0)
				return '<script type="text/javascript"><!--//--><![CDATA[//><!--' . "\n";
			else if ($type === 1)
				return "\n" . '//--><!]]></script>';
			else if (is_string($type))
				return self::js(0) . $type . self::js(1);
			else
				error('unknown argument type');
		}
		
		public static function tableFromArray($array, $columns = array())
		{
			reset($array);
			if (!$columns)
			{
				$columns = current($array);
				foreach ($columns as $k => $v)
					$columns[$k] = $k;
			}
			$result = '<table>';
			$result .= '<tr>';
			foreach ($columns as $k => $v)
				$result .= '<th>' . $v . '</th>';
			$result .= '</tr>';
			foreach ($array as $rowkey => $row)
			{
				$result .= '<tr>';
				foreach ($columns as $k => $v)
					$result .= '<td>' . $row[$k] . '</td>';
				$result .= '</tr>';
			}
			$result .= '</table>';
			return $result;
		}
	}

	class HTMLElement
	{
		public					$name								= '';
		public					$attributes							= array();
		public					$children							= array();

		public function __construct($name = '')
		{
			$this->name = $name;
		}

		public function attributeGet($key, $value = null)
		{
			return (isset($this->attributes[$key]) ? $this->attributes[$key] : $value);
		}

		public function attributeSet($key, $value)
		{
			if ($value === null)
				unset($this->attributes[$key]);
			else
				$this->attributes[$key] = $value;
		}

		public function attributesToString()
		{
			$result = array();
			foreach ($this->attributes as $k => $v)
				$result[] = $k . '="' . HTML::e($v) . '"';
			return implode(' ', $result);
		}

		public function isPaired()
		{
			return !in_array($this->name, array('img', 'hr'));
		}

		public function add($element)
		{
			$this->children[] = $element;
		}

		public function renderOpen()
		{
			$autoclose = '';
			if (!$this->isPaired())
				$autoclose = ' /';
			return '<'.$this->name.' '.$this->attributesToString().$autoclose.'>';
		}

		public function renderClose()
		{
			if (!$this->isPaired())
				return '';
			return '</'.$this->name.'>';
		}

		public function render()
		{
			$result = '';
			$result .= $this->renderOpen();
			foreach ($this->children as $child)
				$result .= $child->render();
			$result .= $this->renderClose();
			return $result;
		}
	}

	class HTMLText
	{
		public					$text								= '';

		public function __construct($text = '')
		{
			$this->text = $text;
		}

		public function render()
		{
			return $this->text;
		}
	}

	class SimpleHTTP
	{
		public static function GET($url)
		{
			if (!class_exists('HttpRequest'))
				return @file_get_contents($url);
			$request = new HttpRequest($url);
			$request->setOptions(array(
				'timeout' => 10,
				'connecttimeout' => 10,
			));
			$tries = 3;
			$result = false;
			do
			{
				$response = $request->send();
				if ($response)
					$result = $response->getBody();
				$tries--;
			}
			while ($tries && ($result === false));
			return $result;
		}
	}

	class MonthCalendar
	{
		public					$empty_cell							= array('date' => null, 'text' => '', 'style' => ';', 'value' => null);

		public					$month								= null;
		public					$year								= null;
		public					$days								= null;

		public					$week_start							= 1; // monday

		public					$data								= array();
		public					$rows								= null;
		public					$previous							= '';
		public					$next								= '';

		public function __construct($month = null, $year = null, $empty_value = null)
		{
			$date = getdate();
			$this->month = (($month === null) ? $date['mon'] : $month);
			$this->year = (($year === null) ? $date['year'] : $year);

			$this->days = 31;
			while (!checkdate($this->month, $this->days, $this->year))
				$this->days--;
			$empty_value = serialize($empty_value);
			for ($day = 1; $day <= $this->days; $day++)
				$this->data[$day] = unserialize($empty_value);

			$m = $this->year * 12 + ($this->month - 1);
			$m--;
			$this->previous = ((int) ($m / 12)) . '-' . sprintf('%02d', (($m % 12) + 1));
			$m++; $m++;
			$this->next = ((int) ($m / 12)) . '-' . sprintf('%02d', (($m % 12) + 1));
		}

		public function prepare($callback = null)
		{
			$first = getdate(strtotime($this->year . '-' . $this->month . '-01'));
			$last = getdate(strtotime($this->year . '-' . $this->month . '-' . $this->days));

			$before = ($first['wday'] + 7 - $this->week_start) % 7;
			$after = 7 - (($this->days + $before) % 7);

			for ($r = 0; $r < 6; $r++)
			{
				$row = array();
				for ($d = 0; $d < 7; $d++)
				{
					$date = getdate(strtotime(sprintf('%+d days', $r * 7 + $d - $before), $first[0]));
					$cell = $this->empty_cell;
					$cell['date'] = $date;
					$cell['text'] = $date['mday'];
					if ($date['mon'] < $this->month)
						$cell['style'] .= 'before;gray;';
					elseif ($date['mon'] > $this->month)
						$cell['style'] .= 'after;gray;';
					else
					{
						$cell['style'] .= 'normal;';
						$cell['value'] = $this->data[$date['mday']];
					}
					$cell['style'] .= 'wday_' . $date['wday'] . ';';
					if ($callback)
						$cell = callback($callback, array('cell' => $cell, 'calendar' => $this));
					$row[] = $cell;
				}
				$this->rows[] = $row;
			}
		}

		public function render($callback = null, $type = 'table')
		{
			$this->prepare($callback);
			$weekdays = array();
			for ($i = 0; $i < 7; $i++)
				$weekdays[] = __('wday-short-'.$i, '@core');
			$weekdays = array_merge($weekdays, $weekdays);
			$weekdays = array_slice($weekdays, $this->week_start, 7, true);
			$row = array();
			foreach ($weekdays as $day)
			{
				$cell = $this->empty_cell;
				$cell['style'] .= 'header;wday_'.($i%7).';';
				$cell['text'] = $day;
				$row[] = $cell;
			}
			$rows = array_merge(array($row), $this->rows);

			ob_start();
			if ($type == 'table')
			{
				echo '<table class="calendar">';
				foreach ($rows as $row)
				{
					echo '<tr>';
					foreach ($row as $day)
						echo '<td class="'.strtr($day['style'], ';', ' ').'">' . $day['text'] . '</td>';
					echo '</tr>';
				}
				echo '</table>';
			}
			return ob_get_clean();
		}
	}

	class U
	{
		private	static			$lambda0							= null;

		public static function log($section, $path = null, $message = null)
		{
			Log::l($section, $path, $message);
		}

		public static function cut($text, $length)
		{
			if ($length <= 0)
				return '';
			if (mb_strlen($text) > $length)
				return mb_substr($text, 0, $length) . ' ...';
			else
				return $text;
		}

		public static function dashToUpper($string)
		{
			if (!self::$lambda0)
				self::$lambda0 = create_function('$matches', 'return strtoupper($matches[1]);');
			return preg_replace_callback('/-(\w)/', self::$lambda0, $string);
		}

		public static function indent($text, $indent = "\t")
		{
			if (strlen($indent) == 0)
				return $text;
			else
			{
				$result = $indent . str_replace("\n", "\n" . $indent, $text);
				if (substr($result, strlen($result) - strlen("\n" . $indent)) == ("\n" . $indent))
					$result = substr($result, 0, strlen($result) - strlen("\n" . $indent));
				return $result;
			}
		}

		public static function menuSelectedClass($location, $type = 'location', $class = 'selected')
		{
			$result = false;
			if ($type == 'location')
				$result = ($GLOBALS['page']['location'] == $location);
			if ($type == 'directory')
				$result = ($GLOBALS['page']['directory'] == $location);
			if ($type == 'pages_simple-id')
				$result = (@$GLOBALS['page']['pages_simple-id'] == $location);
			return ($result ? $class : '');
		}

		public static function compareObjectPropertiesInteger($a, $b, $property, $reverse = false)
		{
			if ($a->$property == $b->$property)
				$result = 0;
			else if ($a->$property > $b->$property)
				$result = 1;
			else
				$result = -1;
			return ($reverse) ? (- $result) : $result;
		}

		public static function compareObjectPropertiesString($a, $b, $property, $reverse = false)
		{
			$result = strcmp($a->$property, $b->$property);
			return ($reverse) ? (- $result) : $result;
		}

		public static function findFiles($file_name, $simple = true, $single = false, $base = null)
		{
			if (!$base)
				$base = $GLOBALS['site']['base'];
			if (!is_array($base))
				$base = explode(':', $base);
			$file_name = '/' . ltrim($file_name, '/');
			$file = substr(strrchr($file_name, '/'), 1);
			$directory = substr($file_name, 0, strlen($file_name) - strlen($file) - 1);

			$dirs = array(); $dir = '';
			foreach (explode('/', $directory) as $d)
			{
				$dir .= $d . '/';
				$dirs[] = ltrim($dir, '/');
			}

			$result = array();
			foreach ($dirs as $dir)
			{
				if ($simple)
					$f = $dir . $file;
				else
					$f = $dir . str_replace('/', '.', substr($file_name, 1 + strlen($dir)));
				if (self::firstExistingFile($base, $f))
					$result[] = $f;
			}
			return ($single ? (empty($result) ? false : $result[0]) : $result);
		}

		public static function firstExistingFile($dirs, $file = null)
		{
			if (is_string($dirs))
			{
				$temp = $dirs;
				$dirs = $file;
				$file = $temp;
			}
			if ($dirs === null)
				$dirs = $GLOBALS['site']['base'];
			if (is_string($file) && $file)
				foreach ($dirs as $dir)
					if (file_exists($dir . $file))
						return $dir . $file;
			return false;
		}

		public static function fef($file) // simplified firstExistingFile
		{
			foreach ($GLOBALS['site']['base'] as $dir)
				if (file_exists($dir . $file))
					return $dir . $file;
			return null;
		}

		public static function request($key, $value = null)
		{
			if (isset($_REQUEST[$key]))
				return $_REQUEST[$key];
			elseif (isset($_REQUEST[$key = strtr($key, '.', '_')]))
				return $_REQUEST[$key];
			else
				return $value;
		}

		public static function cookie($key, $value = null, $expiration = null)
		{
			if ($expiration === null)
				return (isset($_COOKIE[$key]) ? $_COOKIE[$key] : $value);
			else
				setcookie($key, $value, time() + $expiration, $GLOBALS['site']['path']);
		}

		public static function patchQuery($query, $patch)
		{
			if (is_string($patch))
				$patch = array($patch);
			if (is_string($query))
				$query = array($query);
			return array_merge($query, $patch);
		}

		public static function removeNonLetters($string)
		{
			$string = self::removeAccents($string);
			$string = preg_replace('~[^-\.a-zA-Z0-9]~u', '-', $string);
			return $string;
		}

		public static function removeAccents($string)
		{
			$from = 'áäąćčďëéěęíłňóöŕřśšťúůüýźżžÁÄĄĆČĎËÉĚĘÍŁŃŇÓÖŔŘŚŠŤÚŮÜÝŹŽŻ';
			$to =   'aaaccdeeeeilnoorrsstuuuyzzzAAACCDEEEEILNNOORRSSTUUUYZZZ';
			for ($i = 0; $i < mb_strlen($from); $i++)
				$string = str_replace(mb_substr($from, $i, 1), $to[$i], $string);
			return $string;
		}

		public static function removeJunk($string, $skip = false)
		{
			$string = self::removeAccents($string);
			$pattern = '~[^a-zA-Z0-9\-\.\+\*/\~!@#\$%\^&\(\)\<\>\[\]\{\}\\\|:;\?_\']~u';
			if ($skip)
				$pattern = str_replace($skip, '', $pattern);
			$string = preg_replace($pattern, '-', $string);
			return $string;
		}

		public static function redirect($url, $options = array())
		{
			if ($url instanceof Template)
				$url = $url->process($options);
			if (!$GLOBALS['page']['administration'])
				header("Location: $url");
			$GLOBALS['page']['redirect'] = $url;
		}

		public static function urlize($string)
		{
			$string = trim(strtr(self::removeNonLetters($string), '.', '-'), '-');
			$string = strtolower($string);
			$string = preg_replace('~-+~', '-', $string);
			$string = str_replace('.', '', $string);
			return $string;
		}

		public static function select($value = null, $values = array())
		{
			if ($value === null)
				return $values;
			else if (isset($values[$value]))
				return $values[$value];
			else
				return false;
		}

		public static function applyNumericAdjustment($number, $adjustment)
		{
			$result = $number;
			if (substr($adjustment, 0, 1) == '=')
			{
				$adjustment = substr($adjustment, 1);
				if (strpos($adjustment, '%'))
					$result *= (0 + str_replace('%', '', $adjustment)) / 100;
				else
					$result = (0 + $adjustment);
			}
			else
			{
				if (strpos($adjustment, '%'))
					$result *= (100 + str_replace('%', '', $adjustment)) / 100;
				else
					$result += (0 + $adjustment);
			}
			return $result;
		}

		public static function arrayToUrlParameters($array)
		{
			$temp = array();
			foreach ($array as $x => $y)
				$temp[] = $x . '=' . $y;
			return implode('&', $temp);
		}

		public static function urlParametersToArray($string)
		{
			$array = array();
			foreach (explode('&', $string) as $v)
			{
				$temp = explode('=', $v);
				$array[$temp[0]] = $temp[1];
			}
			return $array;
		}

		public static function replaceParameter($var, $name, $value)
		{
			if (is_string($var))
			{
				return self::arrayToUrlParameters(self::replaceParameter(self::urlParametersToArray($var), $name, $value));
			}
			else
			{
				$var[$name] = $value;
				return $var;
			}
		}

		public static function gzip($data = '', $level = 6, $filename = '', $comments = '')
		{
			$flags = (empty($comment)? 0 : 16) + (empty($filename)? 0 : 8);
			$mtime = time();

			return (pack('C1C1C1C1VC1C1', 0x1f, 0x8b, 8, $flags, $mtime, 2, 0xFF) .
						(empty($filename) ? '' : $filename . "\0") .
						(empty($comment) ? '' : $comment . "\0") .
						gzdeflate($data, $level) .
						pack('VV', crc32($data), strlen($data)));
		}

		public static function acl($action = null, $object = null, $debug = false)
		{
			if ($action instanceof Invocation)
			{
				$invocation = $action;
				$action = $invocation->action();
			}
			else
				$invocation = null;
			if (!m('@users'))
				return false;
			if (!USER)
				return false;
			if (($action === null) && ($object === null))
				return true;

			$rights = array(
				'u:' . USER,
				'r:everyone'
			);
			foreach (user()->roles_array as $group)
				$rights[] = 'r:' . $group;
			if ((USER == 'administrator') || (in_array('r:administrator', $rights)))
				return true;
			if ($object === null)
				return false;
			$temp = $object->acl($invocation, $action);
			if ($temp !== null)
				return $temp;
			if ($object->ouid == USER)
				return true;
			if (in_array('r:' . $object->orid, $rights))
				return true;
			if (!$object->oacl || !is_array($object->oacl))
				return false;
			$ok = false;
			foreach ($object->oacl as $k => $v)
				if (in_array($v->subject, $rights))
				{
					$change = null;
					$type = ($v->type & acl::TYPE_FINAL) ? ($v->type - acl::TYPE_FINAL) : $v->type;
					if ($type == acl::TYPE_SCRIPT)
						if (($temp = eval($v->value)) !== null)
							$change = $temp;
					if ($type == acl::TYPE_ALLOW_ALL)
						$change = true;
					if ($type == acl::TYPE_DENY_ALL)
						$change = false;
					else if ($action)
					{
						$a = preg_split('/[;,\s]/', $v->value);
						if (($type == acl::TYPE_ALLOW) && in_array($action->id, $a))
							$change = true;
						if (($type == acl::TYPE_DENY) && in_array($action->id, $a))
							$change = false;
					}
					if ($change !== null)
						$ok = $change;
					if (($change !== null) && $v->type & acl::TYPE_FINAL)
						break;
				}
			return $ok;
		}

		public static function priceFormat($number, $currency = null)
		{
			$result = number_format($number, 2, ',', ' ');
			if (substr($result, -3) == ',00')
				$result = str_replace(',00', ',- ', $result);
			if ($currency !== false)
			{
				if (($currency === null) && m('shop'))
					$currency = m('shop')->getS('currency');
				if ($temp = __('currency-' . $currency, 'shop', null))
					$currency = $temp;
				if (in_array($GLOBALS['page']['locale'], array('en')))
					$result = $currency . ' ' . $result;
				else
					$result .= ' ' . $currency;
			}
			return $result;
		}

		public static function mail($to, $bcc, $subject, $body, $from = null, $headers = array(), $attachments = array())
		{
			require_once('third-party/phpmailer/class.phpmailer.php');
			if ($from === null)
				$from = $GLOBALS['site']['email'];
			$from = explode('|', $from);
			if (!is_array($to))
				$to = array($to);
			if (!is_array($bcc))
				$bcc = array($bcc);
			if (!is_array($attachments))
				$attachments = array($attachments);
			$text = strip_tags(str_replace(array('<br/>', '</div>', '</p>'), array("\n", "\n</div>", "\n</p>"), $body));

			$mail = new PHPMailer();
			$mail->IsMail();
			$mail->CharSet = 'utf-8';
			$mail->Encoding = 'quoted-printable';
			$mail->From = $from[0];
			$mail->FromName = (isset($from[1]) ? $from[1] : '');
			$mail->Subject = $subject;
			$mail->Body = $body;
			foreach ($headers as $k => $v)
				$mail->AddCustomHeader($k.':'.$v);
			if ($text != $body)
				$mail->IsHTML(true);
				//$mail->AltBody = trim($text);

			foreach ($attachments as $attachment)
			{
				if (isset($attachment['content']))
					$mail->AddStringAttachment($attachment['content'], $attachment['name'], 'base64', $attachment['type']);
				else
					$mail->AddStringAttachment(file_get_contents($attachment['file']), $attachment['name'], 'base64', $attachment['type']);
			}
			foreach ($to as $email)
			{
				$email = explode('|', $email);
				if (isset($email[1]))
					$mail->AddAddress($email[0], $email[1]);
				else
					$mail->AddAddress($email[0]);
			}
			foreach ($bcc as $email)
			{
				$email = explode('|', $email);
				if (isset($email[1]))
					$mail->AddBCC($email[0], $email[1]);
				else
					$mail->AddBCC($email[0]);
			}
			return $mail->Send();
		}

		public static function mailPseudoSmarty($template, $args = array())
		{
			return self::pseudoSmarty($template, array_merge(array('site' => $GLOBALS['site']), $args));
		}

		public static function pseudoSmarty($template, $args = array())
		{
			foreach ($args as $k => $v)
				if (is_scalar($v))
					$template = str_replace('{$'.$k.'}', $v, $template);
				else if (is_array($v))
					foreach ($v as $kk => $vv)
						if (is_scalar($vv))
							$template = str_replace('{$'.$k.'.'.$kk.'}', $vv, $template);
				else if (is_object($v))
					foreach ($v as $kk => $vv)
						if (is_scalar($vv))
							$template = str_replace('{$'.$k.'->'.$kk.'}', $vv, $template);
			return $template;
		}

		public static function randomString($length, $chars = 'abcdefghijklmnopqrstuvwxyz0123456789')
		{
			$result = '';
			$max = strlen($chars) - 1;
			for ($i = 0; $i < $length; $i++)
				$result .= substr($chars, mt_rand(0, $max), 1);
			return $result;
		}

		public static function string($string, $args = array())
		{
			if (is_string($string) && !$args)
				return $string;
			if (is_array($string) && ($string[0] == '__'))
				return self::string(__($string[1], @$string[2]), $args);
			if ($string instanceof E)
				return $string->evaluate($args);
			if (is_string($string) && $args)
				return self::pseudoSmarty($string, $args);
		}

		public static function patternMatch($patterns, $string, $process_all = true)
		{
			if (!is_array($patterns))
				$patterns = array($patterns);
			$result = false;
			foreach ($patterns as $patern)
				if ($pattern)
				{
					if ($negative = (substr($pattern, 0, 1) == '!'))
						$pattern = substr($pattern, 1);
					if (substr($pattern, 0, 1) == '~')
						$ok = preg_match($pattern, $string);
					else
						$ok = ($pattern == $string);
					if ($ok && !$negative)
						$result = true;
					if ($ok && $negative)
						$result = false;
					if ($ok && !$process_all)
						return $result;
				}
			return $result;
		}

		public static function piece($piece, $skip, $value)
		{
			$args = func_get_args();
			array_shift($args); array_shift($args);
			foreach ($args as $value)
				if ((($value === true) || ($piece == $value)) && (strpos($skip, ';'.$value.';') === false))
					return true;
		    return false;
		}

		public static function compare($value1, $value2, $operator = '==')
		{
			if (!in_array($operator, array('==', '!=', '===', '!==', '>', '<', '>=', '<=')))
				$operator = '==';
			if ($operator == '==')
				return ($value1 == $value2);
			else if ($operator == '==')
				return ($value1 == $value2);
			else if ($operator == '!=')
				return ($value1 != $value2);
			else if ($operator == '===')
				return ($value1 === $value2);
			else if ($operator == '!==')
				return ($value1 !== $value2);
			else if ($operator == '>')
				return ($value1 > $value2);
			else if ($operator == '>=')
				return ($value1 >= $value2);
			else if ($operator == '<')
				return ($value1 < $value2);
			else if ($operator == '<=')
				return ($value1 <= $value2);
		}
		
		public static function metaArrayToString($input, $keys = array(), $onlyKeys = false)
		{
			if (!$onlyKeys)
				$keys = array_unique(array_merge($keys, array_keys($input)));
			$output = '';
			foreach ($keys as $k)
				$output .= $k . ':' . (isset($input[$k]) ? $input[$k] : '') . "\n";
			return $output;
		}
		
		public static function metaStringToArray($input, $keys = array(), $onlyKeys = false)
		{
			$output = array();
			foreach ($keys as $k)
				$output[$k] = null;
			foreach (explode("\n", $input) as $line)
			{
				$line = trim($line);
				if ($temp = strpos($line, ':'))
				{
					$k = substr($line, 0, $temp);
					if (!$onlyKeys || isset($output[$k]))
						$output[$k] = substr($line, $temp + 1);
				}
			}
			
			return $output;
		}
	}

	class Mail
	{
		public					$to									= array();
		public					$bcc								= array();
		public					$from								= '';
		public					$subject							= '';
		public					$body								= '';
		public					$headers							= array();
		public					$attachments						= array();

		private					$_subject							= '';
		private					$_body								= '';

		public function prepare()
		{
			if ($this->subject instanceof Template)
				$this->_subject = $this->subject->process(array('mail' => $this));
			else
				$this->_subject = $this->subject;

			if ($this->body instanceof Template)
				$this->_body = $this->body->process(array('mail' => $this));
			else
				$this->_body = $this->body;
		}

		public function send()
		{
			return U::mail($this->to, $this->bcc, $this->_subject, $this->_body, $this->from, $this->headers, $this->attachments);
		}

		public function prepareAndSend()
		{
			$this->prepare();
			return $this->send();
		}
	}

	class ARES
	{
		public static function XMLReaderToAssoc($xml)
		{
			$result = null;
			while($xml->read())
				if ($xml->nodeType == XMLReader::END_ELEMENT)
					return $result;
				else if ($xml->nodeType == XMLReader::ELEMENT)
				{
					$result[$xml->name][] = array('value' => $xml->isEmptyElement ? '' : self::XMLReaderToAssoc($xml));
					if($xml->hasAttributes)
					{
						$el =& $result[$xml->name][count($result[$xml->name]) - 1];
						while($xml->moveToNextAttribute()) $el['attributes'][$xml->name] = $xml->value;
					}
				}
				else if (($xml->nodeType == XMLReader::TEXT) || ($xml->nodeType == XMLReader::CDATA))
					$result .= $xml->value;
			return $result;
		}

		public static function getStandardICO($ico)
		{
			$url = 'http://wwwinfo.mfcr.cz/cgi-bin/ares/darv_std.cgi?ico=' . $ico;
			$xmlstring = @file_get_contents($url);
			if (!$xmlstring)
				return false;
			//$xml = @simplexml_load_string($xmlstring);
			if (!class_exists('XMLReader'))
				return false;
			$xml = new XMLReader();
			$xml->xml($xmlstring);
			$array = self::XMLReaderToAssoc($xml);
			$pocet = (int) @$array['are:Ares_odpovedi'][0]['value']['are:Odpoved'][0]['value']['are:Pocet_zaznamu'][0]['value'];
			if (!$pocet)
				return false;
			$result = array();
			$zaznam = @$array['are:Ares_odpovedi'][0]['value']['are:Odpoved'][0]['value']['are:Zaznam'][0]['value'];
			$result['firma'] = @$zaznam['are:Obchodni_firma'][0]['value'];
			$adresa = @$zaznam['are:Identifikace'][0]['value']['are:Adresa_ARES'][0]['value'];
			$map = array(
				'okres' => 'dtt:Nazev_okresu',
				'obec' => 'dtt:Nazev_obce',
				'obecni_cast' => 'dtt:Nazev_casti_obce',
				'mestska_cast' => 'dtt:Nazev_mestske_casti',
				'ulice' => 'dtt:Nazev_ulice',
				'cislo_domovni' => 'dtt:Cislo_domovni',
				'cislo_orientacni' => 'dtt:Cislo_orientacni',
				'psc' => 'dtt:PSC',
			);
			foreach ($map as $k => $v)
				$result[$k] = @$adresa[$v][0]['value'];
			return $result;
		}
	}

	class History
	{
		public static			$active								= true;
		public static			$save_current						= true;
		public static			$return_point						= true;
		const					KEY									= 'history';

		public static function initialize()
		{
			if (!self::$active)
				return;

			if (!isset($_SESSION[self::KEY]) || !is_array($_SESSION[self::KEY]))
				$_SESSION[self::KEY] = array();
			if (!isset($_SESSION[self::KEY.'-ppanels']) || !is_array($_SESSION[self::KEY.'-ppanels']))
				$_SESSION[self::KEY.'-ppanels'] = array();
		}

		public static function save()
		{
			if (!self::$active || !self::$save_current)
				return;
			if ($GLOBALS['page']['location'] == '404')
				return;

			$info = array(
				'timestamp' => time(),
				'request_uri' => substr($GLOBALS['site']['url'], 0, -strlen($GLOBALS['site']['path'])) . $_SERVER['REQUEST_URI'],
				'page' => $GLOBALS['page'],
				'return_point' => self::$return_point,
			);
			array_unshift($_SESSION[self::KEY], $info);
			$_SESSION[self::KEY] = array_slice($_SESSION[self::KEY], 0, 50);
			if ($GLOBALS['page']['panel'])
				$_SESSION[self::KEY.'-ppanels'][$GLOBALS['page']['panel']] = $GLOBALS['page']['ppanel'];
		}

		public static function last($pattern = null, $field = 'page.location')
		{
			$result = null;
			if ($pattern === null)
				list($result) = array_split($_SESSION[self::KEY], 0, 1);
			else
			{
				if ($negative = (substr($pattern, 0, 1) == '!'))
					$pattern = substr($pattern, 1);
				foreach ($_SESSION[self::KEY] as $info)
				{
					if (substr($field, 0, 4) == 'page')
						$value = $info['page'][substr($field, 5)];
					else
						$value = $info[$field];
					$matches = preg_match($pattern, $value);
					if ((!$negative && $matches) || ($negative && !$matches))
						return $info;
				}
			}
			return $result;
		}

		public static function ppanel($panel = true)
		{
			if ($panel === true)
				$panel = $GLOBALS['page']['panel'];
			return (isset($_SESSION[self::KEY.'-ppanels'][$panel]) ? $_SESSION[self::KEY.'-ppanels'][$panel] : null);
		}
	}

	$GLOBALS['qp_tables'] = array();
	$GLOBALS['q_connection'] = null;

	function qconnect($dsn = null)
	{
		if (!$dsn)
			$dsn = $GLOBALS['site']['dsn'];
		if (preg_match('~^(.+?)://(.+?):(.+?)@(.+?)/(.+?)$~', $dsn, $matches))
		{
			if ($matches[1] == 'mysql')
			{
				$result = mysql_connect($matches[4], $matches[2], $matches[3]);
				if (!$result)
					die('database failure');
				mysql_select_db($matches[5], $result);
				mysql_query("SET NAMES 'utf8'", $result);
				if (!$GLOBALS['q_connection'])
					$GLOBALS['q_connection'] = $result;
				if ($GLOBALS['site']['dsn'] == $dsn)
					$GLOBALS['db'] = $result;
				return ($result ? true : false);
			}
		}
		else
			return false;
	}

	function qe($value)
	{
		if ($value === null)
			return 'NULL';
		return  mysql_real_escape_string($value, $GLOBALS['q_connection']);
	}

	function qq($value)
	{
		return "'".qe($value)."'";
	}

	function qi($identifier)
	{
		return '`' . $identifier . '`';
	}

	function qp($query, $args = array())
	{
		if ($GLOBALS['page']['log'] & LOG_STORAGE)
		{
			U::log('storage', 'qp()', $query);
			if ($GLOBALS['site']['development'])
				Log::ll($GLOBALS['site']['data'] . 'logs/qp-' . date('Y-m-d') . '.log', strtr($query, "\n", ' '));
		}
		foreach ($GLOBALS['qp_tables'] as $k => $v)
			$query = str_replace('##'.$k, $v, $query);
		$query = str_replace('##', $GLOBALS['site']['prefix'], $query);
		$query = str_replace('#L#', $GLOBALS['page']['locale'], $query);
		$query = str_replace('#LL#', '_' . $GLOBALS['page']['locale'], $query);
		$query = preg_replace('~(:[\w\d-_\.]+)~', '\1:', $query);
		foreach ($args as $k => $v)
			$query = str_replace('::' . $k . ':', qe($v), $query);
		foreach ($args as $k => $v)
			$query = str_replace(':' . $k . ':', qq($v), $query);
		return $query;
	}

	function q($query, $args = array())
	{
		$query = qp($query, $args);
		if ($GLOBALS['page']['log'] & LOG_STORAGE)
			U::log('storage', 'q()', $query);
		/*if ($GLOBALS['site']['development'])
			fb('q(): ' . $query);*/
		$r = mysql_query($query, $GLOBALS['q_connection']);
		if ($r === true)
			return true;
		if ($r === false)
			return error(mysql_error($GLOBALS['q_connection']));
		return $r;
	}

	function qa($query, $args = array(), $index = 'oid')
	{
		$r = q($query, $args);
		if (is_resource($r))
		{
			$result = array();
			while ($row = mysql_fetch_assoc($r))
				if (isset($row[$index]))
					$result[$row[$index]] = $row;
				else
					$result[] = $row;
			mysql_free_result($r);
			return $result;
		}
		else
			return $r;
	}

	function qr($query, $args = array())
	{
		$r = q($query, $args);
		if (is_resource($r))
		{
			$result = mysql_fetch_assoc($r);
			mysql_free_result($r);
			return $result;
		}
		else
			return $r;
	}

	function qc($query, $args = array(), $column = null, $index = 'oid')
	{
		$r = q($query, $args);
		if (is_resource($r))
		{
			$result = array();
			if ($column === null)
			{
				while ($row = mysql_fetch_assoc($r))
					if (isset($row[$index]))
						$result[$row[$index]] = current($row);
					else
						$result[] = current($row);
			}
			else
			{
				while ($row = mysql_fetch_assoc($r))
					if (isset($row[$index]))
						$result[$row[$index]] = $row[$column];
					else
						$result[] = $row[$column];
			}
			mysql_free_result($r);
			return $result;
		}
		else
			return $r;
	}

	function qo($query, $args = array())
	{
		$r = qr($query, $args);
		if (is_array($r))
			return array_shift($r);
		else
			return $r;
	}

	function qupdate()
	{
		$args = func_get_args();
		$table = array_shift($args);
		$result = '';
		foreach ($args as $item)
			$result .= qi($item).' = :'.$item.',';
		return 'UPDATE `##'.$table.'` SET ' . rtrim($result, ',') . ' ';
	}

	function qupdatedirect($table, $fields, $key = 'oid', $table_prefix = '##')
	{
		$query = '';
		foreach ($fields as $k => $v)
			if ($k != $key)
				$query .= qi($k).' = :'.$k.',';
		$query = 'UPDATE '.qi($table_prefix.$table).' SET ' . rtrim($query, ',') . ' WHERE `'.$key.'` = :'.$key;
		return q($query, $fields);
	}

	function qinsert()
	{
		$one = '';
		$two = '';
		$args = func_get_args();
		$table = array_shift($args);
		foreach ($args as $item)
		{
			$one .= qi($item).',';
			$two .= ':'.$item.',';
		}
		return 'INSERT INTO '.qi('##'.$table).'('.rtrim($one, ',').') VALUES ('.rtrim($two, ',').')';
	}

	function qinserta($table, $args, $table_prefix = '##')
	{
		$one = '';
		$two = '';
		foreach ($args as $item)
		{
			$one .= qi($item).',';
			$two .= ':'.$item.',';
		}
		return 'INSERT INTO '.qi($table_prefix.$table).'('.rtrim($one, ',').') VALUES ('.rtrim($two, ',').')';
	}

	function qinsertdirect($table, $fields, $table_prefix = '##')
	{
		$query = qinserta($table, array_keys($fields), $table_prefix);
		return q($query, $fields);
	}

	function ar()
	{
		$result = array();
		$a = func_get_args();
		foreach ($a as $aa)
			if (!isset($key))
				$key = $aa;
			else
			{
				$result[$key] = $aa;
				unset($key);
			}
		return $result;
	}

	function array_insert($arr1, $key, $arr2, $before = false)
	{ // from http://drupal.org/node/66183
		$done = false;
		foreach ($arr1 as $arr1_key => $arr1_val)
		{
			if (!$before)
				$new_array[$arr1_key] = $arr1_val;
			if ($arr1_key == $key && !$done)
			{
				foreach($arr2 as $arr2_key => $arr2_val)
					$new_array[$arr2_key] = $arr2_val;
				$done = true;
			}
			if ($before)
				$new_array[$arr1_key] = $arr1_val;
		}
		if (!$done)
			$new_array = array_merge($arr1, $arr2);
		return $new_array;
	}

	function callback($_x_callback, $_x_args = array())
	{
		if ($_x_callback === null)
			return null;
		else if ($_x_callback instanceof E)
			return callback($_x_callback->expression, $_x_args);
		else if (is_string($_x_callback) && !function_exists($_x_callback))
		{
			if (strpos($_x_callback, '$this') !== false)
				error('callback uses $this: ' . $_x_callback);
			extract($_x_args, EXTR_SKIP);
			return eval($_x_callback);
		}
		else
			return call_user_func_array($_x_callback, $_x_args);
	}

	function imagecreatefromfile($file)
	{
		$i = @imagecreatefromjpeg($file); if ($i) return $i;
		$i = @imagecreatefrompng($file); if ($i) return $i;
		$i = @imagecreatefromgif($file); if ($i) return $i;
		$i = @imagecreatefromwbmp($file); if ($i) return $i;

		return false;
	}

	function imagescale($i, $max_width, $max_height)
	{
		if ((imagesx($i) < $max_width) && (imagesy($i) < $max_height))
		{
			$j = @imagecreatetruecolor(imagesx($i), imagesy($i));
			imagecopy($j, $i, 0, 0, 0, 0, imagesx($i), imagesy($i));
			return $j;
		}
		if (imagesx($i) * $max_height < imagesy($i) * $max_width)
		{
			$width = round(imagesx($i) * $max_height / imagesy($i));
			$j = @imagecreatetruecolor($width, $max_height);
		}
		else
		{
			$height = round(imagesy($i) * $max_width / imagesx($i));
			$j = @imagecreatetruecolor($max_width, $height);
		}
		imagecopyresampled($j, $i, 0, 0, 0, 0, imagesx($j), imagesy($j), imagesx($i),  imagesy($i));
		return $j;
	}

	function imagescaleexpand($i, $max_width, $max_height, $color = 'ffffff', $transparent = false)
	{
		$temp = imagescale($i, $max_width, $max_height);
		$j = @imagecreatetruecolor($max_width, $max_height);

		$r = hexdec(substr($color, 0, 2));
		$g = hexdec(substr($color, 2, 2));
		$b = hexdec(substr($color, 4, 2));
		if ($transparent)
		{
			$color = imagecolorallocatealpha($j, $r, $g, $b, 127);
			imagecolortransparent($j, $color);
		}
		else
			$color = imagecolorallocate($j, $r, $g, $b);
		imagefilledrectangle($j, 0, 0, imagesx($j), imagesy($j), $color);

		imagecopy($j, $temp, (imagesx($j) - imagesx($temp)) / 2, (imagesy($j) - imagesy($temp)) / 2, 0, 0, imagesx($temp), imagesy($temp));
		imagedestroy($temp);
		return $j;
	}

	function imagescalecrop($i, $max_width, $max_height)
	{
		$j = @imagecreatetruecolor($max_width, $max_height);
		if (!$j)
			return false;
		if ((imagesx($i) < $max_width) && (imagesy($i) < $max_height))
		{
			imagecopy($j, $i, round(($max_width - imagesx($i)) / 2), round(($max_height - imagesy($i)) / 2), 0, 0, imagesx($i), imagesy($i));
			return $j;
		}
		if (imagesx($i) * $max_height < imagesy($i) * $max_width)
		{
			$height = round($max_height * imagesx($i) / $max_width);
			imagecopyresampled($j, $i, 0, 0, 0, round((imagesy($i) - $height) / 2), imagesx($j), imagesy($j), imagesx($i), $height);
		}
		else
		{
			$width = round($max_width * imagesy($i) / $max_height);
			imagecopyresampled($j, $i, 0, 0, round((imagesx($i) - $width) / 2), 0, imagesx($j), imagesy($j), $width, imagesy($i));
		}
		return $j;
	}

	function imagethumb($blob, $function, $width, $height, $arg0 = null, $arg1 = null, $arg2 = null)
	{
		$file_blob = $GLOBALS['site']['data'] . 'blob/' . $blob;
		$thumb = $blob . '.thumb.' . $function . '-' . $width . 'x' . $height . '.jpg';
		$url_thumb = $GLOBALS['site']['url'] . 'data/blob/' . $thumb;
		$file_thumb = $GLOBALS['site']['data'] . 'blob/' . $thumb;

		if (!is_readable($file_thumb) || (filemtime($file_blob) > filemtime($file_thumb)))
		{
			$function = 'image' . $function;
			if (!function_exists($function))
				return error('unknown method: ' . $function);
			$i = imagecreatefromfile($i);
			if (!$i)
				return null;
			$j = $function($i, $width, $height, $arg0, $arg1, $arg2);
			imagejpeg($j, $file_thumb);
			imagedestroy($i);
			imagedestroy($j);
		}
		return $url_thumb;
	}

	function imagewatermarktransparent($image, $watermark, $alpha = 30, $position = 'center')
	{
		if (!is_resource($watermark))
		{
			if (is_string($watermark))
				$watermark_file = $watermark;
			else
				$watermark_file = $GLOBALS['site']['base'][0] . 'web/_images/watermark.gif';
			$watermark = imagecreatefromgif($watermark_file);
		}

		imagealphablending($watermark, true);
	    imagealphablending($image, true);
	    imagesavealpha($watermark, false);
	    imagesavealpha($image, false);
		if ($position == 'top-left')
	    {
			$x = 0;
			$y = 0;
	    }
		else if ($position == 'top-right')
	    {
			$x = (int) (imagesx($image) - imagesx($watermark));
			$y = 0;
	    }
		else if ($position == 'bottom-left')
	    {
			$x = 0;
			$y = (int) (imagesy($image) - imagesy($watermark));
	    }
		else if ($position == 'bottom-right')
	    {
			$x = (int) (imagesx($image) - imagesx($watermark));
			$y = (int) (imagesy($image) - imagesy($watermark));
	    }
	    else /*($position == 'center')*/
	    {
			$x = (int) ((imagesx($image) - imagesx($watermark)) / 2);
			$y = (int) ((imagesy($image) - imagesy($watermark)) / 2);
	    }
		//imagecopy($image, $watermark, $x, $y, 0, 0, imagesx($watermark), imagesy($watermark));
		imagecopymerge($image, $watermark, $x, $y, 0, 0, imagesx($watermark), imagesy($watermark), $alpha);

		if (isset($watermark_file))
			imagedestroy($watermark);
	}

	function a($action, $module = null) { return Action::find($action, $module); }
	function m($id) { return @$GLOBALS['modules'][$id]; }
	function o($id) { return @$GLOBALS['objects'][$id]; }
	function dgp() { dump($GLOBALS['page']); }
	function dgs() { dump($GLOBALS['site']); }

	function isUser($user) { return (USER == $user); }
	function isRole($role) { return (strpos(ROLES, ';'.$role.';') !== false); }
	function isAdministrator() { return (isUser('administrator') || isRole('administrator')); }
	function isRoleOrAdministrator($role) { return (isAdministrator() || isRole($role)); }
	function user($id = null) { if ($id) return $GLOBALS['modules']['@users']->user($id); else return $GLOBALS['modules']['@users']->user;}
?>