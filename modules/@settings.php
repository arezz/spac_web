<?php
	class o_settings extends object
	{
		public function initialize($phase)
		{
			parent::initialize($phase);
			
			if ($phase == 0)
			{
				$this->f('otitle')->get = 'return "";';

				$f = $this->define('value', Field::TYPE_NULL, null, null);
			}
		}
	}

	class settings extends Module
	{
		public					$object								= null;

		public function initialize($phase)
		{
			parent::initialize($phase);

			if ($phase == 0)
			{
				$this->object = object::instantiate('settings', 'o_settings', array('module' => $this));
	
				$this->action('edit', true);
				$this->action('edit')->optionsUnsafe[] = 'id';
				$this->action('clear-cache', true);
			}
		}

		public function generateAdministrationMenu($type = null)
		{
			AdministrationMenu::addItem(new Invocation('edit', $this->id), $this->id);
		}

		/************************************************** ACTIONS **************************************************/

		public function save()
		{
			$locale_postfixes = array();
			foreach ($GLOBALS['site']['locales'] as $locale)
				$locale_postfixes[] = '_' . $locale;

			$output = '<?php'."\n";
			foreach (Setting::$_ as $setting)
			{
				$postfixes = ($setting->field->get('localized') ? $locale_postfixes : array(''));
				foreach ($postfixes as $postfix)
				{
					$value = $setting->get($postfix);
					if ($value !== $setting->field->default)
					{
						$v = var_export($value, true);
						$output .= '@$GLOBALS[\'settings\'][\'' . $setting->module . '\'][\'' . $setting->field->id . $postfix . '\'] = ' . $v . ';'. "\n";
					}
				}
				if ($setting->field->get('localized'))
				{
					$output .= 'if (isset($GLOBALS[\'settings\'][\'' . $setting->module . '\'][\'' . $setting->field->id . '_\'.$GLOBALS[\'page\'][\'locale\']]))' . "\n";
					$output .= "\t" . '@$GLOBALS[\'settings\'][\'' . $setting->module . '\'][\'' . $setting->field->id . '\'] = $GLOBALS[\'settings\'][\'' . $setting->module . '\'][\'' . $setting->field->id . '_\'.$GLOBALS[\'page\'][\'locale\']];'. "\n";
				}
			}
			$output .= '?>';
			file_put_contents($GLOBALS['site']['data'] . 'settings.php', $output);
			chmod($GLOBALS['site']['data'] . 'settings.php', 0666);
		}

		public function actionEdit($invocation, $action)
		{
			$result = null;
			$id = $invocation->get('id');
			$setting = (isset(Setting::$_[$id]) ? Setting::$_[$id] : null);

			if ($setting)
			{
				$back = new Invocation($action->id, $this->id, __('back'));
				$back->icon = 'x';

				$form = $invocation->form();
				$form->i('_main')->add('hidden', 'id', $id);
				$form->autoHeader(null, ' : ' . $setting->name());
				$this->object->fields()->value = $field = clone($setting->field);
				$field->id = 'value';
				if (!$field->get('label'))
					$field->set('label', $setting->name());
				if ($field->get('localized'))
				{
					$field->set('localized', null);
					$this->object->localize('value');
				}
				foreach (clone($this->object->fields()) as $field)
					if (substr($field->id, 0, 5) == 'value')
						$field->set($this->object, $setting->get(substr($field->id, 5)));
				$this->object->form(0, $form->i('_main'), null, $action);
				$form->autoSubmit();
				if ($form->validate())
				{
					$this->object->form(1, $form->i('_main'), null, $action);
					foreach ($this->object->fields() as $field)
						if (substr($field->id, 0, 5) == 'value')
							$setting->set($field->get($this->object), substr($field->id, 5));
					$this->save();
					$invocation->message(__('saved') . ': ' . HTML::e($setting->name()));
					$back->forward();
				}

				$invocation->actionsTop(array($back));
				$invocation->output($form);
			}
			else
			{
				$ref = new Invocation('clear-cache', $this->id);
				$invocation->actionsTop(array($ref));

				$ref = new Invocation($action->id, $this->id);
				$ref->icon = 'x';
				$invocation->output('<br /><table>');
				foreach (Setting::$_ as $setting)
					if (USER == 'administrator')
						$invocation->output('<tr><td>' . $ref->url(array('id' => $setting->id()), $setting->id()) . '</td><td>' . $setting->name() . '</td></tr>');
					else
						$invocation->output('<tr><td>' . $ref->url(array('id' => $setting->id()), $setting->nameShort()) . '</td></tr>');
				$invocation->output('</table>');

			}

			return $result;
		}

		public function actionClearCache($invocation, $action)
		{
			$ref = new Invocation('edit', $this->id, __('back'));
			$ref->icon = 'x';
			$invocation->actionsTop(array($ref));

			$files = glob($GLOBALS['site']['data'] . 'cache/*');
			foreach ($files as $file)
			{
				unlink($file);
				$invocation->output($file . '<br />');
			}

			$invocation->message(__($action->id.'-done'));
		}
	}
?>