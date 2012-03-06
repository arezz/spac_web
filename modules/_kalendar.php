<?php
	class o__kalendar extends object
	{
		public function initialize($phase)
		{
			parent::initialize($phase);

			if ($phase == 0)
			{
				$this->set('order', array('datum'));
				$this->f('otitle')->get = 'return ($object->oid ? $object->nazev : "");';
	
				$f = $this->defineIdAutoIncrement();
				$f = $this->defineOCreated();
				$f = $this->define('datum', Field::TYPE_DATE, null, time());
				$f = $this->defineString('nazev');
				$f = $this->defineString('typ');
				$f = $this->defineString('poradatel_jmeno', 64);
				$f = $this->defineString('poradatel_telefon', 16);
				$f = $this->defineString('poradatel_email', 100);
				$f = $this->defineString('poradatel_web', 100);
				$f = $this->defineString('poradatel_text');
				$f = $this->define('propozice', Field::TYPE_FILE, null, '');
				$f = $this->define('vysledky', Field::TYPE_FILE, null, '');
				$f = $this->defineTextHTML('text');
	
				$f = $this->defineV('url', Field::TYPE_STRING);
				$f->get = 'return (!$object->oid ? "" : (U::urlize($object->nazev) . "-" . $object->id));';
			}
		}
	}
	
	class _kalendar extends ObjectModule
	{
		public function initialize($phase)
		{
			parent::initialize($phase);

			if ($phase == 4)
			{
				$this->action('list')->set('columns', array('datum', 'nazev', 'poradatel_jmeno'));
				$this->action('list')->set('actions', array('new', 'edit', 'delete'));

				$this->action('get')->set('object-fields', array('id'));
			}
		}

		public function index($options = array())
		{
			Router::add(Router::TYPE_EQUALS, __('get-url'), '', array('page.location' => $this->id.'/get'));
			Router::add(Router::TYPE_REGEX, __('get-url').'([^/]*)-(\d+)', '', array('page.location' => $this->id.'/get'), array('title', 'id'));
			Router::process();
			if (Core::location("{$this->id}/get"))
				$this->mainTemplate();
		}

		public function page($options = array())
		{
			if (Core::location("{$this->id}/get"))
			{
				$GLOBALS['page']['path'][] = array(
					'text' => __('module-name', $this->id),
					'url' => $this->url('get'),
				);
				if ($GLOBALS['invocation']->get('id'))
					$GLOBALS['page']['path'][] = array(
						'text' => ($GLOBALS['result']['object'] ? $GLOBALS['result']['object']->nazev : __('not-found', '@core')),
						'url' => false,
					);
				return true;
			}
		}

		public function url($action, $options = array(), $type = null)
		{
			if (($type == 'pager') && ($action == 'get'))
				return parent::url('get');
			else
				return parent::url($action, $options, $type);
		}

		public function select_id($value = null)
		{
			$result = Cache::get($this->id, __FUNCTION__);
			if ($result === null)
			{
				$result = array('' => '');
				$rows = qa("SELECT `id`, `nazev_#L#` FROM `##{$this->id}` ORDER BY `nazev_#L#`");
				if (is_array($rows) && count($rows))
					foreach ($rows as $row)
						$result[$row['id']] = $row['nazev'.LL];
				Cache::set($result, $this->id, __FUNCTION__);
			}
			return parent::select($value, $result);
		}

		public function select_oid($value = null)
		{
			$result = Cache::get($this->id, __FUNCTION__);
			if ($result === null)
			{
				$result = array('' => '');
				$rows = qa("SELECT `oid`, `nazev_#L#` FROM `##{$this->id}` ORDER BY `nazev_#L#`");
				if (is_array($rows) && count($rows))
					foreach ($rows as $row)
						$result[$row['oid']] = $row['nazev'.LL];
				Cache::set($result, $this->id, __FUNCTION__);
			}
			return parent::select($value, $result);
		}

		/************************************************** ACTIONS **************************************************/

	}
?>