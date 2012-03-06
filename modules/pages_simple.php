<?php
	class pages_simple extends Module
	{
		public					$meta								= array('title', 'keywords', 'description');

		public function initialize($phase)
		{
			parent::initialize($phase);

			if ($phase == 0)
			{
				foreach ($GLOBALS['site']['locales'] as $locale)
					Locale::module($this->id, $locale);
	
				$this->action('edit', true);
				$this->action('edit')->optionsUnsafe[] = 'locale';
			}
		}
		
		public function prepare($invocation, $action, $phase, $result)
		{
			$result = parent::prepare($invocation, $action, $phase, $result);

			if ($phase == 7)
			{
				if (isRole('administrator') || isRole($this->id))
					$result = true;
			}

			return $result;
		}

		public function generateAdministrationMenu()
		{
			AdministrationMenu::addItem(new Invocation('edit', $this->id), $this->id);
		}

		public function index($options = array())
		{
			foreach ($this->get('pages', array()) as $page)
				if (($url = __($page.'-url', null, false)) !== false)
					Router::add(Router::TYPE_EQUALS, __('get-url', null, '') . $url, '', array('page.location' => $this->id.'/get', 'page.pages_simple-id' => $page));
			Router::process();

			if (Core::location("{$this->id}/get"))
				$this->mainTemplate();
		}

		public function page($options = array())
		{
			if (Core::location("{$this->id}/get"))
			{
				$id = Core::getG('page.pages_simple-id');
				$data = $this->load($id);
				Core::set('pages_simple-data', $data);
				if ($data)
				{
					foreach ($this->meta as $k)
						if (!$GLOBALS['page'][$k])
							$GLOBALS['page'][$k] = $data[$k];
					$GLOBALS['page']['path'][] = array(
						'text' => $data['title'],
						'url' => false,
					);
				}
				else
					$GLOBALS['page']['path'][] = array(
						'text' => __('not-found', '@core'),
						'url' => false,
					);

				return true;
			}
		}

		public function file_name($id, $locale, $ext = '.html')
		{
			return $GLOBALS['site']['data'] . 'modules/' . $this->id . '-' . $locale . '-' . $id . $ext;
		}

		public function load($id, $locale = null)
		{
			if (!$locale)
				$locale = $GLOBALS['page']['locale'];
			$data = array();
			$data['text'] = (string) @file_get_contents($this->file_name($id, $locale));
			$meta = (string) @file_get_contents($this->file_name($id, $locale, '.meta'));
			foreach ($this->meta as $temp)
				$data[$temp] = (string) @Locale::$container[$locale][$this->id][$id.'-'.$temp];
			$meta = U::metaStringToArray($meta, $this->meta, true);
			foreach ($this->meta as $k)
				if ($meta[$k])
					$data[$k] = $meta[$k];
			return $data;
		}

		public function save($id, $locale, $data)
		{
			file_put_contents($f = $this->file_name($id, $locale), @$data['text']);
			chmod($f, 0666);
			$meta = U::metaArrayToString($data, $this->meta, true);
			file_put_contents($f = $this->file_name($id, $locale, '.meta'), $meta);
			chmod($f, 0666);
		}

		/************************************************** ACTIONS **************************************************/

		public function actionEdit($invocation, $action)
		{
			$locale = $invocation->get('locale');
			$id = $invocation->get('id');
			$result = null;
			if ($id)
			{
				$back = new Invocation($action->id, $this->id);
				$back->text = __('back', '@core');
				$back->icon = 'x';

				$data = $this->load($id, $locale);
				$form = $invocation->form();
				$form->autoHeader($data['title'] . ' (' . $id . ')');
				$e = $form->i('_hidden')->add('hidden', 'id', $id);
				$e->default = $id;
				$e = $form->i('_hidden')->add('hidden', 'locale', $locale);
				$e->default = $locale;
				if ($this->get('meta-edit'))
					foreach ($this->meta as $k)
					{
						$e = $form->i('_main')->add('text', $k, __($k));
						$e->default = $data[$k];
					}
				$e = $form->i('_main')->add('textarea', 'text', __('text'), 'html');
				$e->default = $data['text'];
				$e = $form->autoSubmit();
				if ($form->validate())
				{
					if ($this->get('meta-edit'))
					{
						foreach ($this->meta as $k => $v)
							$data[$k] = $v;
					}
					$data['text'] = $form->values['text'];
					$this->save($id, $locale, $data);
					$invocation->message(__('saved') . ': ' . HTML::e(@$data['title']));
					$back->forward();
				}

				$invocation->actionsTop(array($back));
				$invocation->output($form);
			}
			else
			{
				$invocation->output("<ul>\n");
				foreach ($this->get('locales', $GLOBALS['site']['locales']) as $locale)
				{
					$invocation->output("<li>" . __('locale-' . $locale) . "<ul>\n");
					foreach ($this->get('pages', array()) as $id)
					{
						$data = $this->load($id, $locale);
						$invocation->output('<li>');
						$ref = new Invocation($action->id, $this->id);
						$ref->icon = 'x';
						$ref->text = $data['title'] . ' (' . $id . ')';
						$invocation->output($ref->url(array('locale' => $locale, 'id' => $id), null, 7));
						$invocation->output('</li>');
					}
					$invocation->output("</ul></li>\n");
				}
				$invocation->output("</ul>\n");
			}

			return $result;
		}
	}
?>