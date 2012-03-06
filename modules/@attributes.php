<?php
	class o_attributes extends object
	{
		public function initialize($phase)
		{
			parent::initialize($phase);
			
			if ($phase == 0)
			{
				$this->set('order', array('+object', '+key'));
				$this->f('otitle')->get = 'return ($object->oid ? $object->object : "");';
	
				$f = $this->define('object', Field::TYPE_STRING, 64, '');
				$f = $this->define('key', Field::TYPE_STRING, 64, '');
				$f = $this->define('variant', Field::TYPE_STRING, 64, '');
				$f = $this->define(clone(o('attribute_types')->f('type')));
	
				$f = $this->define('value_integer', Field::TYPE_INTEGER, null, 0); // integer, boolean
				$f = $this->define('value_float', Field::TYPE_FLOAT, null, 0.0); // float
				$f = $this->define('value_string', Field::TYPE_STRING, 255, ''); // string, file
				$f = $this->define('value_datetime', Field::TYPE_DATETIME, null, time()); // datetime
			}
		}

		public function whichValue()
		{
			switch ($this->type)
			{
				case Field::TYPE_BOOLEAN:
				case Field::TYPE_INTEGER:
					return 'value_integer';
				case Field::TYPE_FLOAT:
					return 'value_float';
				case Field::TYPE_DATETIME:
					return 'value_datetime';
				case Field::TYPE_STRING:
				case Field::TYPE_FILE:
					return 'value_string';
				default:
					return null;
			}
		}

		public function getValue()
		{
			if ($fid = $this->whichValue())
				return $this->$fid;
		}

		public function setValue($value)
		{
			if ($fid = $this->whichValue())
				$this->$fid = $value;
		}
	}

	class attributes extends ObjectModule
	{
		public function initialize($phase)
		{
			parent::initialize($phase);

			if ($phase == 4)
			{
				$this->action('list')->set('order-fields', array('module', 'object', 'key'));
				$this->action('list')->set('columns', array('module', 'object', 'key'));
				$this->action('list')->set('actions', array('new', 'edit', 'delete'));
			}
		}

		public function prepare($invocation, $action, $phase, $result)
		{
			$result = parent::prepare($invocation, $action, $phase, $result);
			if ($phase == 7)
			{
				$result = false;
			}
			return $result;
		}
		
		public function generateAdministrationMenu()
		{
		}

		public function getAttribute($field, $object, $id, $variant)
		{
			$atttype = o('attribute_types')->load(Feq('oid', $id), true);
			if (!$atttype)
				return $field->default;
			$attribute = $this->object->load(Fand(Feq('object', $object->id), Feq('key', $atttype->key), Feq('variant', $variant)), true);
			if (!$attribute)
				return $field->default;
			return $attribute->getValue();
		}

		public function setAttribute($field, $object, $id, $variant, $value)
		{
			$atttype = o('attribute_types')->load(Feq('oid', $id), true);
			if (!$atttype)
				return;
			$attribute = $this->object->load(Fand(Feq('object', $object->id), Feq('key', $atttype->key), Feq('variant', $variant)), true);
			if (!$attribute)
			{
				$attribute = clone($this->object);
				$attribute->object = $object->id;
				$attribute->key = $atttype->key;
				$attribute->variant = $variant;
				$attribute->type = $atttype->type;
			}
			$attribute->setValue($value);
			$attribute->save();
		}

		/************************************************** ACTIONS **************************************************/
	}
?>