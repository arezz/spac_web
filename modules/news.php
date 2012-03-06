<?php
	class o_news extends object
	{
		public function initialize($phase)
		{
			parent::initialize($phase);
			
			if ($phase == 0)
			{
				$this->set('order', array('-ocreated'));
				$this->f('otitle')->get = 'return ($object->oid ? $object->title : "");';
	
				$f = $this->defineIdAutoIncrement();
				$f = $this->defineOCreated();
				$f = $this->defineImage();
				$f = $this->defineStringLocalized('title');
				$f = $this->defineTextLocalized('summary');
				$f = $this->defineTextLocalizedHTML('text');
				$f = $this->define('attachment', Field::TYPE_FILE, 255, '');
	
				$f = $this->defineV('url', Field::TYPE_STRING);
				$f->get = 'return (!$object->oid ? "" : (U::urlize($object->title) . "-" . $object->id));';
			}
		}
	}
	
	class news extends ObjectModule
	{
		public function initialize($phase)
		{
			parent::initialize($phase);

			if ($phase == 4)
			{
				$this->action('test-data')->set('present', true);

				$this->action('list')->set('columns', array('image', 'ocreated', 'title'));
				$this->action('list')->set('actions', array('new', 'edit', 'delete'));

				$this->action('get')->set('object-fields', array('id'));
				$this->action('get')->set('page-size', 12);
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
						'text' => ($GLOBALS['result']['object'] ? $GLOBALS['result']['object']->title : __('not-found', '@core')),
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
				$rows = qa("SELECT `id`, `title_#L#` FROM `##{$this->id}` ORDER BY `title_#L#`");
				if (is_array($rows) && count($rows))
					foreach ($rows as $row)
						$result[$row['id']] = $row['title'.LL];
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
				$rows = qa("SELECT `oid`, `title_#L#` FROM `##{$this->id}` ORDER BY `title_#L#`");
				if (is_array($rows) && count($rows))
					foreach ($rows as $row)
						$result[$row['oid']] = $row['title'.LL];
				Cache::set($result, $this->id, __FUNCTION__);
			}
			return parent::select($value, $result);
		}

		/************************************************** ACTIONS **************************************************/

		public function actionTestData($invocation, $action)
		{
			$object = clone($this->object);
			$object->image = $object->f('image')->blob(
				$this->actionTestDataFetch($invocation, $action, 'image/jpeg')->result,
				'image/jpeg',
				$this->object->i . '-'
			);
			$object->title_all_locales = $this->actionTestDataFetch($invocation, $action, 'text/plain', array('size', '<', 100))->result;
			$object->summary_all_locales = $this->actionTestDataFetch($invocation, $action, 'text/plain', array('size', '<', 400, 'size', '>', 100))->result;
			$object->text_all_locales = $this->actionTestDataFetch($invocation, $action, 'text/html')->result;
			$object->save();

			$back = new Invocation('list', $this->id);
			$back->forward();
		}
	}
?>