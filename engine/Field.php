<?php
	// Author: Jakub Macek, CZ; Copyright: Poski.com s.r.o.; Code is 100% my work. Do not copy.

	Field::$types = array(
		Field::TYPE_NULL			=> 'NULL',
		Field::TYPE_BOOLEAN			=> 'BOOLEAN',
		Field::TYPE_INTEGER			=> 'INTEGER',
		Field::TYPE_FLOAT			=> 'FLOAT',
		Field::TYPE_STRING			=> 'STRING',
		Field::TYPE_DATETIME		=> 'DATETIME',
		Field::TYPE_DATE			=> 'DATE',
		Field::TYPE_TIME			=> 'TIME',
		Field::TYPE_BLOB			=> 'BLOB',
		Field::TYPE_TEXT			=> 'TEXT',
		Field::TYPE_FILE			=> 'FILE',
		Field::TYPE_OBJECT			=> 'OBJECT',
	);
	array_flip(Field::$types);

	class Fields implements Iterator
	{
		public					$_									= array();

		public function object()
		{
			foreach ($GLOBALS['objects'] as $object)
				if ($object->fields() == $this)
					return $object;
		}

		public function __get($id)
		{
			if (isset($this->_[$id]))
				return $this->_[$id];
			else
				return error("unknown field {$id} in {$this->object()->i}");
		}

		public function __set($id, $field)
		{
			$this->_[$id] = $field;
			if (!$field->id)
				$field->id = $id;
		}

		public function __isset($id)
		{
			return isset($this->_[$id]);
		}

		public function __unset($id)
		{
			unset($this->_[$id]);
		}

		public function __clone()
		{
			foreach ($this->_ as $k => $v)
				$this->_[$k] = clone($this->_[$k]);
		}

		public function add($field)
		{
			$this->{$field->id} = $field;
		}

		public function size()						{ return count($this->_); }
		public function count()						{ return count($this->_); }
		public function rewind()					{ reset($this->_); }
		public function current()					{ return current($this->_); }
		public function key()						{ return key($this->_); }
		public function next()						{ return next($this->_); }
		public function valid()						{ return ($this->current() !== false); }
		public function keys()						{ return array_keys($this->_); }

		public function iterator()
		{
			return new ContainerIterator($this);
		}

		public function select($selection = true)
		{
			$result = array();
			if (!is_array($selection))
				foreach ($this->_ as $k => $v)
					$result[$k] = $v;
			else
				foreach ($selection as $k)
					if (!isset($result[$k]) && isset($this->_[$k]))
						$result[$k] = $this->_[$k];
			return $result;
		}

		public static function attributes($attributes = null, $list = null)
		{
			if ($attributes === null)
			{
				if ($result = Cache::get(__FUNCTION__, $list))
				{
					if ($GLOBALS['page']['log'] & LOG_FIELD)
						U::log('fields', 'attributes', 'cached: ' . $list);
					return clone($result);
				}
			}
			$result = new Fields();

			$all = array();
			foreach (explode(';', $list) as $attr)
				if ($attr)
				{
					$group = o('@attribute_types')->load(Fand(Feq('group', true), Feq('key', $attr)), true);
					if (!$group)
						continue;
					$items = o('@attribute_types')->load(Fand(Feq('group', false), Feq('pid', $group->id)));
					if (!$items)
						continue;
					foreach ($items as $item)
					{
						if ($group->title)
							$item->title = $group->title . ' - ' . $item->title;
						$all[$item->key] = $item;
					}
				}

			if ($attributes === null)
				$attributes = array_keys($all);
			foreach ($attributes as $v)
				if (is_object($v))
					$result->define($v);
				else if (isset($all[$v]))
				{
					$attribute = $all[$v];
					$size = false;
					$default = null;
					$value = null;
					$options = array();
					switch ($attribute->type)
					{
						case Field::TYPE_BOOLEAN:
							$size = null;
							$default = false;
							$value = 'value_integer';
							break;
						case Field::TYPE_INTEGER:
							$size = null;
							$default = 0;
							$value = 'value_integer';
							break;
						case Field::TYPE_FLOAT:
							$size = null;
							$default = 0.0;
							$value = 'value_float';
							break;
						case Field::TYPE_STRING:
						case Field::TYPE_TEXT:
						case Field::TYPE_TIME:
							$size = 255;
							$default = '';
							$value = 'value_string';
							break;
						case Field::TYPE_DATE:
						case Field::TYPE_DATETIME:
							$size = null;
							$default = time();
							$value = 'value_datetime';
							if ($attribute->data)
								$options['format'] = trim($attribute->data);
							break;
						case Field::TYPE_FILE:
							$size = null;
							$default = '';
							$value = 'value_string';
							break;
					}

					$postfixes = array();
					if ($attribute->variant == 'locale')
					{
						foreach ($GLOBALS['site']['locales'] as $locale)
							$postfixes['_' . $locale] = __('locale-' . $locale, '@core');
					}
					else
						$postfixes[''] = '';

					if ($attribute->variant)
					{
						$field = $result->define('attribute_' . $attribute->key, $attribute->type, $size, $default, false, false, $options);
						$field->set('label', $attribute->title);
						$field->set('attribute', $attribute);
						$field->set('forms', array());
						$field->get = 'return $GLOBALS["modules"]["@attributes"]->getAttribute($field, $object, ' . $attribute->id . ', $field->get(\'attribute\')->currentVariant());';
						$field->set = '$GLOBALS["modules"]["@attributes"]->setAttribute($field, $object, ' . $attribute->id . ', $field->get(\'attribute\')->currentVariant(), $value);';
					}

					foreach ($postfixes as $postfix => $label)
					{
						$variant = substr($postfix, 1);
						$field = $result->define('attribute_' . $attribute->key . $postfix, $attribute->type, $size, $default, false, false, $options);
						$field->set('label', $attribute->title . ($label ? (' ('.$label.')') : ''));
						$field->set('attribute', $attribute);
						$field->set('variant', $variant);
						$field->get = 'return $GLOBALS["modules"]["@attributes"]->getAttribute($field, $object, ' . $attribute->id . ', \'' . $variant . '\');';
						$field->set = '$GLOBALS["modules"]["@attributes"]->setAttribute($field, $object, ' . $attribute->id . ', \'' . $variant . '\', $value);';
					}
				}

			if ($attributes === null)
				Cache::set(clone($result), __FUNCTION__, $list);
			if ($GLOBALS['page']['log'] & LOG_FIELD)
				U::log('fields', 'attributes', 'loaded: ' . $list);
			return $result;
		}
	}

	class Field
	{
		public static			$field_view_static					= true;
		public static			$_									= array();
		public static			$types								= array();

		const					TYPE_NULL							= 0;
		const					TYPE_BOOLEAN						= 1;
		const					TYPE_INTEGER						= 2;
		const					TYPE_FLOAT							= 3;
		const					TYPE_STRING							= 4;
		const					TYPE_BLOB							= 5;
		const					TYPE_TEXT							= 6;
		const					TYPE_DATETIME						= 7;
		const					TYPE_DATE							= 8;
		const					TYPE_TIME							= 9;

		const					TYPE_FILE							= 33;
		const					TYPE_OBJECT							= 34;

		const					TYPE_REAL_LIMIT						= 64;

		const					TYPE_CONNECTION						= 127;

		const					TYPE_MASK_INDEX						= 128;
		const					TYPE_PRIMARY_KEY					= 128;
		const					TYPE_KEY							= 129;
		const					TYPE_INDEX							= 130;
		const					TYPE_UNIQUE							= 131;
		const					TYPE_FULLTEXT						= 132;

		const					FILES_AT_ONCE						= 3;

		const					REAL_LOAD							= 1;
		const					REAL_SAVE							= 2;

		public					$id									= '';
		public					$type								= self::TYPE_NULL;
		public					$size								= 0;
		public					$default							= null;
		public					$get								= null;
		public					$set								= null;
		public					$options							= array();
		public					$rules								= array();
		public					$object								= null;

		public function __construct($id, $type = self::TYPE_VOID, $size = null, $default = null, $options = array(), $get = null, $set = null)
		{
			/*if ($type == self::TYPE_INTEGER)
			{
				if ($default === null)
					$default = 0;
			}
			else if ($type == self::TYPE_FLOAT)
			{
				if ($default === null)
					$default = 0.0;
			}
			else if ($type == self::TYPE_STRING)
			{
				if ($default === null)
					$default = '';
				if ($size === null)
					$size = 255;
			}
			else if ($type == self::TYPE_TEXT)
			{
				if ($default === null)
					$default = '';
				if ($size === null)
					$size = 65535;
			}
			else if ($type == self::TYPE_FILE)
			{
				if ($default === null)
					$default = '';
				if ($size === null)
					$size = 255;
			}
			else if ($type == self::TYPE_BOOLEAN)
			{
				if ($default === null)
					$default = false;
			}
			else if ($type == self::TYPE_OBJECT)
			{
				if ($size === null)
					$size = 65535;
			}*/

			$this->id = $id;
			$this->type = $type;
			$this->size = $size;
			$this->default = $default;
			$this->options = $options;
			$this->get = $get;
			$this->set = $set;

			//HACK odstranit po konverzi vsech modulu
			if (($this->type == self::TYPE_DATETIME) && (isset($options['format'])))
				die('field ' . $this->id . ' has old datetime format');
			if (is_array($get))
				die('field ' . $this->id . ' has bad parameters');
			if (($this->type == Field::TYPE_STRING) && ($this->size > 255))
				die('field ' . $this->id . ' is string, should be text');
		}

		public function get($key, $value = null)
		{
			if (is_string($key))
				return (isset($this->options[$key]) ? $this->options[$key] : $value);

			if ($this->type & self::TYPE_MASK_INDEX)
				return null;
			if ($this->get === false)
				return $this->default;
			if ($this->get !== null)
				return callback($this->get, array('object' => $key, 'field' => $this));
			else if ($this->get('localized'))
				$idx = $this->id . '_' . $GLOBALS['page']['locale'];
			else
				$idx = $this->id;

			$object = $key;

			if ($this->type == self::TYPE_CONNECTION)
			{
				if (!isset($object->x['connections'][$this->id]))
				{
					$info = $this->connection($object);
					$object->x['connections'][$this->id] = null;
					if (!$info)
						$object->x['connections'][$this->id] = false;
					$object->x['connections'][$this->id] = $info['object']->load($info['filter'], $info['order']);
				}
				if ($this->get('one'))
					return $object->x['connections'][$this->id]->{null};
				return $object->x['connections'][$this->id];
			}

			return (isset($object->d[$idx]) ? $this->convert($object->d[$idx]) : $this->default);
		}

		public function set($key, $value)
		{
			if (is_string($key))
			{
				if ($value === null)
					unset($this->options[$key]);
				else
					$this->options[$key] = $value;
				return $this;
			}

			if ($this->type & self::TYPE_MASK_INDEX)
				return null;
			if ($this->set === false)
				return;
			$object = $key;
			$original = $this->get($key);
			if ($this->set !== null)
				callback($this->set, array('object' => $key, 'field' => $this, 'value' => $this->convert($value)));
			else if ($this->type == self::TYPE_CONNECTION)
			{
				$info = $this->connection($object);
				foreach ($info['filter']->a as $feq)
				{
					$fid = $feq->a->fid();
					$value->$fid = $feq->b->a;
					$fields = array_diff($fields, array($fid));
				}
				if ($this->get('one'))
				{
					$object->x['connections'][$this->id] = new Objects();
					$object->x['connections'][$this->id]->{null} = $value;
				}
				else
					$object->x['connections'][$this->id] = $value;
			}
			else if ($this->get('localized'))
				$object->d["{$this->id}_{$GLOBALS['page']['locale']}"] = $this->convert($value);
			else
				$object->d[$this->id] = $this->convert($value);

			foreach ($object->fields() as $field)
			{
				if (($generate = $field->get('generate')) && ($field->id != $this->id))
				{
					$depends = $field->get('depends');
					if (($depends === true) || (is_array($depends) && in_array($this->id, $depends)) || ((!$depends) && (!$field->get($object))))
						callback($generate, array('object' => $object, 'field' => $this, 'value' => $this->convert($value)));
				}
			}
		}
	
		public function getAllPostfix($key, $value = null, $postfixes = null, $including_original = true)
		{
			$result = array();
			if ($postfixes === null)
				$postfixes = $GLOBALS['site']['locales'];
			if ($including_original)
				$postfixes[] = '';
			$fields = $this->object()->fields();
			foreach ($postfixes as $postfix)
			{
				$fieldId = $this->id;
				if ($postfix)
					$fieldId .= '_' . $postfix;
				$result[$postfix] = $fields->$fieldId->get($key, $value);
			}
			return $result;
		}
		
		public function setAllPostfix($key, $value, $postfixes = null, $including_original = true)
		{
			if ($postfixes === null)
				$postfixes = $GLOBALS['site']['locales'];
			if ($including_original)
				$postfixes[] = '';
			$fields = $this->object()->fields();
			foreach ($postfixes as $postfix)
			{
				$fieldId = $this->id;
				if ($postfix)
					$fieldId .= '_' . $postfix;
				$fields->$fieldId->set($key, $value);
			}
		}
		
		public function object()
		{
			return o($this->object);
		}

		public function key()
		{
			return $this->object . '.' . $this->id;
		}

		public function clear($object)
		{
			$this->set($object, $this->default);
		}

		public function absorb($field, $level = 0)
		{
			$this->type = $field->type;
			$this->size = $field->size;
			$this->default = $field->default;
			$this->set('fixed', $field->get('fixed', false));
			$this->set('unsigned', $field->get('unsigned', false));

			if ($level > 0)
				;
		}
		
		public function localize()
		{
			$this->object()->localize($this->id);
		}

		public static function typeConstantToInteger($type, $safe = false)
		{
			$type = (int) @constant('Field::TYPE_'.strtoupper($type));
			if ($safe && !in_array($type, array(
				self::TYPE_BLOB,
				self::TYPE_BOOLEAN,
				self::TYPE_DATETIME,
				self::TYPE_DATE,
				self::TYPE_TIME,
				self::TYPE_FILE,
				self::TYPE_FLOAT,
				self::TYPE_INTEGER,
				self::TYPE_STRING,
				self::TYPE_TEXT,
				self::TYPE_OBJECT)))
				return $safe;
			return $type;
		}

		public static function typeIntegerToConstant($type)
		{
			return (isset(Field::$types[$type]) ? Field::$types[$type] : '');
		}

		public static function convertValueToPHP($type, $value)
		{
			if ($type == self::TYPE_BOOLEAN)
				return (boolean) $value;
			if ($type == self::TYPE_INTEGER)
				return (integer) $value;
			if ($type == self::TYPE_FLOAT)
				return (float) $value;
			if (($type == self::TYPE_STRING) || ($type == self::TYPE_TEXT))
				return (string) $value;
			if ($type == self::TYPE_FILE)
				return (string) $value;
			if (($type == self::TYPE_DATETIME) || ($type == self::TYPE_DATE) || ($type == self::TYPE_TIME))
			{
				if ($value === '1970-01-01 01:00:00')
					return null;
				else if ($value == 0)
					return null;
				if (is_numeric($value))
					return (integer) $value;
				else if (is_string($value))
					return strtotime($value);
			}
			if (($type == self::TYPE_OBJECT) && is_string($value))
			{
				if ($value == 'NULL')
					return null;
				else
					return unserialize($value);
			}
			return $value;
		}

		public function convert($value, $to = 'php')
		{
			if ($to == 'php')
				return self::convertValueToPHP($this->type, $value);
			else if ($to == 'sql')
			{
				if ($value === null)
					return null;
				$value = $this->convert($value, 'php');
				if (($this->type == self::TYPE_DATETIME) && ($value > 0))
					return date('Y-m-d H:i:s', $value);
				if (($this->type == self::TYPE_DATE) && ($value > 0))
					return date('Y-m-d', $value);
				if (($this->type == self::TYPE_TIME) && ($value > 0))
					return date('H:i:s', $value);
				if ($this->type == self::TYPE_OBJECT)
					return serialize($value);
				return $value;
			}
		}

		public function sql($object)
		{
			return qq($this->convert($this->get($object), 'sql'));
		}

		public function compare($a, $b)
		{
			$value_a = $this->get($a);
			$value_b = $this->get($b);
			return $this->compareValues($value_a, $value_b);
		}

		public function compareValues($value_a, $value_b)
		{
			switch ($this->type)
			{
				case self::TYPE_BOOLEAN:
				case self::TYPE_INTEGER:
				case self::TYPE_DATETIME:
				case self::TYPE_DATE:
				case self::TYPE_FLOAT:
					if ($value_a < $value_b)
						return -1;
					else if ($value_a > $value_b)
						return 1;
					else
						return 0;
					break;
				case self::TYPE_TIME:
				case self::TYPE_STRING:
				case self::TYPE_TEXT:
				case self::TYPE_FILE:
					return strnatcasecmp($value_a, $value_b);
					break;
				case self::TYPE_BLOB:
				default:
					return 0;
			}
		}

		public function sqlName()
		{
			if ($sql = $this->get('sql'))
				return $sql;
			$object = $this->object();
			if ($this->get('localized'))
				return $object->f($this->id.LL)->sqlName();
			switch ($this->type)
			{
				case self::TYPE_BOOLEAN:
				case self::TYPE_INTEGER:
				case self::TYPE_FLOAT:
				case self::TYPE_STRING:
				case self::TYPE_TEXT:
				case self::TYPE_DATETIME:
				case self::TYPE_DATE:
				case self::TYPE_TIME:
				case self::TYPE_BLOB:
				case self::TYPE_FILE:
				case self::TYPE_OBJECT:
					$sql = qi($object->prefixField($this->id));
					break;
				case self::TYPE_CONNECTION:
					if (!($info = $this->connection($object)))
						$sql = '[error]';
					else
					{
						$sql = qi($info['object']->get('table'));
						if ($info['fields'])
							$sql .= ' ON ' . $info['fields']->sql(array('object' => $object));
					}
					break;
				default:
					$sql = '';
					break;
			}
			return $sql;
		}

		public function sqlDefinition()
		{
			if (($temp = $this->get('sql-definition')) !== null)
				return $temp;
			if ($this->size === false)
				return '';
			$object = $this->object();
			$fid = qi('@BEFORE@' . $this->id . '@AFTER@');
			$result = $fid . ' ';
			switch ($this->type)
			{
				case self::TYPE_BOOLEAN:
					$result .= 'BOOL';
					break;
				case self::TYPE_INTEGER:
					$result .= 'INT';
					if ($this->size)
						$result .= '(' . $this->size . ')';
					if ($this->get('fixed'))
						$result .= ' ZEROFILL';
					if ($this->get('unsigned'))
						$result .= ' UNSIGNED';

					/*if ($this->get('autoincrement'))
					{
						if ($object->db()->phptype == 'sqlite')
							$result .= ' AUTOINCREMENT';
						else
							$result .= ' AUTO_INCREMENT';
					}*/
					break;
				case self::TYPE_DATETIME:
					$result .= 'DATETIME';
					break;
				case self::TYPE_DATE:
					$result .= 'DATE';
					break;
				case self::TYPE_TIME:
					$result .= 'TIME';
					break;
				case self::TYPE_FLOAT:
					$result .= 'FLOAT';
					break;
				case self::TYPE_STRING:
					if ($this->get('enum'))
					{
						$temp = array();
						foreach ($this->select() as $value)
							$temp[] = qq($value);
						$result .= ' ENUM (' . implode(', ', $temp) . ')';
					}
					else
					{
						if ($this->get('fixed'))
							$result .= 'CHAR(' . $this->size . ')';
						else
							$result .= 'VARCHAR(' . $this->size . ')';
					}
					break;
				case self::TYPE_TEXT:
						if ($this->size < 65536)
							$result .= 'TEXT';
						else if  ($this->size < 16777216)
							$result .= 'MEDIUMTEXT';
						else
							$result .= 'LONGTEXT';
					break;
				case self::TYPE_FILE:
					$result .= 'VARCHAR(' . ($this->size ? $this->size : 255) . ')';
					break;
				/*case self::TYPE_FILES:
					$result .= 'BLOB';
					break;*/
				//case self::TYPE_TABLE:
				case self::TYPE_BLOB:
				case self::TYPE_OBJECT:
					if ($this->size < 256)
						$result .= 'TINYBLOB';
					else if  ($this->size < 65536)
						$result .= 'BLOB';
					else if  ($this->size < 16777216)
						$result .= 'MEDIUMBLOB';
					else
						$result .= 'LONGBLOB';
					break;
				case self::TYPE_PRIMARY_KEY:
					if (!isset($k)) $k = 'PRIMARY KEY';
				case self::TYPE_KEY:
					if (!isset($k)) $k = 'KEY ' . $fid;
				case self::TYPE_INDEX:
					if (!isset($k)) $k = 'INDEX ' . $fid;
				case self::TYPE_UNIQUE:
					if (!isset($k)) $k = 'UNIQUE ' . $fid;
				case self::TYPE_FULLTEXT:
					if (!isset($k)) $k = 'FULLTEXT ' . $fid;

					$temp = array();
					$loc = false;
					foreach ($this->default as $value)
					{
						$field = $object->f($value);
						if ($field->get('localized'))
						{
							$loc = true;
							$temp[] = qi($value . '_#L#');
						}
						else
							$temp[] = qi($value);
					}

					if ($loc)
						$k = str_replace('@AFTER@', '_#L#@AFTER@', $k);
					$result = $k . ' (' . implode(', ', $temp) . ')';

					if ($loc)
					{
						$temp = array();
						foreach ($GLOBALS['site']['locales'] as $locale)
							$temp[] = str_replace('#L#', $locale, $result);
						$result = implode(",\n", $temp);
					}

					break;
				default:
					return '';
			}
			//$result .= ' NOT NULL';
			//$result .= ' DEFAULT \'' . $this->default . '\''; // nefunguje u BLOB
			$result = str_replace('@BEFORE@', '', $result);
			$result = str_replace('@AFTER@', '', $result);
			return $result;
		}

		public function isReal($type = null)
		{
			if (!$type)
				$type = self::REAL_LOAD | self::REAL_SAVE;
			$temp = (int) $this->get('real', self::REAL_LOAD | self::REAL_SAVE);
			$result = (bool) ($type & $temp);
			if ($type & self::REAL_LOAD)
			{
				if ($this->get('sql-definition') === '')
					$result = false;
				$result = $result && ($this->size !== false);
				if ($this->type > self::TYPE_REAL_LIMIT)
					$result = false;
			}
			if ($type & self::REAL_SAVE)
			{
				if ($this->get('sql-definition') === '')
					$result = false;
				$result = $result && ($this->size !== false);
				if ($this->type > self::TYPE_REAL_LIMIT)
					$result = false;
			}
			return $result;
		}

		public function connection($object = null)
		{
			$result = array('object' => null, 'fields' => null, 'order' => null, 'filter' => null, 'connected' => array());
			if (!isset($this->default['object']) || !isset($this->default['fields']) || !is_array($this->default['fields']))
				return null;
			if (!($result['object'] = @o($this->default['object'])))
				return null;

			$result['order'] = $this->get('order');
			if (($result['order'] === null) && $object)
				$result['order'] = $result['object']->get('order');
			foreach ($this->default['fields'] as $local => $connected)
			{
				if ($connected instanceof Filter)
				{
					$result['fields'][] = $connected;
					$filters = clone($connected);
					foreach ($filters->all() as $filter)
					{
						$temp = $this->a;
						if ($temp && (!($temp instanceof Fc)))
						{
							$ff = (is_string($temp) ? Ff($temp) : $temp);
							if (in_array($ff->oid(), array(null, $object->i)))
							{
								$fid = $ff->fid();
								$temp = Fc($result['connected'][$fid] = $object->$fid);
								$this->a = $temp;
							}
						}
						$temp = $this->b;
						if ($temp && ($temp instanceof Ff))
						{
							$ff = $temp;
							if (in_array($ff->oid(), array(null, $object->i)))
							{
								$fid = $ff->fid();
								$temp = Fc($result['connected'][$fid] = $object->$fid);
								$this->b = $temp;
							}
						}
					}
					$result['filter'][] = $filters;
				}
				else
				{
					$result['fields'][] = Feq(Ff($result['object']->i . '.' . $connected), Ff($object->i . '.' . $local));
					$result['filter'][] = Feq(Ff($result['object']->i . '.' . $connected), Fc($object->$local));
					$result['connected'][$connected] = $object->$local;
				}
			}
			$result['fields'] = ($result['fields'] ? new Fand($result['fields']) : null);
			$result['filter'] = ($result['filter'] ? new Fand($result['filter']) : null);
			return $result;
		}

		public function blob($data, $type = false, $prefix = '', $postfix = '', $is_file = false, $original_name = null)
		{
			$path = $GLOBALS['site']['data'] . 'blob/';
			$ttype = str_replace('/', '_', $type);
			if ($ttype)
				$ttype .= '-';

			do {
				$id = $prefix . $ttype . date('Ymdhis', time()) . sprintf('-%04d', rand(0, 9999)) . $postfix;
			} while (file_exists($path . $id));

			if ($is_file)
				copy($data, $path . $id);
			else
				file_put_contents($path . $id, $data);

			$this->imageTransform($id);
			
			$meta = array();
			$meta['type'] = $type;
			$meta['name'] = $original_name;
			$meta = U::metaArrayToString($meta);
			file_put_contents($path . $id . '.meta', $meta);

			return $id;
		}

		public function imageTransform($id)
		{
			$path = $GLOBALS['site']['data'] . 'blob/';

			$image = @imagecreatefromfile($path . $id);

			if (!is_resource($image))
				return;

			foreach ($this->get('image-variants', array()) as $variant)
			{
				$method = 'image' . array_shift($variant);
				$name = ($variant[0] ? $variant[0] : ('.' . $variant[1] . 'x' . $variant[2])); array_shift($variant);
				$i = call_user_func_array($method, array_merge(array($image), $variant));
				imagejpeg($i, $path . $id . $name);
				imagedestroy($i);
			}

			$i = imagescale($image, 800, 99999);
			$watermark_file = $GLOBALS['site']['base'][0] . 'web/_images/watermark.gif';
			if (is_readable($watermark_file) && $this->get('watermark', true))
				imagewatermarktransparent($i, $watermark_file, $this->get('watermark-alpha', 30), $this->get('watermark-position', 'center'));
			imagejpeg($i, $path . $id . '.full');
			imagedestroy($i);

			imagedestroy($image);
		}

		public function selectOne($value = null)
		{
			$select = $this->get('select');
			if (is_array($select))
			{
				$function = array_slice($select, 0, 2);
				$args = array_slice($select, 2);
				if (is_string($function[0]) && m($function[0]))
					$function[0] = m($function[0]);
				if (is_string($function[0]) && o($function[0]))
					$function[0] = o($function[0]);
				if ($function[0] === null)
					array_shift($function);
				return call_user_func_array($function, array_merge(array($value, $this), $args));
			}
			$values = $this->get('values');
			if (is_array($values))
			{
				if ($value === null)
					return $values;
				else
					return (isset($values[$value]) ? $values[$value] : null);
			}
			return null;
		}

		public function select($v = null)
		{
			if (!is_array($v))
				return $this->selectOne($v);
			else
			{
				$result = array();
				foreach ($v as $value)
					$result[$value] = $this->selectOne($value);
				return $result;
			}
		}

		public function label($mo = null, $id = 'label')
		{
			if ($temp = $this->get('localized-field'))
				$label = $this->get($id, $temp);
			else
				$label = $this->get($id, $this->id);
			if ($id == 'label')
			{
				$label = __($label, $mo);
				if ($this->get('localized-field'))
					$label .= ' (' . __('locale-' . substr($this->id, -2), '@core') . ')';
				return $label;
			}
			else
			{
				$label = __($label . '-' . $id, $mo, false);
				return $label;
			}
		}

		public function visible($formid, $default = false)
		{
			$forms = $this->get('forms');
			if ($forms === null)
				return true;
			if (empty($forms))
				return false;

			$ok = $default;
			foreach ($forms as $temp)
				if ($temp)
				{
					$neg = (substr($temp, 0, 1) == '!');
					if ($neg)
						$temp = substr($temp, 1);

					if ($temp == '*')
						$fits = true;
					else
						$fits = (substr($temp, 0, 1) != '~') ? ($temp == $formid) : preg_match($temp, $formid);
					if ($neg)
						$ok = $ok && (!$fits);
					else
						$ok = $ok || $fits;
				}
			return $ok;
		}

		public function rule($rule)
		{
			$this->rules[] = $rule;
		}

		public function ruleFileSize($max)
		{
			return $this->rule(new FormRule_file_size($max));
		}

		public function ruleFileType($types)
		{
			return $this->rule(new FormRule_file_type($types));
		}

		public function ruleFileTypeRegex($types)
		{
			return $this->rule(new FormRule_file_type_regex($types));
		}

		public function ruleRequired()
		{
			$this->rule(new FormRule_required());
		}

		public function ruleID()
		{
			$this->rule(new FormRule_id());
		}

		public function ruleEmail()
		{
			$this->rule(new FormRule_email());
		}

		public function form($operation, $group, $object, $invocation = null, $options = array())
		{
			if ($this->type == self::TYPE_CONNECTION)
				return;
			$result = null;
			$action = (($invocation instanceof Invocation) ? $invocation->action() : $invocation);
			$prefix = (isset($options['prefix']) ? $options['prefix'] : $object->prefix());
			$form = $group->form();

			$label_module = ($object ? $object->i : $action->module);
			$label = $this->label($label_module);
			$elementId = $prefix . $this->id;
			$element = null;
			$elements = array();
			$value = $this->get($object);

			if ($operation == 1)
			{
				if ($element = $group->find($elementId))
					$value = $element->value;
			}
			if (($operation == 2) || ($operation == 3))
			{
				$filter_values = $action->getV('filter-values', array());
				if (isset($filter_values[$this->id]))
					$filter = $filter_values[$this->id];
				else
					$filter = $this->get('filter-default', array());
			}

			$view = $this->get('view', (isset($options['view']) && $options['view']));

			if (($operation == 1) && $view)
				return;
			if (isset($options['visible']) && $options['visible'])
				;
			else if ($this->get('visible'))
				;
			else if ((($operation == 0) || ($operation == 1)) && !$this->visible($form->id))
				return;
			else if ((($operation == 0) || ($operation == 1)) && ($this->size === false))
				return;
			if ($GLOBALS['page']['location'] == 'administration')
				if (($temp0 = $this->get('localized-locale')) && ($temp1 = Session::get('locale-filter')))
					if ($temp0 != $temp1)
						return;

			if ($this->type == self::TYPE_UNIQUE)
			{
				$temp0 = array();
				foreach ($this->default as $temp1)
					$temp0[] = $prefix . $temp1;
				$group->rule('unique', null, $temp0);
			}
			else  if ($this->type == self::TYPE_NULL)
			{
				if ($operation == 0)
					$element = new FormElement_static($value, $elementId);
			}
			elseif ($this->get('select'))
			{
				$values = $this->select();
				if ($multi = $this->get('multi'))
				{
					if (!is_string($multi))
						$multi = ';';
					if (is_string($value))
						$value = explode($multi, trim($value, $multi));
				}
				if ($operation == 0)
				{
					$element = new FormElement_select($elementId, $label, $values, $multi);
					$element->default = $value;
				}
				if ($operation == 1)
				{
					if ($value === null)
						$value = array($this->default);
					if ($multi && ($this->type == self::TYPE_OBJECT))
						;//$value = $value;
					else if ($multi)
						$value = $multi . implode($multi, $value) . $multi;
					else
						list($value) = $value;
					$this->set($object, $value);
				}

				if ($operation == 2)
				{
					$temp = array('' => '');
					foreach ($values as $k => $v)
						$temp[$k] = $v;
					$element = new FormElement_select($elementId, $label, $temp);
					if (isset($filter['value']))
						$element->default = $filter['value'];
				}
				if ($operation == 3)
				{
					if ($form->submitted())
						$filter['value'] = $group->find($elementId)->first;
					if ((@$filter['value'] !== null) && (@$filter['value'] !== ''))
					{
						if ($multi)
							$result = Flike($this->id, '%' . $multi . $filter['value'] . $multi . '%');
						else
							$result = Feq($this->id, $filter['value']);
					}
				}
			}
			else if ($this->get('files'))
			{
				$use_id = $this->get('use-id', false);
				$use_text = $this->get('use-text', true);
				$settings = $this->get('files');
				if ($settings === true)
					$settings = array();
				$files = $this->get($object);
				if ($operation == 0)
				{
					$groupOriginal = $group;
					$group = new FormGroup($elementId);
					$form->add($group);
					$group->set('after', $groupOriginal->id);
					$group->label = $label;

					foreach ($files as $index => $file)
					{
						$eid = $elementId.'-'.$index;
						$label_index = ''; //' : ' . $index;
						$element = $group->add('file', $eid, '');
						$elements[] = $element;
						$element->allow_upload = false;
						$element->default = $file['blob'];
						if (!$view)
						{
							if ($use_id)
							{
								$element = $group->add('text', $eid.'-id', __('files-id', '@core').$label_index);
								$element->default = $index;
							}
							if ($use_text)
							{
								foreach ($GLOBALS['site']['locales'] as $locale)
								{
									$element = $group->add('text', $eid.'-text-'.$locale, __('files-text', '@core').' ('.__('locale-'.$locale, '@core').')' . $label_index);
									$element->default = $file['text_'.$locale];
								}
							}
						}
					}

					$element = $group->add('static', '<hr />' . sprintf(__('files-warning', '@core'), ((int) ini_get('upload_max_filesize'))));
					for ($index = 0; $index < self::FILES_AT_ONCE; $index++)
					{
						$eid = $elementId.'-add-'.$index;
						$s = ' : '.($index + 1);
						$element = $group->add('file', $eid, __('files-file', '@core'));
						$elements[] = $element;
						if ($use_id)
							$element = $group->add('text', $eid.'-id', __('files-id', '@core'));
						if ($use_text)
							foreach ($GLOBALS['site']['locales'] as $locale)
								$element = $group->add('text', $eid.'-text-'.$locale, __('files-text', '@core').' ('.__('locale-'.$locale, '@core').')');
					}

					$element = null;
				}
				if ($operation == 1)
				{
					$groupOriginal = $group;
					$group = $form->i($elementId);

					$newfiles = array();
					foreach ($files as $index => $file)
					{
						$eid = $elementId.'-'.$index;
						$element = $group->find($eid);
						if ($element->remove)
						{
						}
						else
						{
							$id = ($use_id ? trim($group->valueOf($eid.'-id')) : '');
							if ($use_text)
								foreach ($GLOBALS['site']['locales'] as $locale)
									$file['text_'.$locale] = trim($group->valueOf($eid.'-text-'.$locale));
							if (isset($newfiles[$id]) || !$id)
								$id = $index;
							$newfiles[$id] = $file;
						}
					}

					for ($i = 0; $i < self::FILES_AT_ONCE; $i++)
					{
						$eid = $elementId.'-add-'.$i;
						$element = $group->find($elementId.'-add-'.$i);
						$element->loadValue();
						if ($element->value)
						{
							$element->blobulize();
							$id = ($use_id ? trim($e->valueOf($eid.'-id')) : '');
							if (isset($files[$id]))
								$id = '';
							$file = array(
								'blob' => $element->value,
								'name' => $element->name,
								'size' => $element->size,
								'type' => $element->type,
							);
							foreach ($GLOBALS['site']['locales'] as $locale)
								if ($use_text)
									$file['text_'.$locale] = trim($group->valueOf($eid.'-text-'.$locale));
								else
									$file['text_'.$locale] = '';
							if ($id)
								$newfiles[$id] = $file;
							else
								$newfiles[] = $file;
						}
					}
					$element = null;

					if ($files != $newfiles)
						$form->continue = true;

					$this->set($object, $newfiles);
				}
			}
			else if ($this->type == self::TYPE_FILE)
			{

				if ($operation == 0)
				{
					$element = new FormElement_file($elementId, $label);
					$element->default = $value;
				}
				if ($operation == 1)
				{
					if ($element->remove)
					{
						/*foreach (glob($GLOBALS['site']['data'] . 'blob/' . $this->get($object) . '*') as $temp)
							unlink($temp);*/
						$this->set($object, '');
						if ($temp = $this->get('name'))
							$object->$temp = '';
						if ($temp = $this->get('size'))
							$object->$temp = 0;
						if ($temp = $this->get('type'))
							$object->$temp = '';
					}
					if ($value && ($value != $element->default))
					{
						if ($temp = $this->get('name'))
							$object->$temp = $element->name;
						if ($temp = $this->get('size'))
							$object->$temp = $element->size;
						if ($temp = $this->get('type'))
							$object->$temp = $element->type;
						$element->blobulize();
						$value = $element->value;
						$this->set($object, $value);
					}
				}
			}
			else if ($this->type == self::TYPE_BOOLEAN)
			{
				if ($operation == 0)
				{
					$element = new FormElement_checkbox($elementId, $label);
					$element->default = $value;
				}
				if ($operation == 1)
					$this->set($object, $value);

				if ($operation == 2)
				{
					$element = new FormElement_select($elementId, $label, array('' => '', 'false' => __('false', '@core'), 'true' => __('true', '@core')));
					if (isset($filter['value']))
						$element->default = $filter['value'];
				}
				if ($operation == 3)
				{
					if ($form->submitted())
						$filter['value'] = $group->find($elementId)->first;
					if (@$filter['value'] === 'false')
						$result = Feq($this->id, false);
					else if (@$filter['value'] === 'true')
						$result = Feq($this->id, true);
				}
			}
			else if (($this->type == self::TYPE_INTEGER) || ($this->type == self::TYPE_FLOAT))
			{
				if ($operation == 0)
				{
					$element = new FormElement_text($elementId, $label);
					$element->default = $value;
					$element->rule('numeric', __('rule-numeric'));
				}
				if ($operation == 1)
					$this->set($object, $value);

				if ($operation == 2)
				{
					foreach (array('min' => '>=', 'max' => '<=') as $op => $operator)
					{
						$element = new FormElement_text($elementId . '-' . $op, $label . ': ' . __($op, '@core'));
						if (isset($filter[$op]))
							$element->default = $filter[$op];
						$elements[] = $element;
					}
				}
				if ($operation == 3)
				{
					$result = array();
					if ($form->submitted())
						foreach (array('min' => '>=', 'max' => '<=') as $op => $operator)
							if (is_numeric($v = $group->valueOf($elementId . '-' . $op)))
								$filter[$op] = $v;

					foreach (array('min' => '>=', 'max' => '<=') as $op => $operator)
						if (is_numeric(@$filter[$op]))
							$result[] = Fop($this->id, $this->convert($filter[$op], 'sql'), $operator);

					if ($result)
						$result = new Fand($result);
				}
			}
			else if (($this->type == self::TYPE_DATETIME) || ($this->type == self::TYPE_DATE) || ($this->type == self::TYPE_TIME))
			{
				if ($operation == 0)
				{
					if ($this->type != self::TYPE_TIME)
					{
						$element = new FormElement_date($elementId, $label);
						$element->default = strftime($element->format(), $value);
					}
					else
					{
						$element = new FormElement_text($elementId, $label);
						$element->rule('regex', '\d\d:\d\d');
						$element->default = $value;
					}
				}
				if ($operation == 1)
					$this->set($object, $value);
				if ($operation == 2)
				{
					if ($this->type != self::TYPE_TIME)
					{
						foreach (array('min' => '>=', 'max' => '<=') as $op => $operator)
						{
							$element = new FormElement_date($elementId . '-' . $op, $label . ': ' . __($op, '@core'));
							if (isset($filter[$op]))
								$element->default = $filter[$op];
							$elements[] = $element;
						}
					}
				}
				if ($operation == 3)
				{
					if ($this->type != self::TYPE_TIME)
					{
						$result = array();
						if ($form->submitted())
							foreach (array('min' => '>=', 'max' => '<=') as $op => $operator)
								$filter[$op] = $group->valueOf($elementId . '-' . $op);

						foreach (array('min' => '>=', 'max' => '<=') as $op => $operator)
							if (@$filter[$op])
								$result[] = Fop($this->id, $this->convert($filter[$op], 'sql'), $operator);

						if ($result)
							$result = new Fand($result);
					}
				}
			}
			else if (($this->type == self::TYPE_STRING) || ($this->type == self::TYPE_TEXT))
			{
				if ($operation == 0)
				{
					if ($this->get('textarea') || (($this->type == self::TYPE_TEXT && $this->get('textarea', true))))
					{
						$formatting = ($this->get('html', false) ? 'html' : null);
						$element = new FormElement_textarea($elementId, $label, $formatting);
					}
					else
					{
						$element = new FormElement_text($elementId, $label);
						$element->attributeSet('maxlength', $this->size);
					}
					$element->default = $value;
				}
				if ($operation == 1)
					$this->set($object, $value);

				if ($operation == 2)
				{
					$element = new FormElement_text($elementId, $label);
					if (isset($filter['value']))
						$element->default = $filter['value'];
				}
				if ($operation == 3)
				{
					if ($form->submitted())
						$filter['value'] = $group->valueOf($elementId);
					if (@$filter['value'])
						$result = Flike($this->id, '%'.$filter['value'].'%');
				}
			}
			else if (($this->type == self::TYPE_OBJECT) && $this->get('editable'))
			{
				if ($operation == 0)
				{
					$element = new FormElement_textarea($elementId, $label);
					$newvalue = '';
					if (is_array($value))
						foreach ($value as $k => $v)
						{
							if (is_array($v) || is_bool($v) || is_null($v) || is_object($v))
								$v = serialize($v);
							$newvalue .= '@' . $k . "\n" . str_replace("\n", '\n', $v) . "\n";
						}
					$value = $newvalue;
					$element->default = $value;
				}
				if ($operation == 1)
				{
					$newvalue = array();
					$k = null;
					$a = explode("\n", trim(str_replace("\r", '', $value)));
					while ($i = array_shift($a))
					{
						if ($k === null)
							$k = substr($i, 1);
						else
						{
							$i = str_replace('\n', "\n", $i);
							$v = @unserialize($i);
							$newvalue[$k] = ((($v === false) && ($i != serialize(false))) ? $i : $v);
							$k = null;
						}
					}
					$this->set($object, $newvalue);
				}
			}

			if (($operation == 0) || ($operation == 2))
			{
				if (!$elements && $element)
					$elements = array($element);
				foreach ($elements as $element)
					if ($element)
					{
						if (!$element->group)
							$group->add($element);

						$element->field = $this;
						$element->object = $object;

						if ($operation == 0)
						{
							if ($view)
								$element->view = true;
							$element->note[] = $this->label($label_module, 'note');
							//$element->tooltip = $this->label($label_module, 'tooltip');
							foreach ($this->rules as $rule)
								$element->rule($rule);
						}
					}
			}

			if ($operation == 3)
			{
				$filter_values = $action->getV('filter-values', array());
				$filter_values[$this->id] = $filter;
				$action->setV('filter-values', $filter_values);
			}

			return $result;
		}
	}
?>