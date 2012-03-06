<?php
	// Author: Jakub Macek, CZ; Copyright: Poski.com s.r.o.; Code is 100% my work. Do not copy.

	class Object
	{
		public static			$_									= null;
		public static			$_query_cache						= array();
		public static			$_fields							= array();
		public static			$_options							= array();

		public					$i									= null;
		public					$o									= array();
		public					$d									= array();
		public					$x									= array();

		public static function instantiate($id, $class, $options = array())
		{
			if ($temp0 = o($id))
				return $temp0;
			$object = new $class();
			$object->i = $id;
			$GLOBALS['objects'][$id] = $object;
			self::$_options[$object->i] = $options;
			self::$_fields[$object->i] = new Fields();
			if ($options = Core::get('object-options-'.$object->i))
				self::$_options[$object->i] = array_merge(self::$_options[$object->i], $options);
			if ($module = $object->get('module'))
				if ($options = $module->get('object-options'))
					self::$_options[$object->i] = array_merge(self::$_options[$object->i], $options);

			if (!$object->get('table'))
			{
				$temp = $object->i;
				//$temp = strtr($object->i, '#@', '__');
				$object->set('table', $GLOBALS['site']['prefix'] . $temp);
			}

			for ($phase = 0; $phase < 8; $phase++)
				$object->initialize($phase);

			return $object;
		}

		public function prefix($value = '')
		{
			return $this->i . '-' . $value;
		}

		public function prefixField($text = '')
		{
			return $text;
		}

		public function module()
		{
			return $this->get('module');
		}

		public function options()
		{
			return self::$_options[$this->i];
		}

		public function set($key, $value = null)
		{
			if ($value === null)
				unset(self::$_options[$this->i][$key]);
			else
				self::$_options[$this->i][$key] = $value;
		}

		public function get($key, $default = null)
		{
			return isset(self::$_options[$this->i][$key]) ? self::$_options[$this->i][$key] : $default;
		}

		public function initialize($phase)
		{
			if ($phase == 0)
			{
				$this->define('oid', Field::TYPE_STRING, 64, '', array('forms' => false));
				$this->define('ostatus', Field::TYPE_STRING, false, 'new', array(
					'select' => true,
					'enum' => true,
					'values' => array('normal' => 'normal', 'new' => 'new', 'deleted' => 'deleted'),
					'forms' => false,
				));
				$this->define('otitle', Field::TYPE_STRING, false, '', array('forms' => false), false, false);
				$this->define('g', Field::TYPE_BOOLEAN, false, false, array(), 'return ((!$object->oid) || (isset($object->group) && $object->group));', false);
	
				$this->define('primary_key', Field::TYPE_PRIMARY_KEY, 0, array('oid'));
	
				$this->defineACL($this->get('fake-acl', true));
				$this->defineAttributes($this->get('fake-attributes', true));
			}
			
			if ($phase == 4)
			{
				foreach ($this->get('fields', array()) as $field)
					$this->define(clone($field));
	
				foreach ($this->fields()->keys() as $fid)
					if ($reference = $this->f($fid)->get('reference'))
						$this->f($fid)->absorb(o($reference[0])->f($reference[1]));
	
				if ($modifications = $this->get('modifications'))
					foreach ($modifications as $modification)
						callback($modification, array('object' => $this));
			}
		}

		public function define($id, $type = Field::TYPE_NULL, $size = null, $default = null, $options = array(), $get = null, $set = null)
		{
			if (!is_object($id))
				$id = new Field($id, $type, $size, $default, $options, $get, $set);
			$field = $id;
			$this->fields()->{$field->id} = $field;
			$field->object = $this->i;
			Field::$_[$field->key()] = $field;
			if ($field->get('localized'))
				$field->localize();

			if ($field->get('select'))
			{
				$this->define(
					$field->id . '+',
					Field::TYPE_STRING,
					false,
					'',
					array(
						'forms' => array(),
					),
					'return $object->f(\'' . $field->id . '\')->select($object->f(\'' . $field->id . '\')->get($object));',
					false
				);
				$this->define(
					$field->id . '__0',
					Field::TYPE_STRING,
					false,
					'',
					array(
						'forms' => array(),
					),
					'return $object->f(\'' . $field->id . '\')->select($object->f(\'' . $field->id . '\')->get($object));',
					false
				);
			}

			if (($field->type == Field::TYPE_DATETIME) || ($field->type == Field::TYPE_DATE))
			{
				$default_format = 'Y-m-d H:i:s';
				if ($field->type == Field::TYPE_DATE)
					$default_format = 'Y-m-d';
				$this->define(
					$field->id . '+',
					Field::TYPE_STRING,
					false,
					date($field->get('format', $default_format), 0),
					array(
						'forms' => array(),
						'format' => $field->get('format'),
					),
					'return date($field->get("format", "'.$default_format.'"), $object->{\'' . $field->id . '\'});',
					'$object->{\'' . $field->id . '\'} = strtotime($value);'
				);
				$this->define(
					$field->id . '__0',
					Field::TYPE_STRING,
					false,
					date($field->get('format', $default_format), 0),
					array(
						'forms' => array(),
						'format' => $field->get('format'),
					),
					'return date($field->get("format", "'.$default_format.'"), $object->{\'' . $field->id . '\'});',
					'$object->{\'' . $field->id . '\'} = strtotime($value);'
				);
			}

			return $field;
		}

		public function defineACL($fake = false)
		{
			$this->define('ouid', Field::TYPE_STRING, 64, '', array(
				'select' => array('@users', 'select_id'),
				'forms' => array('~acl~'),
			));
			$this->define('ouser', Field::TYPE_CONNECTION, null, array('object' => '@users', 'fields' => array('ouid' => 'id')), array(
				'one' => true,
			));

			$this->define('orid', Field::TYPE_STRING, 64, '', array(
				'forms' => array('~acl~'),
			));

			/*$this->define('oacl', Field::TYPE_CONNECTION, null, array('object' => 'acl', 'fields' => array('oid' => 'pid')), array(
				'recurse' => true,
				'forms' => false,
			));*/
			//HACK $this->define('oacl', Field::TYPE_OBJECT, 64, array(), array('forms' => array()));
			$this->define('oacl', Field::TYPE_OBJECT, false, array(), array('forms' => array()));

			if ($fake)
			{
				$this->f('ouid')->size = false;
				$this->f('orid')->size = false;
				$this->f('oacl')->size = false;
			}
		}

		public function defineAttributes($fake = false)
		{
			if (!isset($GLOBALS['site']['flags']['@attributes']))
				$fake = true;

			$this->define('oattrs', Field::TYPE_STRING, 255, '', array(
				'forms' => array('~acl~'),
				'multi' => ';',
			));
			if (!$fake)
				$this->f('oattrs')->set('select', array('@attribute_types', 'select_key'));
			$this->define('oattributes', Field::TYPE_OBJECT, 65535, array(), array('forms' => array()));

			if ($fake)
			{
				$this->f('oattrs')->size = false;
				$this->f('oattributes')->size = false;
			}
		}

		public function defineV($id, $type = Field::TYPE_NULL, $size = false, $default = null, $options = array(), $get = null, $set = null)
		{
			$field = $this->define($id, $type, $size, $default, $options, $get, $set);
			$field->set('forms', array());
			return $field;
		}

		public function defineOCreated()
		{
			$f = $this->define('ocreated', Field::TYPE_DATETIME, null, 0, array('forms' => array()));
			return $f;
		}

		public function defineOModified()
		{
			$f = $this->define('omodified', Field::TYPE_DATETIME, null, 0, array('forms' => array()));
			return $f;
		}

		public function defineTimestamp($id = 'timestamp')
		{
			$f = $this->define($id, Field::TYPE_DATETIME, null, 0/*, array('forms' => array())*/);
			return $f;
		}

		public function defineBlob($full = false, $id = 'blob')
		{
			$f = $this->define($id, Field::TYPE_FILE, null, '');
			$f->set('image-variants', array(array('scaleexpand', '.thumb', 150, 100, 'ffffff')));
			if ($full)
			{
				$f->set('size', $id.'_size');
				$f->set('type', $id.'_type');
				$f->set('name', $id.'_name');
				$this->define($id.'_size', Field::TYPE_INTEGER, null, 0, array('forms' => array()));
				$this->define($id.'_type', Field::TYPE_STRING, 255, 0, array('forms' => array()));
				$this->define($id.'_name', Field::TYPE_STRING, 255, 0, array('forms' => array()));
			}
			return $f;
		}

		public function defineImage($id = 'image')
		{
			$f = $this->define($id, Field::TYPE_FILE, null, '');
			$f->set('image-variants', array(array('scaleexpand', '.thumb', 150, 100, 'ffffff')));
			return $f;
		}

		public function defineAttachments($id = 'attachments')
		{
			$f = $this->define($id, Field::TYPE_OBJECT, 65535, array());
			$f->set('files', true);
			return $f;
		}

		public function defineImages($id = 'images')
		{
			$f = $this->define($id, Field::TYPE_OBJECT, 65535, array());
			$f->set('files', true);
			$f->set('image-variants', array(array('scaleexpand', '.thumb', 150, 100, 'ffffff')));
			return $f;
		}

		public function defineGroup()
		{
			$f = $this->define('group', Field::TYPE_BOOLEAN, null, false);
			return $f;
		}

		public function definePriority()
		{
			$f = $this->define('priority', Field::TYPE_INTEGER, null, 0);
			return $f;
		}

		public function defineString($id, $size = 255, $default = '')
		{
			$f = $this->define($id, Field::TYPE_STRING, $size, $default);
			return $f;
		}

		public function defineStringLocalized($id, $size = 255, $default = '')
		{
			$f = $this->defineString($id, $size, $default);
			$f->localize();
			return $f;
		}

		public function defineText($id, $size = 65535, $default = '')
		{
			$f = $this->define($id, Field::TYPE_TEXT, $size, $default);
			return $f;
		}

		public function defineTextLocalized($id, $size = 65535, $default = '')
		{
			$f = $this->defineText($id, $size, $default);
			$f->localize();
			return $f;
		}

		public function defineTextHTML($id, $size = 65535, $default = '')
		{
			$f = $this->defineText($id, $size, $default);
			$f->set('html', true);
			return $f;
		}

		public function defineTextLocalizedHTML($id, $size = 65535, $default = '')
		{
			$f = $this->defineTextHTML($id, $size, $default);
			$f->localize();
			return $f;
		}		

		public function defineIdAutoIncrement()
		{
			$f = $this->define('id', Field::TYPE_INTEGER, null, 0, array('forms' => array(), 'autoincrement' => true));
			$this->define('index_id', Field::TYPE_INDEX, null, array('id'));
			return $f;
		}

		public function definePid($parent = null)
		{
			if (!$parent)
				$parent = $this->i;
			$f = $this->define('pid', Field::TYPE_STRING, 64, '', array(
				'forms' => array(),
				'reference' => array($parent, 'oid'),
			));
			$this->define('connection_parent', Field::TYPE_CONNECTION, null, array('object' => $parent, 'fields' => array('pid' => 'oid')), array(
				'one' => true,
			));
			if (method_exists(m($parent), 'select_oid'))
				$f->set('select', array($parent, 'select_oid'));
			$this->define('index_pid', Field::TYPE_INDEX, null, array('pid'));
			return $f;
		}

		public function defineUnique($fields, $name = null)
		{
			if (!$name)
			{
				$name = 'index';
				foreach ($fields as $field)
					$name .= '_' . $field;
			}
			$f = $this->define($name, Field::TYPE_UNIQUE, null, $fields);
			return $f;
		}

		public function defineIndex($fields, $name = null)
		{
			if (!$name)
			{
				$name = 'index';
				foreach ($fields as $field)
					$name .= '_' . $field;
			}
			$f = $this->define($name, Field::TYPE_INDEX, null, $fields);
			return $f;
		}

		public function localize($field)
		{
			foreach ($GLOBALS['site']['locales'] as $locale)
			{
				$temp = clone($this->f($field));
				$temp->id = $field . '_' . $locale;
				$temp->set('localized', false);
				$temp->set('localized-field', $field);
				$temp->set('localized-locale', $locale);
				$this->fields()->_[$temp->id] = $temp;
			}
			$this->f($field)->set('localized', true);
			$this->f($field)->size = false;
		}

		public function fields($fields = null, $clone = false)
		{
			if ($fields === null)
			{
				$result = self::$_fields[$this->i];
				if ($clone)
					$result = clone($result);
				return $result;
			}

			$temp1 = array();
			foreach ($fields as $k => $v)
				if (is_object($v))
					$temp1[] = $v;
				else if (is_int($k) && is_string($v))
					$temp1[] = $this->f($v);
				else
					$temp1[] = $this->f($k);

			$temp2 = array();
			$temp2['^'] = null;
			foreach ($temp1 as $field)
				$temp2[$field->id] = $field;
			$temp2['$'] = null;

			$temp3 = array();
			foreach ($temp2 as $id => $field)
				if (!$field || (!$field->get('form-before') && !$field->get('form-after')))
				{
					$temp3[$id] = $field;
					unset($temp2[$id]);
				}

			$work = true;
			while ($work)
			{
				$work = false;
				foreach ($temp2 as $id => $field)
				{
					if (($before = $field->get('form-before')) && isset($temp3[$before]))
					{
						$temp3 = array_insert($temp3, $before, array($id => $field), true);
						unset($temp2[$id]);
						$work = true;
					}
					if (($after = $field->get('form-after')) && isset($temp3[$after]))
					{
						$temp3 = array_insert($temp3, $after, array($id => $field), false);
						unset($temp2[$id]);
						$work = true;
					}
				}
			}
			unset($temp3['^']);
			unset($temp3['$']);

			$result = new Fields();
			foreach ($temp3 as $field)
				$result->add($field);
			return $result;
		}

		public function f($id)
		{
			if (!isset($this->fields()->$id))
				return null;
			return $this->fields()->$id;
		}

		public function attributes($attributes = null)
		{
			return Fields::attributes($attributes, $this->oattrs);
		}

		public function __get($key)
		{
			$field = $this->f($key);
			if (!$field)
				error('unknown field: ' . $key);
			return $field->get($this);
		}

		public function __set($key, $value)
		{
			if (substr($key, -12) == '_all_locales')
			{
				$key = substr($key, 0, strlen($key) - 12);
				foreach ($GLOBALS['site']['locales'] as $locale)
					$this->{$key . '_' . $locale} = $value;
			}
			else
			{
				$field = $this->f($key);
				if (!$field)
					error('unknown field: ' . $key);
				$field->set($this, $value);
			}
		}

		public function __isset($key)
		{
			return isset($this->fields()->$key);
		}

		public function clear()
		{
			foreach ($this->fields() as $field)
				$field->clear($this);
		}

		public function acl($invocation, $action)
		{
			return null;
		}

		public function listStyle($column = null)
		{
		}

		public function dataGet($key, $default = null)
		{
			return (isset($this->data[$key]) ? $this->data[$key] : $default);
		}

		public function dataSet($key, $value = null)
		{
			$data = $this->data;
			if ($value === null)
				unset($data[$key]);
			else
				$data[$key] = $value;
			$this->data = $data;
		}

		public function attributeGet($key, $default = null)
		{
			$attributes = $this->attributes();
			if (!isset($attributes->$key))
				return $default;
			return $attributes->$key->get($this, $default);
		}

		public function attributeSet($key, $value = null)
		{
			$attributes = $this->attributes();
			if (!isset($attributes->$key))
				return null;
			return $attributes->$key->set($this, $value);
		}

		public function event($ev)
		{
			switch ($ev)
			{
				case 'load.before':
					break;
				case 'load.after':
					$this->o = $this->d;
					foreach ($this->fields() as $field)
						if ($field->type == Field::TYPE_OBJECT)
							$this->o[$field->id] = $field->default;
					break;
				case 'save.before':
					if (isset($this->ocreated) && ($this->ocreated == 0))
						$this->ocreated = time();

					if (isset($this->omodified))
						$this->omodified = time();

					if (isset($this->id) && $this->f('id')->get('autoincrement') && ($this->id == 0))
						$this->generate(0, 'id');

					if (!$this->ouid && m('@users') && defined('USER') && USER)
						$this->ouid = USER;

					if (m('@attributes'))
					{
						$this->oattributes = array();
						$attributes = o('@attributes')->load(Feq('object', $this->oid));
						$as = array();
						foreach ($attributes as $attribute)
							$as[$attribute->key] = $attribute->getValue();
						$this->oattributes = $as;
					}

					foreach ($this->fields() as $field)
						if ($field->type == Field::TYPE_CONNECTION)
						{
							if (!$field->get('recurse'))
								break;
							if (!isset($event['object']->x[$field->id]))
								break;
							$info = $field->connection($event['object']);
							foreach ($event['object']->x[$field->id] as $o)
								foreach ($info['connected'] as $key => $value)
									$o->$key = $value;
							foreach ($event['object']->x[$field->id] as $o)
								$o->save();
							break;
						}

					break;
				case 'save.after':
					break;
				case 'delete.before':
					break;
				case 'delete.after':
					foreach ($this->fields() as $field)
						if ($field->type == Field::TYPE_CONNECTION)
						{
							if (!$field->get('recurse'))
								break;
							$info = $field->connection($event['object']);
							$info['object']->delete($info['filter']);
						}

					if (m('@attributes'))
						o('@attributes')->delete(Feq('object', $this->oid));
					break;
			}
		}

		public function generate($value = null, $fid = 'oid', $method = null)
		{
			if ($fid == 'oid')
				$method = 'oid';
			$field = $this->f($fid);

			if ($value === 0)
				$method = 'sequence';

			switch ($method)
			{
				case 'date':
					if ($this->$fid)
						return;
					$format = (($field->type == Field::FIELD_TYPE_INTEGER) ? 'dhis' : 'Ymd-his-');
					$counter = 0;
					do
						$temp = date($format) . sprintf('%04d', $counter++);
					while ($this->count(Feq($field, $temp)));
					$this->$fid = $temp;
					break;
				case 'sequence':
					if ($this->$fid)
						return;
					$query = 'SELECT MAX(CAST(' . qi($fid) . ' AS UNSIGNED)) FROM ' . qi($this->get('table'));
					$max = (int) qo($query);
					$this->$fid = $max + 1;
					break;
				case 'rewrite':
					if (!$value)
						return;
					$value = substr(U::urlize($value), 0, $field->size - 5) . '-';
					$counter = 0;
					$temp = substr(U::urlize($value), 0, $field->size);
					while (qo("SELECT COUNT(".qi('oid').") FROM ".qi($this->get('table'))." WHERE ".qi('oid')." != ".qq($this->oid)." AND ".qi($field->id)." = ".qq($temp)))
						$temp = $value . sprintf('%04d', $counter++);
					$this->$fid = $temp;
					break;
				case 'oid':
				default:
					if ($this->$fid)
						return;
					$this->$fid = Session::oid($this->i);
					break;
			}
		}

		public function form($operation, $group, $fields = null, $invocation = null, $options = array())
		{
			$fields = clone($this->fields($fields));
			foreach ($fields as $field)
				$field->form($operation, $group, $this, $invocation, $options);
		}

		public function parents($first = false)
		{
			if (isset($this->x['parent']) && $first)
				return $this->x['parent'];
			if (isset($this->x['parents']) && !$first)
				return $this->x['parents'];
			$result = new Objects();

			$parent = null;
			if ($fid = $this->get('parent-connection'))
			{
				$field = $this->f($fid);
				$info = $field->connection($this);
				$parent = $info['object']->load($info['filter'], true);
				if (!$parent)
				{
					$parent = clone($info['object']);
					$parent->x['placeholder'] = true;
				}
			}
			else if ($this->f('pid'))
			{
				$reference = $this->f('pid')->get('reference');
				if (o($reference[0]))
					$parent = o($reference[0])->load(Feq($reference[1], $this->pid), true);
			}

			$this->x['parent'] = $parent;
			if ($first)
				return $parent;
			if ($parent)
			{
				$result->{null} = $parent;
				$result->add($parent->parents());
			}

			$this->x['parents'] = $result;
			return $result;
		}

		public function children()
		{
			//TODO dodelat reference - vice typu potomku
			if (isset($this->x['children']))
				return $this->x['children'];
			$result = array();
			$result[0] = $this->load(Feq('pid', $this->oid));
			$this->x['children'] = $result;
			return $result;
		}

		public function queryCache($query = null, $result = null)
		{
			if ($query === true) // start cache
				self::$_query_cache = array();
			else if ($query === false) // stop cache
				self::$_query_cache = null;
			else if (self::$_query_cache === null) // if stopped, exit
				return null;
			else if (($query === null) && ($result === null)) // clear
				self::$_query_cache = array();
			elseif ($result === null) // get
				return (isset(self::$_query_cache[$query]) ? self::$_query_cache[$query] : null);
			else // set
				self::$_query_cache[$query] = $result;
			return null;
		}

		public function sqlTable()
		{
			return qi($this->get('table'));
		}

		public function cast($o)
		{
			$result = clone($this);
			if (is_object($o))
				$result->d = $o->d;
			else
				$result->d = $o;
			return $result;
		}

		public function fromArray($rows)
		{
			$fid_oid = $this->prefixField('oid');
			$objects = new Objects();
			$fields = $this->fields();
			foreach ($rows as $row)
			{
				$oid = null;
				if (isset($row[$fid_oid]))
					$oid = $row[$fid_oid];
				if (isset(self::$_->$oid))
					$objects->$oid = self::$_->$oid;
				else if ($oid && isset($objects->$oid))
					;
				else
				{
					$object = clone($this);
					//Event::invoke(array("object.load.before", "object.{$this->i}.load.before"), array('object' => $object));
					$object->event('load.before');
					foreach ($row as $k => $v)
						if (($v !== null) && isset($fields->$k))
							$object->$k = $v;
					$object->ostatus = 'normal';
					$object->event('load.after');
					//Event::invoke(array("object.load.after", "object.{$this->i}.load.after"), array('object' => $this));
					$objects->{null} = $object;
				}
			}
			return $objects;
		}

		public function create()
		{
			$this->drop();
			$query = array();
			foreach ($this->fields() as $field)
				if (!($field->type & Field::TYPE_MASK_INDEX))
					if ($temp = $field->sqlDefinition($this))
						$query[] = "\t" . $temp;
			foreach ($this->fields() as $field)
				if ($field->type & Field::TYPE_MASK_INDEX)
					if ($temp = $field->sqlDefinition($this))
						$query[] = "\t" . $temp;

			$query = "CREATE TABLE ".qi($this->get('table'))." (\n" . implode(",\n", $query) . "\n";
			if ($temp = $this->get('create-table-0'))
				$query .= $temp;
			$query .= ")";
			//$query .= "ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci";
			$result = q($query);
		}

		public function exists()
		{
			$query = "SHOW TABLES LIKE '{$this->get('table')}'";
			$result = qa($query);
			return count($result) ? true : false;
		}

		public function drop()
		{
			$result = q("DROP TABLE IF EXISTS ".qi($this->get('table'))."");
		}

		public function sqlWhere($filter)
		{
			if ($filter)
				return $filter->sql(array('object' => $this));
			else
				return null;
		}

		public function sqlPrepareCallback($matches)
		{
			$match = str_replace('~', $this->i, $matches[1]);
			if (strpos($match, '.') !== false)
			{
				$parts = explode('.', $match);
				$fid = array_pop($parts);
				$oid = array_shift($parts);
				foreach ($parts as $temp)
				{
					if (!($object = @o($oid)))
						return '[error-1]';
					if (!($field = $this->f($temp)))
						return '[error-2]';
					if (($field->type != Field::TYPE_CONNECTION) || !isset($field->default['object']))
						return '[error-3]';
					$oid = $field->default['object'];
				}
				if (!($object = @o($oid)))
					return '[error-4]';
				if (!($field = $object->f($fid)))
					return '[error-5]';
				return $field->sqlName();
			}
			else
			{
				if (!($object = @o($match)))
					return '[error]';
				return qi($this->get('table'));
			}
		}

		public function sqlSelect($filter = null, $order = null, $limit = null, $count = false)
		{
			if ($filter && !is_object($filter))
				error('where: ' . gettype($filter));
			$query = "SELECT " . ($count ? "COUNT(DISTINCT {$this->f('oid')->sqlName()})" : "*") . " FROM ".qi($this->get('table'))." ".qi($this->i);
			if ($filter)
			{
				$filter = $filter->sql(array('object' => $this));
				$filter = trim($filter);
				if ($filter)
					$query .= " WHERE " . $filter;
			}

			if (($order === null) || ($order === false))
				$order = $this->get('order');
			if ($order && is_array($order) && count($order))
			{
				$o = array();
				foreach ($order as $temp)
				{
					$field = str_replace(array('+', '-'), array('', ''), $temp);
					if (!$this->f($field))
						error("missing field '$field' in object '{$this->i}'");
					if ($this->f($field)->get('localized'))
						$field .= '_' . $GLOBALS['page']['locale'];
					if (strpos($temp, '-') !== false)
						$o[] = qi($field) . " DESC";
					else
						$o[] = qi($field);
				}
				$query .= " ORDER BY " . implode (", ", $o);
			}

			if ($limit) // count, offset
				$query .= " LIMIT " . (is_array($limit) ? ($limit[1] . ", " . $limit[0]) : $limit);

			return $query;
		}

		public function sql($query, $arguments = array())
		{
			$select = (strtoupper(substr($query, 0, 6)) == 'SELECT');
			/*$query = preg_replace_callback('~\|([\w:\./\~]+?)\|~', array($this, 'sqlPrepareCallback'), $query);
			foreach ($arguments as $k => $v)
				$query = str_replace('|%' . $k . '%|', qe($v), $query);*/
			//TODO upravit syntaxi

			if ($objects = $this->queryCache($query))
			{
				if ($GLOBALS['page']['log'] & LOG_STORAGE)
					U::log('storage', $this->i . ':sql-cached', $query);
				return $objects;
			}

			if ($GLOBALS['page']['log'] & LOG_STORAGE)
				U::log('storage', $this->i . ':sql', $query);

			$result = ($select ? qa($query) : q($query));

			if ($select)
			{
				$objects = $this->fromArray($result);
				$this->queryCache($query, $objects);
				return $objects;
			}
			else
				return true;
		}

		public function load($filter = null, $order = null, $limit = null)
		{
			$query = $this->sqlSelect($filter, (($order === true) ? null : $order), (($order === true) ? 1 : $limit));
			if ($GLOBALS['page']['log'] & LOG_STORAGE_FIREPHP)
				fbo($query);
			$objects = $this->sql($query);
			if (($order === true) && ($objects instanceof Objects))
				return $objects->first();
			return $objects;
		}

		public function loadOne($value, $field = 'oid')
		{
			return $this->load(Feq($field, $value), true);
		}

		public function count($filter = null)
		{
			$query = $this->sqlSelect($filter, null, null, true);
			if ($GLOBALS['page']['log'] & LOG_STORAGE)
				U::log('storage', $this->i . ':count', $query);
			$result = qo($query);

			return (int) $result;
		}

		public function sqlArray($insert = false)
		{
			$data = array();
			foreach ($this->fields()->iterator() as $field)
				if ($field->isReal(Field::REAL_SAVE))
				{
					$k = $this->prefixField($field->id);
					$v = $field->get($this);
					/*if (($v == $field->default) && !$v) // zruseno kvuli vyhledavani, je treba ukladat i nulove hodnoty
						$v = null;*/
					$v = $field->convert($v, 'sql');
					$temp = $insert;
					if (!$temp)
					{
						$o = null;
						if (isset($this->o[$k]) && ($field->type != Field::TYPE_OBJECT))
							$o = $field->convert($this->o[$k], 'sql');
						if ($o != $v)
							$temp = true;
					}
					if ($temp)
						$data[$k] = $v;
				}
			return $data;
		}

		public function save($object = null)
		{
			if (is_array($object))
			{
				foreach ($object as $o)
					$this->save($o);
				return;
			}
			else if ($object !== null)
				return $object->save();

			$this->queryCache();
			//Event::invoke(array("object.save.before", "object.{$this->i}.save.before"), array('object' => $this));
			$proceed = $this->event('save.before');
			if ($proceed === false)
				return false;

			$this->ostatus = 'normal';
			$insert = false;
			if (!$this->oid)
			{
				$this->generate();
				$insert = true;
			}
			else if (!$this->count(Feq('oid', $this->oid)))
				$insert = true;

			$data = $this->sqlArray($insert);

			if ($GLOBALS['page']['log'] & LOG_STORAGE)
				U::log('storage', $this->i . ':save' . ($insert ? '-insert' : '-update'), $this->oid . ' - ' . count($data) . ': ' . implode(', ', array_keys($data)));

			if ($data)
			{
				if ($insert)
					$result = qinsertdirect($this->get('table'), $data, '');
				else
				{
					$data['oid'] = $this->oid;
					$result = qupdatedirect($this->get('table'), $data, 'oid', '');
				}
			}
			else
				$result = true;

			$this->event('save.after');
			//Event::invoke(array("object.save.after", "object.{$this->i}.save.after"), array('object' => $this));
			self::$_->{$this->oid} = $this;
		}

		public function delete($filter = null, $tree = false)
		{
			$this->queryCache();

			if (($filter !== null) || (!$this->oid))
			{
				$objects = $this->load($filter);
				foreach ($objects->iterator() as $object)
					$object->delete(null, $tree);
			}
			else
			{
				if ($GLOBALS['page']['log'] & LOG_STORAGE)
					U::log('storage', $this->i . ':delete', $this->oid);
				//Event::invoke(array("object.delete.before", "object.{$this->i}.delete.before"), array('object' => $this));
				$proceed = $this->event('delete.before');
				if ($proceed !== false)
				{
					q("DELETE FROM ".qi($this->get('table'))." WHERE ".qi('oid')."=".qq($this->oid));
					$this->ostatus = 'deleted';
					$this->event('delete.after');
					//Event::invoke(array("object.delete.after", "object.{$this->i}.delete.after"), array('object' => $this));
				}
			}
		}
	}

	class Objects implements Iterator
	{
		public					$_									= array();

		public function __get($id)
		{
			if (!$id)
			{
				$temp = array_values($this->_);
				return (count($temp) ? $temp[0] : false);
			}
			if (isset($this->_[$id]))
				return $this->_[$id];
			else
				return null;
		}

		public function __set($id, $value)
		{
			if (!$id)
			{
				$value->generate();
				$this->_[$value->oid] = $value;
			}
			else
				$this->_[$id] = $value;

			if ($this !== object::$_)
				object::$_->$id = $value;
		}

		public function __isset($id)
		{
			return isset($this->_[$id]);
		}

		public function __unset($id)
		{
			unset($this->_[$id]);
		}

		public function size()						{ return count($this->_); }
		public function count()						{ return count($this->_); }
		public function rewind()					{ reset($this->_); }
		public function current()					{ return current($this->_); }
		public function key()						{ return key($this->_); }
		public function keys()						{ return array_keys($this->_); }
		public function next()						{ return next($this->_); }
		public function valid()						{ return ($this->current() !== false); }

		public function iterator()
		{
			return new ContainerIterator($this);
		}

		public function __construct()
		{
			$args = func_get_args();
			foreach ($args as $arg)
				$this->add($arg);
		}

		public function first()
		{
			if (!count($this->_))
				return null;
			return array_shift(array_slice($this->_, 0, 1));
		}

		public function last()
		{
			if (!count($this->_))
				return null;
			return array_shift(array_values(array_slice($this->_, -1, 1)));
		}

		public function add($arg, $key = null)
		{
			if ($arg instanceof Objects)
			{
				foreach ($arg->iterator() as $object)
					$this->add($object);
			}
			else
			{
				if ($key === null)
					$key = $arg->oid;
				if ($key)
					$this->_[$key] = $arg;
				else
					$this->_[] = $arg;
			}
		}
	}

	class acl/* extends object*/
	{
		public static			$container							= array();

		const					TYPE_ALLOW_ALL						= 1;
		const					TYPE_DENY_ALL						= 2;
		const					TYPE_ALLOW							= 3;
		const					TYPE_DENY							= 4;
		const					TYPE_SCRIPT							= 5;
		const					TYPE_FINAL							= 128;

		public					$ouid								= '';
		public					$orid								= '';
		public					$oacl								= array();

		public					$subject							= '';
		public					$type								= 0;
		public					$value								= '';

		public static function add($module, $action, $name = null, $options = array())
		{
			$temp = new acl(false);
			$temp->ouid = $action;
			$temp->orid = $module;
			$temp->oacl = $options;
			$temp->subject = $module . '/' . $action;
			$temp->value = ($name ? $name : __($action, $module));
			self::$container[$temp->subject] = $temp;
		}

		public function get($key, $value = null)
		{
			return (isset($this->oacl[$key]) ? $this->oacl[$key] : $value);
		}

		public function set($key, $value)
		{
			if ($key === null)
				foreach ($value as $k => $v)
					$this->set($k, $v);
			else if (($value === null) && isset($this->oacl[$key]))
				unset($this->oacl[$key]);
			else if ($value !== null)
				$this->oacl[$key] = $value;
			else
				$this->oacl[$key] = $value;
		}

		public function __construct($ouid = null, $orid = null, $oacl = array())
		{
			if ($ouid === false)
				return;
			$result = $this->apply($ouid, $orid, $oacl);
			foreach (array('ouid', 'orid', 'oacl') as $k)
				$this->$k = $result->$k;
		}

		public static function rule($subject, $type, $value)
		{
			$temp = new acl();
			$temp->subject = $subject;
			$temp->type = $type;
			$temp->value = $value;
			return $temp;
		}

		public function apply($ouid = null, $orid = null, $oacl = array())
		{
			$result = clone($this);

			if (is_object($ouid))
			{
				if (!($ouid instanceof object))
					error('not instance of object');
				if ($orid === false)
					foreach (array('ouid', 'orid', 'oacl') as $fid)
						list($ouid->$fid, $result->$fid) = array($result->$fid, $ouid->$fid);
				$oacl = array();
				foreach ($ouid->oacl as $v)
					$oacl[$v->subject] = array($v->type, $v->value);
				$orid = $ouid->orid;
				$ouid = $ouid->ouid;
			}

			if ($ouid !== null)
				$result->ouid = $ouid;
			if ($orid !== null)
				$result->orid = $orid;
			foreach ($oacl as $k => $v)
			{
				if (is_object($v))
					$r = clone($v);
				else
				{
					$r = new acl(false);
					$r->subject = $k;
					if (is_bool($v))
						$r->type = $v ? acl::TYPE_ALLOW_ALL : acl::TYPE_DENY_ALL;
					if (is_string($v))
					{
						$r->type = acl::TYPE_ALLOW;
						$r->value = $v;
					}
					if (is_array($v))
					{
						$r->type = $v[0];
						$r->value = $v[1];
					}
				}
				$result->oacl[] = $r;
			}
			return $result;
		}

		public function select_type($value = null)
		{
			$result = array(
				acl::TYPE_ALLOW_ALL => __('type-allow-all', 'acl'),
				acl::TYPE_DENY_ALL => __('type-deny-all', 'acl'),
				acl::TYPE_ALLOW => __('type-allow', 'acl'),
				acl::TYPE_DENY => __('type-deny', 'acl'),
			);
			if (m('@users') && isAdministrator())
			{
				$result[acl::TYPE_FINAL] = __('type-final', 'acl');
				$result[acl::TYPE_FINAL + acl::TYPE_ALLOW_ALL] = __('type-allow-all-final', 'acl');
				$result[acl::TYPE_FINAL + acl::TYPE_DENY_ALL] = __('type-deny-all-final', 'acl');
				$result[acl::TYPE_FINAL + acl::TYPE_ALLOW] = __('type-allow-final', 'acl');
				$result[acl::TYPE_FINAL + acl::TYPE_DENY] = __('type-deny-final', 'acl');
			}
			return object::select($value, $result);
		}

		public function acl($invocation, $action)
		{
			return null;
		}
	}

	class Filter
	{
		public				$a								= null;
		public				$b								= null;

		public function __construct($a = null, $b = null)
		{
			$this->a = $a;
			$this->b = $b;
		}

		public function getQuotedField($object = null)
		{
			return qi($this->a);
		}

		public function all()
		{
			return array($this);
		}

		public function sql($options)
		{
			return "(1)";
		}

		public function validate($options)
		{
			return true;
		}

		public function prepare()
		{
			if (!is_object($this->a))
				$this->a = Ff($this->a);
			if (!is_object($this->b))
				$this->b = Fc($this->b);
		}

		function __clone()
		{
			foreach($this as $k => $v)
				if(gettype($v) == 'object')
					$this->$k = clone($this->$k);
				else if (gettype($v) == 'array')
				{
					$temp = array();
					foreach ($v as $kk => $vv)
						if (gettype($vv) == 'object')
							$temp[$kk] = clone($vv);
						else
							$temp[$kk] = $vv;
					$this->$k = $temp;
				}
		}
		
		/*function factoryConstant($_value, $options = array())
		{
			extract($options, EXTR_SKIP);
			
			if (!isset($object))
				return $_value;
			
			
				
						$a = (($this->a instanceof Filter) ? $this->a->sql($options) : Ff($this->a)->sql($options));
			$b = (($this->b instanceof Filter) ? $this->b->sql($options) : Fc($this->b)->sql($options));
			
		}*/
	}

	class FilterField extends Filter
	{
		public $field = null;
		
		public function prepare() {}

		public function fid()
		{
			$temp = strpos($this->a, '.');
			return ($temp ? substr($this->a, $temp + 1) : $this->a);
		}

		public function oid()
		{
			$temp = strpos($this->a, '.');
			return ($temp ? substr($this->a, 0, $temp) : null);
		}

		public function sql($options)
		{
			extract($options, EXTR_SKIP);
			if (strpos($this->a, '.') !== false)
			{
				if ($this->b)
				{
					list($x, $y) = explode('.', $this->a);
					return qi($x) . '.' . qi($y);
				}
				else
				{
					if (!$field = @Field::$_[$this->a])
						return '[error field '.$this->a.']';
					return $field->sqlName($object);
				}
			}
			else
				return $object->f($this->a)->sqlName();
		}

		public function validate($options)
		{
			extract($options, EXTR_SKIP);
			return $object->{$this->a};
		}
	}

	class FilterConstant extends Filter
	{
		public $field = null;
		
		public function prepare() {}

		public function quote($value)
		{
			if ($this->field && ($this->field instanceof Field))
				return qq($this->field->$this->convert($value, 'sql'));
			return qq($value);
		}
		
		public function sql($options)
		{
			extract($options, EXTR_SKIP);
			if (is_array($this->a))
			{
				$result = array();
				foreach ($this->a as $temp)
					$result[] = $this->quote($temp);
				return implode(', ', $result);
			}
			else
				return $this->quote($this->a);
		}

		public function validate($options)
		{
			extract($options, EXTR_SKIP);
			return $this->a;
		}
	}

	class FilterNot extends Filter
	{
		public function prepare()
		{
			$this->a->prepare();
		}

		public function all()
		{
			return array_merge(array($this), $this->a->all());
		}

		public function sql($options)
		{
			if (!$this->a)
				return null;
			return '(NOT ' . $this->a->sql($options) . ')';
		}

		public function validate($options)
		{
			return !$this->a->validate($options);
		}
	}

	class FilterAnd extends Filter
	{
		public function prepare()
		{
			if (!is_array($this->a))
				fbo($this->a);
			foreach ($this->a as $x)
				if ($x !== null)
					$x->prepare();
		}

		public function all()
		{
			$result = array($this);
			foreach ($this->a as $x)
				if ($x !== null)
					$result = array_merge($result, $x->all());
			return $result;
		}

		public function sql($options)
		{
			$result = array();
			if (!is_array($this->a))
				fbo($this->a);
			foreach ($this->a as $x)
				if (($x !== null) && ($temp = $x->sql($options)))
					$result[] = '(' . $temp . ')';
			return implode(' AND ', $result);
		}

		public function validate($options)
		{
			$result = true;
			foreach ($this->a as $x)
				if (($x !== null) && (($temp = $x->validate($options)) !== null))
					$result = $result && $temp;
			return $result;
		}
	}

	class FilterOr extends Filter
	{
		public function prepare()
		{
			if (!is_array($this->a))
				fbo($this->a);
			foreach ($this->a as $x)
				if ($x !== null)
					$x->prepare();
		}

		public function all()
		{
			$result = array($this);
			if (!is_array($this->a))
				fbo($this->a);
			foreach ($this->a as $x)
				if ($x !== null)
					$result = array_merge($result, $x->all());
			return $result;
		}

		public function sql($options)
		{
			$result = array();
			foreach ($this->a as $x)
				if (($x !== null) && ($temp = $x->sql($options)))
					$result[] = '(' . $temp . ')';
			return implode(' OR ', $result);
		}

		public function validate($options)
		{
			$result = false;
			foreach ($this->a as $x)
				if (($x !== null) && (($temp = $x->validate($options)) !== null))
					$result = $result || $temp;
			return $result;
		}
	}

	class FilterEquals extends Filter
	{
		public function sql($options)
		{
			$a = (($this->a instanceof Filter) ? $this->a->sql($options) : Ff($this->a)->sql($options));
			$b = (($this->b instanceof Filter) ? $this->b->sql($options) : Fc($this->b)->sql($options));
			extract($options, EXTR_SKIP);

			return $a . (($b === null) ? ' IS NULL' : (' = ' . $b));
		}

		public function validate($options)
		{
			$a = (($this->a instanceof Filter) ? $this->a->validate($options) : Ff($this->a)->validate($options));
			$b = (($this->b instanceof Filter) ? $this->b->validate($options) : Fc($this->b)->validate($options));
			extract($options, EXTR_SKIP);

			return ($a == $b);
		}
	}

	class FilterFullText extends Filter
	{
		public function sql($options)
		{
			$a = (($this->a instanceof Filter) ? $this->a->sql($options) : Ff($this->a)->sql($options));
			$b = (($this->b instanceof Filter) ? $this->b->sql($options) : Fc($this->b)->sql($options));
			extract($options, EXTR_SKIP);

			return 'MATCH (' . $a . ') AGAINST (' . $b . ' IN BOOLEAN MODE)';
		}
	}

	class FilterLike extends Filter
	{
		public function sql($options)
		{
			$a = (($this->a instanceof Filter) ? $this->a->sql($options) : Ff($this->a)->sql($options));
			$b = (($this->b instanceof Filter) ? $this->b->sql($options) : Fc($this->b)->sql($options));
			extract($options, EXTR_SKIP);

			return $a . ' LIKE ' . $b;
		}

		public function validate($options)
		{
			$a = (($this->a instanceof Filter) ? $this->a->validate($options) : Ff($this->a)->validate($options));
			$b = (($this->b instanceof Filter) ? $this->b->validate($options) : Fc($this->b)->validate($options));
			extract($options, EXTR_SKIP);

			$pattern = str_replace(
				array("\\", "^", "\$", ".", "[", "]", "|", "(", ")", "?", "*", "+", "{", "}", "%", "_"),
				array("\\\\", "\\^", "\\\$", "\\.", "\\[", "\\]", "\\|", "\\(", "\\)", "\\?", "\\*", "\\+", "\\{", "\\}", ".*", "."),
				$b);
			return (boolean) preg_match('~' . $pattern . '~imu', $a);
		}
	}

	class FilterRegEx extends Filter
	{
		public function sql($options)
		{
			$a = (($this->a instanceof Filter) ? $this->a->sql($options) : Ff($this->a)->sql($options));
			$b = (($this->b instanceof Filter) ? $this->b->sql($options) : Fc($this->b)->sql($options));
			extract($options, EXTR_SKIP);

			return $a . ' REGEXP ' . $b;
		}

		public function validate($options)
		{
			$a = (($this->a instanceof Filter) ? $this->a->validate($options) : Ff($this->a)->validate($options));
			$b = (($this->b instanceof Filter) ? $this->b->validate($options) : Fc($this->b)->validate($options));
			extract($options, EXTR_SKIP);

			return (boolean) preg_match('~' . $b . '~imu', $a);
		}
	}

	class FilterIn extends Filter
	{
		public function sql($options)
		{
			$a = (($this->a instanceof Filter) ? $this->a->sql($options) : Ff($this->a)->sql($options));
			$b = (($this->b instanceof Filter) ? $this->b->sql($options) : Fc($this->b)->sql($options));
			extract($options, EXTR_SKIP);

			return $a . ' IN (' . $b . ')';
		}

		public function validate($options)
		{
			$a = (($this->a instanceof Filter) ? $this->a->validate($options) : Ff($this->a)->validate($options));
			$b = (($this->b instanceof Filter) ? $this->b->validate($options) : Fc($this->b)->validate($options));
			extract($options, EXTR_SKIP);

			foreach ($b as $temp)
				if ($a == $temp)
					return true;
			return false;
		}
	}

	class FilterOperator extends Filter
	{
		public					$operator			= null;

		public function __construct($a, $b, $operator)
		{
			parent::__construct($a, $b);
			$this->operator = $operator;
		}

		public function sql($options)
		{
			$a = (($this->a instanceof Filter) ? $this->a->sql($options) : Ff($this->a)->sql($options));
			$b = (($this->b instanceof Filter) ? $this->b->sql($options) : Fc($this->b)->sql($options));
			extract($options, EXTR_SKIP);

			$op = '[error]';
			switch ($this->operator)
			{
				case '==':
					$op = '=';
					break;
				case '!=':
				case '>':
				case '>=':
				case '<':
				case '<=':
					$op = $this->operator;
					break;
			}

			return $a . ' ' . $op . ' ' . $b;
		}

		public function validate($options)
		{
			$a = (($this->a instanceof Filter) ? $this->a->validate($options) : Ff($this->a)->validate($options));
			$b = (($this->b instanceof Filter) ? $this->b->validate($options) : Fc($this->b)->validate($options));
			extract($options, EXTR_SKIP);

			switch ($this->operator)
			{
				case '==':
					$result = ($a == $b);
					break;
				case '!=':
					$result = ($a != $b);
					break;
				case '>':
					$result = ($a > $b);
					break;
				case '>=':
					$result = ($a >= $b);
					break;
				case '<':
					$result = ($a < $b);
					break;
				case '<=':
					$result = ($a <= $b);
					break;
			}
			return $result;
		}
	}

	class FilterSql extends Filter
	{
		public function sql($options)
		{
			extract($options, EXTR_SKIP);
			$rval = $this->b;
			$lval = $this->a->sql($options);
			return "(" . str_replace("~lval~", $lval, $rval) . ")";
			return $this->a;
		}
	}

	class Ff	extends FilterField		{}
	class Fc	extends FilterConstant	{}
	class Fand	extends FilterAnd		{}
	class Fo	extends FilterOr		{}
	class Fnot	extends FilterNot		{}
	class Feq	extends FilterEquals	{}
	class Flike	extends FilterLike		{}
	class Fft	extends FilterFullText	{}
	class Fre	extends FilterRegEx		{}
	class Fin	extends FilterIn		{}
	class Fop	extends FilterOperator	{}
	class Fsql	extends FilterSql		{}

	function Ff($a)				{ return new Ff($a); }
	function Fc($a)				{ return new Fc($a); }
	function Fand()				{ return new Fand(func_get_args()); }
	function Fo()				{ return new Fo(func_get_args()); }
	function Fnot($a)			{ return new Fnot($a); }
	function Feq($a, $b)		{ return new Feq($a, $b); }
	function Flike($a, $b)		{ return new Flike($a, $b); }
	function Fft($a, $b)		{ return new Fft($a, $b); }
	function Fre($a, $b)		{ return new Fre($a, $b); }
	function Fin($a, $b)		{ return new Fin($a, $b); }
	function Fop($a, $b, $op)	{ return new Fop($a, $b, $op); }
	function Fsql($a)			{ return new Fsql($a); }
?>