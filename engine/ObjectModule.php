<?php
	// Author: Jakub Macek, CZ; Copyright: Poski.com s.r.o.; Code is 100% my work. Do not copy.

	class ListColumn
	{
		public					$field								= null;
		public					$text								= null;
		public					$modifiers							= null;
		public					$invocations						= array();
		public					$e									= null;

		public function __construct($field, $text, $modifiers = '')
		{
			$this->field = $field;
			$this->text = $text;
			$this->modifiers = $modifiers;
		}

		public function value($object)
		{
			$result = $object->{$this->field};
			if ($this->e)
				return $this->e->evaluate(array('column' => $this, 'value' => $result));
			if ($field = $object->f($this->field))
			{
				if (($field->type == Field::TYPE_DATETIME) || ($field->type == Field::TYPE_DATE))
				{
					$default_format = 'Y-m-d H:i:s';
					if ($field->type == Field::TYPE_DATE)
						$default_format = 'Y-m-d';
					if (USER == 'administrator')
						return date($field->get('format-view', $field->get('format', $default_format)), $field->get($object));
					else
						return strftime(__('datetime-format-' . $field->get('format-view', $field->get('format', $default_format))), $field->get($object));
				}
				if ($field->id == 'blobsize')
					return round($field->get($object) / 1024, 1) . ' kB';
				if ($field->type == Field::TYPE_BOOLEAN)
				{
					$result = ($field->get($object) ? 'true' : 'false');
					return '<img src="' . $GLOBALS['site']['url'] . 'web/_administration/icons/' . $result . '.png" alt="' . __($result, '@core') . '" title="' . __($result, '@core') . '">';
				}
				if ($field->type == Field::TYPE_FILE)
				{
					$value = $field->get($object);
					if (!$value)
						return 'n/a';
					$src = $GLOBALS['site']['url'] . 'data/image/' . $value . '/';
					$image_variants = $field->get('image-variants', array());
					if (count($image_variants) > 0)
						$src .= $field->id . ',' . $image_variants[0][1];
					return '<img src="' . $src . '" alt="">';
				}
				if ($field->get('files'))
				{
					$value = $field->get($object);
					if (isset($value[0]))
						$value = $value[0];
					else if (count($value))
						$value = array_shift($value);
					if (!$value)
						return 'n/a';
					$src = $GLOBALS['site']['url'] . 'data/image/' . $value . '/';
					$image_variants = $field->get('image-variants', array());
					if (count($image_variants) > 0)
						$src .= $field->id . ',' . $image_variants[0][1];
					return '<img src="' . $src . '" alt="">';
				}
				if ($field->get('select'))
					$result = $field->select($field->get($object));
			}
			if (strpos($this->modifiers, '=') === false)
				$result = HTML::e($result);
			foreach ($this->invocations as $invocation)
			{
				$invocation->set('column', $this);
				$invocation->set('object', $object);
				$result .= ' ' . $invocation->url(null, null, 7);
			}
			return $result;
		}
	}

	class ObjectModule extends Module
	{
		public					$object								= null;

		public function initialize($phase)
		{
			if ($phase == 0)
			{
				$object_class = $this->get('object');
				if ($object_class && !class_exists($object_class))
					die("object class '$object_class' is missing");
				if (!$object_class)
					$object_class = 'ox_'.$this->id;
				if (!class_exists($object_class))
					$object_class = 'ox_' . get_class($this);
				if (!class_exists($object_class))
					$object_class = 'o_'.$this->id;
				if (!class_exists($object_class))
					$object_class = 'o_' . get_class($this);
				if (!class_exists($object_class))
					die("object class for module '$this->id' is missing");
				$this->object = object::instantiate($this->id, $object_class, array('module' => $this));
				
				if ($this->get('create-default-actions', true))
				{
					$this->action('new', true);
					$this->action('edit', true);
					$this->action('edit')->context = 'object';
					$this->action('delete', true);
					$this->action('delete')->context = 'object';
					$this->action('view', true);
					$this->action('view')->context = 'object';
					$this->action('list', true);
					$this->action('list')->optionsUnsafe[] = 'page';
					$this->action('list')->optionsUnsafe[] = 'order';
					/*$this->action('grab', true);
					$this->action('grab')->context = 'object';
					$this->action('drop', true);
					$this->action('drop')->optionsUnsafe[] = 'selected';
					$this->action('drop')->optionsUnsafe[] = 'cancel';
					$this->action('drop')->mappings['selected'] = new E('return ((isset($object) && $object->oid) ? $object->oid : $invocation->get("selected"));');*/
	
					//if ($this->object->f('oacl')->size !== false)
					{
						$this->action('acl', true);
						$this->action('acl')->context = 'object';
					}
	
					$this->action('get', true);
					$this->action('get')->optionsUnsafe[] = 'page';
				}
			}
			
			parent::initialize($phase);
			
			if ($phase == 7)
			{
				if ($GLOBALS['site']['development'] && !$this->object->exists())
					$this->object->create();
			}
		}

		public function prepare($invocation, $action, $phase, $result)
		{
			$result = parent::prepare($invocation, $action, $phase, $result);

			if ($phase == 2)
			{
				$this->preload($invocation, $action);
			}

			if ($phase == 7)
			{
				if ($result === null)
				{
					switch ($action->id)
					{
						case 'get':
							$result = true;
							break;
						default:
							$allowedGroups = $invocation->get('allowed-groups', array());
							if ($allowedGroups === true)
								$result = true;
							else
							{
								$result = isAdministrator();
								if (!$result)
								{
									$groups = array_merge(array($this->id), $allowedGroups);
									
									foreach ($groups as $group)
										if (isRole($group))
											$result = true;
								}
							}

							/*foreach ($invocation->get('objects', array()) as $object)
								if ($object->oid)
									$result = $result && U::acl($invocation, $object);
							if ($object = $invocation->get('parent'))
								if ($object->oid)
									$result = $result && U::acl($invocation, $object);*/
							break;
					}
				}
			}
			
			
			return $result;
		}

		protected function preload($invocation, $action)
		{
			$objects = $invocation->get('objects', $invocation->getS('objects'));
			$object = $invocation->get('object', $invocation->getS('object'));
			$parent = $invocation->get('parent', $invocation->getS('parent'));

			if (!$object)
			{
				$fields = $invocation->get('object-fields', array('oid'));
				$filter = Fand();
				foreach ($fields as $field)
					$filter->a[] = new Feq($field, U::request($field));
				$objects = $this->object->load($filter);
				$object = $objects->first();
			}

			if (!$object)
			{
				$object = clone($this->object);
				$object->x['placeholder'] = true;
			}

			if (!$parent)
				$parent = $object->parents(true);

			if (!$parent)
			{
				//TODO parent-connection
				$pid = $invocation->get('pid', false);
				if ($this->object->get('tree') && ($pid !== false))
					$parent = $this->object->load(Feq('oid', $pid), true);
				if (!$parent)
				{
					$parent = clone($this->object);
					$parent->x['placeholder'] = true;
				}
			}

			$invocation->set('objects', $objects);
			$invocation->set('object', $object);
			$invocation->set('parent', $parent);
		}
		
		public function generateAdministrationMenu()
		{
			AdministrationMenu::addItem(new Invocation('list', $this->id), $this->id);
		}		

		/************************************************** ACTIONS **************************************************/

		protected function actionListColumns($invocation, $action, &$result)
		{
			$columns = $invocation->get('columns', array());
			$result['columns'] = array();

			if (!$columns)
				foreach ($this->object->fields() as $field)
					$columns[] = $field->id;

			foreach ($columns as $column)
			{
				if (is_string($column))
				{
					$modifiers = preg_replace('/^(\W*).*$/', '\1', $column);
					$column = preg_replace('/^(\W*)/', '', $column);
					$column = new ListColumn($column, __($column, $this->id), $modifiers);
				}

				$result['columns'][$column->field] = $column;
			}
		}

		protected function actionListOrder($invocation, $action, &$result)
		{
			$order_fields = $invocation->get('order-fields', array());
			$result['order_fields'] = array('' => '');
			foreach ($order_fields as $field)
			{
				$result['order_fields']["+$field"] = __($field) . ': ' . __('ascending', '@core');
				$result['order_fields']["-$field"] = __($field) . ': ' . __('descending', '@core');
			}

			$result['order'] = $invocation->getS('order');
			if (!$result['order'])
				$result['order'] = $invocation->get('order', $this->object->get('order', array()));

			$input = $invocation->get('order');
			if (is_string($input))
				if (isset($result['order_fields'][$input]))
					$result['order'][substr($input, 1)] = $input;
			if (is_array($input))
			{
				$result['order'] = array();
				foreach ($input as $temp)
					if ($temp && isset($result['order_fields'][$temp]))
						$result['order'][substr($temp, 1)] = $temp;
			}

			$invocation->setS('order', $result['order']);
		}

		protected function actionListFilterForm($operation, $form, $invocation = null, $fields = array(), $object = null)
		{
			$result = null;
			if ($operation == 0)
				foreach ($fields as $field)
					$field->form(2, $form->i('_main'), $object, $invocation);
			if ($operation == 1)
			{
				$form->load();
				$result = Fand();
				foreach ($fields as $field)
					if ($wc = $field->form(3, $form->i('_main'), $object, $invocation))
						$result->a[] = $wc;
			}
			return $result;
		}

		protected function actionListFilter($invocation, $action, &$result)
		{
			$result['filter'] = Fand();
			if ($temp = $invocation->get('filter'))
				$result['filter']->a[] = $temp;
			if (isset($this->object->pid) && $invocation->get('filter-use-pid', true))
			{
				$pid = $invocation->get('pid', false);
				$invocation->setS('pid', $pid);
				if ($pid !== false)
					$result['filter']->a[] = Feq('pid', $pid);
				else if ($this->object->get('tree'))
					$result['filter']->a[] = Feq('pid', $this->object->f('pid')->default);
			}

			$result['filter_form'] = null;
			$filter_fields_quick = $invocation->get('filter-fields-quick', array());
			$filter_fields = $invocation->get('filter-fields', array());
			if ($filter_fields)
			{
				$object = clone($this->object);
				$fields = new Fields();
				foreach ($filter_fields as $fid)
				{
					if (is_object($fid))
						$field = $fid;
					else
						if (isset($object->$fid))
							$field = $object->f($fid);

					$quick = in_array($field->id, $filter_fields_quick);

					if ($field->get('localized'))
						$temp = clone($object->f($field->id.'_'.Session::get('locale', $GLOBALS['site']['locale-default'])));
					else
						$temp = clone($field);
					if ($quick)
						$temp->set('filter-quick', true);
					$fields->add($temp);
				}

				$invocation->set('filter-fields', $fields);
				if ($fields->count())
				{
					$result['filter_form'] = $form = $invocation->form($action->id . '-filter');
					$form->attributeSet('class', 'filter');
					$form->autoHeader(__('search', '@core'));
					$form->loadValuesIn();
					if (@$form->valuesIn['filter-clear'])
					{
						$action->setV('filter-values', array());
						$form->valuesIn = array(0 => false);
					}
					$this->actionListFilterForm(0, $form, $invocation, $fields, $object);
					$form->autoSubmit(__('search-submit', '@core'), 'filter-submit');
					$form->i('_submit')->add('submit', 'filter-clear', (__('search-clear', '@core')));
					$filter = $this->actionListFilterForm(1, $form, $invocation, $fields, $object);
					if ($filter !== null)
						foreach ($filter->a as $f)
							$result['filter']->a[] = $f;
				}
			}
		}

		protected function actionListLimit($invocation, $action, &$result)
		{
			$limit = $invocation->get('limit');
			if (!$limit)
			{
				$result['pager'] = null;
				$limit = null;
				$pagesize = $invocation->get('page-size', 999999);
				$pagecount = ceil($this->object->count($result['filter']) / $pagesize);

				if ($pagecount > 1)
				{
					if ($invocation->get('page') !== null)
					{
						$page = ((int) $invocation->get('page', 1) - 1);
						$invocation->setV('page', $page);
					}
					$page = $invocation->getV('page', 0);
					if (($page >= $pagecount) || ($page < 0))
					{
						$page = 0;
						$invocation->setV('page', $page);
					}
					$page = $invocation->getV('page', 0);
					$limit = array($pagesize, $page * $pagesize);
					$result['pager'] = HTML::pager(array(
							'type'				=> '',
							'page-count'		=> $pagecount,
							'page-size'			=> $pagesize,
							'page'				=> $page + 1,
							'key'				=> 'page',
					));
				}
			}
			$result['limit'] = $limit;
		}

		protected function actionListActions($invocation, $action, &$result)
		{
			if ($invocation->get('view'))
				$actions = $invocation->get('actions', array('view'));
			else
			{
				$actions = $invocation->get('actions', array('new', 'view', 'edit', 'delete', 'acl'));
				if (USER == 'administrator')
					$actions[] = 'test-data';
				if ($this->object->get('tree'))
				{
					/*$actions[] = new Invocation('grab', $this->id, __('grab', '@core'));
					if ($oids = $this->action('grab')->getS('oids', array()))
					{
						$ref = new Invocation('drop', $this->id);
						$message = $ref->urlOP(null, $invocation->get('parent'), __('drop')) . ': ';
						foreach ($oids as $oid)
						{
							$object = $this->object->load(Feq('oid', $oid), true);
							$message .= $ref->urlOP($object, $invocation->get('parent'), $object->otitle) . ' ; ';
						}
						$invocation->message($message);
					}*/
				}
			}

			$result['actions'] = $this->actionPrepareActions($invocation, $action, $actions);
		}

		/*public function actionGrab($invocation, $action)
		{
			$object = $invocation->get('object');
			$back = $invocation->get('back', new Invocation('list', $this->id));

			if ($invocation->get('oid'))
			{
				$ok = true;
				if ($ok && (!$this->object->get('tree')))
					$ok = false;
				if ($ok && (!$object))
					$ok = false;
				if ($ok && (isset($this->can_grab) && (!$object->can_grab)))
					$ok = false;
				if ($ok)
				{
					$oids = $invocation->getS('oids', array());
					if (!in_array($object->oid, $oids))
						$oids[] = $object->oid;
					$invocation->setS('oids', $oids);
				}
				else
					$invocation->message(__('grab-cannot', '@core'));
			}
			$back->forward();
			return null;
		}

		public function actionDrop($invocation, $action)
		{
			$object = $invocation->get('object');
			$parent = $invocation->get('parent');
			$back = $invocation->get('back', new Invocation('list', $this->id));
			$oids = $this->action('grab')->getS('oids', array());

			if ($invocation->get('cancel'))
			{
				$this->action('grab')->setS('oids', array());
			}
			else
			{
				$selected = $invocation->get('selected', $this->action('grab')->getS('oids', array()));
				if (!is_array($selected))
					$selected = array($selected);
				$message = __('dropped', '@core') . ': ';
				foreach ($selected as $oid)
				{
					if (!in_array($oid, $oids))
						continue;
					if (!$object)
						continue;
					$object = $this->object->load(Feq('oid', $oid), true);
					$object->pid = $parent->oid;
					$object->save();
					$message .= $object->otitle . ' ; ';
					$oids = array_diff($oids, array($oid));
				}
				$this->action('grab')->setS('oids', $oids);
				$invocation->message($message);
			}
			$back->forward(array('parent' => $parent));
			return null;
		}*/

		protected function actionListForm($invocation, $action, &$result)
		{
			return false;
		}

		public function actionList($invocation, $action)
		{
			$result = array();

			$this->actionListFilter($invocation, $action, $result);
			$this->actionListOrder($invocation, $action, $result);
			$this->actionListColumns($invocation, $action, $result);
			$this->actionListLimit($invocation, $action, $result);
			$this->actionListActions($invocation, $action, $result);

			$result['objects'] = $this->object->load($result['filter'], array_values($result['order']), $result['limit']);
			$result['parent'] = $invocation->get('parent');
			$result['list'] = new Invocation($action->id, $this->id);

			if (!$this->actionListForm($invocation, $action, $result))
				$invocation->output(Core::common('list', array('invocation' => $invocation, 'result' => $result)));

			return $result;
		}

		public function actionEdit($invocation, $action)
		{
			$object = $invocation->get('object');
			$parent = $invocation->get('parent');
			$view = $invocation->get('view');

			if ($back = $invocation->get('back', new Invocation('list', $this->id)))
			{
				$back->set('parent', $parent);
				$actions = array_merge(array($back), $invocation->get('actions', array()));
			}
			$actions = $this->actionPrepareActions($invocation, $action, $actions);
			if ($invocation->get('acl'))
			{
				$temp = array();
				if ($object->f('ouid')->size !== false)
					$temp[] = 'ouid';
				if ($object->f('orid')->size !== false)
					$temp[] = 'orid';
				if ($object->f('oattrs')->size !== false)
					$temp[] = 'oattrs';
				$fields = $object->fields($temp, true);
				$attributes = new Fields();
			}
			else
			{
				$fields = $object->fields($invocation->get('fields'), true);
				$attributes = $object->attributes($invocation->get('attributes'));
			}
			
			if ($invocation->get('new'))
			{
				$object->ostatus = 'new';
				if ($parent->oid)
				{
					$object->pid = $parent->oid;
					/*foreach ($parent->fields()->iterator() as $field)
						if (($field->type == Field::TYPE_CONNECTION) && ($field->default['object'] == $object->i))
						{
							$info = $field->connection($parent);
							foreach ($info['filter']->a as $feq)
							{
								$fid = $feq->a->fid();
								$object->$fid = $feq->b->a;
								unset($fields->$fid);
							}
						}
					*/
				}
			}

			$form = $invocation->form();
			$form->i('_main')->object = $object;
			$form_options = array_merge(($view ? array('edit' => false, 'view' => true) : array('edit' => true, 'view' => false)), $this->get('form-options-0', array()));
			$form->autoHeader(null, ($object->otitle ? (' : ' . $object->otitle) : ''));
			$object->form(0, $form->i('_main'), $fields, $invocation, $form_options);
			if ($attributes->size())
			{
				$this->add(new FormGroup('_attributes'));
				$form->_attributes->object = $object;
				$form->_attributes->set('before', '_submit');
				$form->_attributes->label = __('attributes', '@core') . ($object->otitle ? (' : ' . $object->otitle) : '');
				$object->form(0, $form->_attributes, $attributes, $invocation, $form_options);
			}

			$original = clone($object);

			if (!$view)
			{
				$form->i('_submit')->label = __('save', '@core') . ($object->otitle ? (' : ' . $object->otitle) : '');
				$form->autoSubmit(__('save'));

				if ($form->validate())
				{
					$form_options = array_merge(array('edit' => false), $this->get('form-options-1', array()));
					$object->form(1, $form->i('_main'), $fields, $invocation, $form_options);
					if ($attributes->size())
					{
						if (!$invocation->get('new'))
						$object->form(1, $form->_attributes, $attributes, $invocation, $form_options);
					}
					if ($parent && $parent->oid)
						$object->pid = $parent->oid;
					/*if ($invocation->get('new') && ($this->object->count(Feq('oid', $object->oid))))
						$invocation->message(__('rule-id-exists'), 'error');
					else*/
					{
						$object->save();
						if ($attributes->size())
						{
							if ($invocation->get('new'))
							{
								$object->form(1, $form->_attributes, $attributes, $action, $form_options);
								$object->save();
							}
						}
						$invocation->message(__('saved') . ': ' . HTML::e($object->otitle));
						if ($form->continue && $original->oid)
							$back = new Invocation($action->id, $this->id);
						if (!$invocation->invoker())
							$back->forward(array('object' => $object, 'parent' => $parent));
						$form->display = false;
					}
				}
			}

			if ($form->display)
			{
				$invocation->actionsTop($actions);
				$invocation->output($form);

				$connections = array();
				/*if ($invocation->get('acl'))
				 $connections[] = 'oacl';
					else */if (!$invocation->get('new'))
				foreach ($object->fields(null, true) as $field)
					if (($field->type == Field::TYPE_CONNECTION) && $field->visible($form) && $field->get('recurse'))
						$connections[] = $field->id;

				foreach ($connections as $fid)
				{
					$field = $object->f($fid);
					$info = $field->connection($object);
					$module = $info['object']->module();
					$reference = new Invocation('list', $module->id);
					if ($reference->check())
					{
						$reference->invoker = $invocation->id;
						$reference->set('filter-invoker', $info['filter']);
						$reference->set('view', $invocation->get('view'));
						//TODO probudit $reference a spustit znovu spousteni
					}
				}
			}

			$result['fields'] = $fields;
			$result['actions'] = $actions;
			$result['object'] = $object;
			$result['original'] = $original;
			$result['form'] = $form;

			return $result;
		}

		public function actionView($invocation, $action)
		{
			$invocation->set('view', true);
			return $this->actionEdit($invocation, $action);
		}

		public function actionNew($invocation, $action)
		{
			$invocation->set('new', true);
			return $this->actionEdit($invocation, $action);
		}

		public function actionAcl($invocation, $action)
		{
			$invocation->set('acl', true);
			return $this->actionEdit($invocation, $action);
		}

		public function actionDelete($invocation, $action)
		{
			$objects = $invocation->get('objects');
			$object = $invocation->get('object');
			$parent = $invocation->get('parent');

			if ($back = $invocation->get('back', new Invocation('list', $this->id)))
			{
				$actions = array_merge(array($back), $invocation->get('actions', array()));
			}
			$actions = $this->actionPrepareActions($invocation, $action, $actions);

			if (!$objects)
				return $action->status(Action::STATUS_ERROR);

			if ($invocation->get('confirm', true))
			{
				$form = $invocation->form();
				$form->autoHeader();
				$form->i('_main')->add('checkbox', 'confirm', __('confirm-delete', '@core'));
				foreach ($objects->iterator() as $object)
					$form->i('_main')->add('static', $object->otitle);
				$form->autoSubmit();

				$proceed = false;
				if ($form->validate())
				{
					if ($form->values['confirm'])
						$proceed = true;
					else
						$back->forward();
					$form->display = false;
				}
				if ($form->display)
				{
					$invocation->actionsTop($actions);
					$invocation->output($form);
				}
				if (!$proceed)
					$objects = null;
			}

			if ($objects !== null)
			{
				foreach ($objects->iterator() as $object)
				{
					/*
					if ($invocation->get('move-children'))
						foreach ($this->children as $child)
					 		foreach ($child->object->load(Feq('pid', $object->oid)) as $o)
					 		{
					 			$o->pid = $object->pid;
					 			$o->save();
					 		}
					else
						foreach ($this->children as $child)
							if ($a = $child->action('delete'))
								foreach ($child->object->load(Feq('pid', $object->oid)) as $o)
									$a->call(array('pid' => $o->id));
					*/
					if ($this->object->get('tree'))
					{
						if ($invocation->get('move-tree'))
							foreach ($this->object->load(Feq('pid', $object->oid)) as $o)
							{
								$o->pid = $object->pid;
								$o->save();
							}
					}
					$object->delete();
					$invocation->message(__('deleted') . ': ' . HTML::e($object->otitle));
				}
				$back->forward();
			}

			$result = null;
			return $result;
		}

		public function actionMail($invocation, $action)
		{
			$object = $invocation->get('object');
			$parent = $invocation->get('parent');
			$pid = $invocation->get('pid');
			$oid = $invocation->get('oid');

			if ($back = $invocation->get('back', new Invocation('list', $this->id)))
			{
				$actions = array_merge(array($back), $invocation->get('actions', array()));
			}
			$actions = $this->actionPrepareActions($invocation, $action, $actions);
			$fields = $this->object->fields($invocation->get('fields'), true);

			$form = $invocation->form();
			$form->autoHeader(null, ($object->otitle ? (' : ' . $object->otitle) : ''));
			$form->i('_hidden')->add('hidden', 'html', U::request('html', true));
			$form->i('_main')->add('text', 'subject', $action->__('subject'));
			$form->i('_main')->add('text', 'from', $action->__('from'));
			$form->i('_main')->add('textarea', 'body', $action->__('body'), ($invocation->get('html', true) ? 'html' : null));
			$form->i('_main')->add('textarea', 'emails', $action->__('emails'));
			for ($i = 0; $i < 4; $i++)
				$form->i('_main')->add('file', 'attachment'.$i, $action->__('attachment'));
			$form->autoSubmit();

			$form->i('_main')->i('subject')->default = $invocation->get('subject', $GLOBALS['site']['name'] . ' - ');
			$form->i('_main')->i('from')->default = $invocation->get('from', user()->email);
			$form->i('_main')->i('body')->default = $invocation->get('body');
			$form->i('_main')->i('emails')->default = $invocation->get('emails');

			if ($form->validate())
			{
				$attachments = array();
				for ($i = 0; $i < 4; $i++)
				{
					$file = $form->i('_main')->i('attachment'.$i);
					if ($file->error == 0)
						$attachments[] = array(
							'file' => $file->value,
							'type' => $file->type,
							'name' => $file->name,
						);
				}

				$emails_array = array_unique(explode("\n", $form->values['emails']));

				if ((count($emails_array) > 20) && m('cron_mail'))
					m('cron_mail')->mail(
						null,
						$emails_array,
						$form->values['subject'],
						$form->values['body'],
						$form->values['from'],
						array(), // headers
						$attachments
					);
				else
					U::mail(
						null,
						$emails_array,
						$form->values['subject'],
						$form->values['body'],
						$form->values['from'],
						array(), // headers
						$attachments
					);

				$invocation->message(__('sent'));
				$back->forward();
				$form->display = false;
			}

			if ($form->display)
			{
				$invocation->actionsTop($actions);
				$invocation->output($form);
			}

			$result['actions'] = $actions;
			$result['form'] = $form;

			return $result;
		}

		public function actionGetTree($invocation, $action, $list, $pid = null, $parents = array(), &$special_fields, &$special_glue)
		{
			$result = new Objects();
			foreach ($list->iterator() as $object)
				if ($pid == $object->pid)
				{
					$oid = $object->oid;
					$result->add($object);
					if (!isset($result->$oid->x['special']))
						$result->$oid->x['special'] = array();
					foreach ($special_fields as $special_field)
					{
						foreach ($parents as $parent)
							$values[] = $parent->$special_field;
						$result->$oid->x['special'][$special_field] = array(
								'array' => $values,
								'string' => implode((isset($special_glue[$special_field]) ? $special_glue[$special_field] : '/'), $values),
						);
					}
					$result->$oid->x['tree'] = $this->actionGetTree($invocation, $action, $list, $oid, array_merge($parents, array($result->$oid)), $special_fields, $special_glue);
				}
			return $result;
		}

		public function actionGetTreeIdList($invocation, $action, $list, $pid)
		{
			$result = array($pid);
			foreach ($list->iterator() as $object)
				if ($object->pid == $pid)
					$result = array_merge($result, $this->actionGetTreeIdList($invocation, $action, $list, $object->oid));
			return $result;
		}

		public function actionGet($invocation, $action)
		{
			$oid = $invocation->get('oid');
			$result = array('object' => null, 'parent' => null, 'parents' => array());
			$result['object'] = $invocation->get('object');
			if (($oid === null) && !isset($result['object']->x['placeholder']))
				$oid = $result['object']->oid;
			/*else if (isset($result['object']->x['placeholder']))
				unset($result['object']->x['placeholder']);*/ // WHY?
			$result['parents'] = $result['object']->parents();
			if ($result['parents']->count() > 0)
				$result['parent'] = $result['parents']->last();

			if (!$result['object'] || !$result['object']->g)
				return $result;

			$result['objects'] = array('list' => new Objects(), 'tree' => new Objects(), 'pager' => null);

			$page_size = $invocation->get('page-size', 999999);
			$page = ((int) $invocation->get('page', U::request('page', 1))) - 1;

			$filter = $invocation->get('filter', $this->object->get('filter'));
			$tree = $invocation->get('tree', $this->object->get('tree'));
			$groups_filter = $invocation->get('group-filter', $this->object->get('group-filter'));
			$groups_order = $invocation->get('order', $this->object->get('group-order', $this->object->get('order')));
			$groups_limit = null;
			$items_filter = $invocation->get('item-filter', $this->object->get('item-filter', Fnot($groups_filter)));
			$items_order = $invocation->get('order', $this->object->get('order'));

			foreach ($invocation->get('request-filters', array()) as $request_filter)
				$filter = $request_filter->apply($filter, $invocation);

			if ($filter)
			{
				if ($groups_filter)
					$groups_filter = Fand($groups_filter, $filter);
				$items_filter = Fand($items_filter, $filter);
			}

			if ($oid && $groups_filter && $this->object->f('pid'))
				$groups_filter = Fand(Feq('pid', $oid), $groups_filter);

			$groups_objects = ($groups_filter ? $this->object->load($groups_filter, $groups_order, $groups_limit) : new Objects());
			$result['objects']['list']->add($groups_objects);
			if (!$invocation->get('groups'))
			{
				if ($this->object->f('pid'))
				{
					if ($groups_filter && $invocation->get('recurse'))
					{
						$ids = $this->actionGetTreeIdList($invocation, $action, $groups_objects, $oid);
						$items_filter = Fand($items_filter, Fin('pid', $ids));
					}
					if ($groups_filter && !$invocation->get('recurse'))
					{
						$items_filter = Fand($items_filter, Feq('pid', $result['object']->oid));
					}
				}

				$items_count = $this->object->count($items_filter);
				$items_pagecount = (int) ceil($items_count / $page_size);
				if (/*($page >= $pagecount) || */($page < 0)) $page = 0;
					$items_limit = $invocation->get('limit', array($page_size, $page * $page_size));

				$items_objects = $this->object->load($items_filter, $items_order, $items_limit);
				$result['objects']['list']->add($items_objects);

				$args = array();
				if ($result['object'])
				{
					$object_fields = $invocation->get('object-fields', array('oid'));
					foreach ($object_fields as $object_field)
						$args[$object_field] = $result['object']->$object_field;
				}
				$args['invocation'] = $invocation;
				$url = $this->url($action->id, $args, 'pager');
				$result['pager'] = HTML::pager(array(
						'type'				=> '',
						'page-count'		=> $items_pagecount,
						'page-size'			=> $page_size,
						'page'				=> $page + 1,
						'url'				=> $url . '.page.%d',
				));
			}
			else
				$result['pager'] = null;

			if ($tree)
			{
				$special_fields = $invocation->get('special-fields', array(/*'oid' => array()*/));
				$special_glue = $invocation->get('special-glue', array());
				$result['objects']['tree'] = $this->actionGetTree($invocation, $action, $result['objects']['list'], $oid, array(), $special_fields, $special_glue);
			}

			return $result;
		}

	}

	class RequestFilter
	{
		public					$field								= null;
		public					$type								= 'Feq';
		public					$request_key						= null;

		public function __construct($field, $type = 'Feq', $request_key = null)
		{
			$this->field = $field;
			$this->type = $type;
			$this->request_key = $request_key;
		}

		public function apply($in_filter, $invocation = null)
		{
			$request_key = ($this->request_key ? $this->request_key : $this->field);
			if (!($value = U::request($request_key)))
				return $in_filter;
			$result = new Fo(null, array());
			foreach ((is_array($this->field) ? $this->field : array($this->field)) as $fid)
				if ($this->type == 'Flike%')
					$result->a[] = Flike($fid, '%'.$value.'%');
				else if ($this->type == 'Flike')
					$result->a[] = Flike($fid, $value);
				else if ($this->type == 'Fft')
					$result->a[] = Fft($fid, $value);
				else
					$result->a[] = Feq($fid, $value);
			if ($in_filter)
				return Fand(clone($in_filter), Fo($result));
			else
				return $result;
		}
	}
?>