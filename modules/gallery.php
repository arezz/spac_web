<?php
	class o_gallery extends object
	{
		public function initialize($phase)
		{
			parent::initialize($phase);
			
			if ($phase == 0)
			{
				$this->set('tree', true);
				$this->set('group-filter', Feq('group', true));
				$this->set('order', array('+priority', '+title'));
				$this->f('otitle')->get = 'return ($object->oid ? $object->title : "");';
	
				$this->definePid();
				$this->defineIdAutoIncrement();
				$this->defineOCreated();
				$this->defineGroup();
				$this->definePriority();
				$this->defineImage();
				$this->defineStringLocalized('title');
				$this->defineTextLocalizedHTML('text');
	
				$f = $this->defineV('url', Field::TYPE_STRING);
				$f->get = 'return (!$object->id ? "" : (U::urlize($object->title) . "-" . $object->id));';
			}
		}
	}

	class gallery extends ObjectModule
	{
		public function initialize($phase)
		{
			parent::initialize($phase);

			if ($phase == 4)
			{
				$this->action('list')->set('columns', array('priority', 'title', 'image'));
				$this->action('list')->set('actions', array('new', 'edit', 'delete'));
				$this->action('get')->set('object-fields', array('id'));
			}
		}

		public function index($options = array())
		{
			Router::add(Router::TYPE_EQUALS, __('get-url'), '', array('page.location' => $this->id.'/get'));
			Router::add(Router::TYPE_REGEX, __('get-url').'(.*)-(\d+)', '', array('page.location' => $this->id.'/get'), array('title', 'id'));
			Router::process();
			if (Core::location("{$this->id}/get"))
				$this->mainTemplate();
		}

		public function page($options = array())
		{
			if (Core::location("~^{$this->id}/~"))
			{
				$GLOBALS['page']['path'][] = array(
					'text' => __('module-name', $this->id),
					'url' => $this->url('get'),
				);
				if ($GLOBALS['invocation']->get('id'))
					$GLOBALS['page']['path'][] = array(
						'text' => ($GLOBALS['result']['object'] ? $GLOBALS['result']['object']->title : __('not-found', '@core')),
						'url' => false,
					);
				return true;
			}
		}

		/************************************************** ACTIONS **************************************************/
	}
?>