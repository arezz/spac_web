<?php
	class o_attribute_types extends object
	{
		public function initialize($phase)
		{
			parent::initialize($phase);
			
			if ($phase == 0)
			{
				$this->set('tree', true);
				$this->set('group-filter', Fo(Feq('group', true), Feq('type', -1)));
				$this->set('order', array('+group', '+priority', '+title'));
				$this->f('g')->get = 'return (!$object->oid || $object->group || ($object->type == -1));';
				$this->f('otitle')->get = 'return ($object->oid ? $object->key . " (" . $object->title . ")" : "");';
	
				$f = $this->define('pid', Field::TYPE_STRING, 64, '', array('fixed' => true, 'forms' => array()));
				$f = $this->defineGroup();
				$f = $this->definePriority();
				$f = $this->defineString('key');
				$f->ruleID();
				$f->ruleRequired();
				$f = $this->define('type', Field::TYPE_INTEGER, null, Field::TYPE_NULL, array(
					'select' => true,
					'values' => array(
						Field::TYPE_NULL => __('type-null'),
						-1 => __('type-select'),
						Field::TYPE_BOOLEAN => __('type-boolean'),
						Field::TYPE_INTEGER => __('type-integer'),
						Field::TYPE_FLOAT => __('type-float'),
						Field::TYPE_DATETIME => __('type-datetime'),
						Field::TYPE_STRING => __('type-string'),
						Field::TYPE_FILE => __('type-file'),
					),
				));
				$f = $this->define('variant', Field::TYPE_STRING, 64, '', array(
					'select' => true,
					'values' => array(
						'' => '',
						'locale' => __('variant-locale'),
					),
				));
				$f = $this->defineStringLocalized('title');
				$f = $this->defineStringLocalized('format', 255, '%s');
				$f = $this->defineTextLocalizedHTML('text');
	
				$f = $this->define('data', Field::TYPE_OBJECT, 65535, array(), array(
					'editable' => true,
				));
			}
		}

		public function format($value)
		{
			if (!$this->format)
				return $value;
			else
				return sprintf($this->format, $value);
		}

		public function currentVariant()
		{
			if ($this->variant == 'locale')
				return $GLOBALS['page']['locale'];
			return null;
		}
	}

	class attribute_types extends ObjectModule
	{
		public function initialize($phase)
		{
			parent::initialize($phase);

			if ($phase == 4)
			{
				$this->action('list')->set('order-fields', array('group', 'priority', 'key', 'type', 'title'));
				$this->action('list')->set('columns', array('group', 'priority', 'key', 'type', 'title'));
				$this->action('list')->set('actions', array('new', 'edit', 'delete'));
			}
		}

		public function select_key($value = null)
		{
			$result = Cache::get($this->id, __FUNCTION__);
			if ($result === null)
			{
				$result = array();
				$rows = qa("SELECT `key`, `title_#L#` FROM `##{$this->id}` WHERE `group` ORDER BY `priority`, `title_#L#`");
				if (is_array($rows) && count($rows))
					foreach ($rows as $row)
						$result[$row['key']] = $row['title_'.$GLOBALS['page']['locale']] . ' (' . $row['key'] . ')';
				Cache::set($result, $this->id, __FUNCTION__);
			}
			return parent::select($value, $result);
		}

		/************************************************** ACTIONS **************************************************/
	}
?>