<?php
	// Author: Jakub Macek, CZ; Copyright: Poski.com s.r.o.; Code is 100% my work. Do not copy.

	class FormElement
	{
		public					$id					= '';
		public					$options			= array();
		public					$filters			= array();
		public					$rules				= array();
		public					$errors				= array();
		public					$notes				= array();
		public					$javascripts		= array();
		public					$template			= null;
		public					$layout				= 'default';
		public					$default			= null;
		public					$label				= null;
		public					$value				= null;
		public					$valueDisplay		= null;
		public					$group				= null;
		public					$field				= null;
		public					$object				= null;
		public					$readonly			= false;
		public					$disabled			= false;
		public					$view				= false;
		public					$frozen				= false;
		public					$rendered			= array();

		public function __construct()
		{
			$args = func_get_args();
			call_user_func_array(array($this, 'construct'), $args);
		}

		public function construct($id = null)
		{
			$this->id = $id;
		}

		public function type()
		{
			return substr(get_class($this), 12);
		}

		public function get($key, $value = null)
		{
			return (isset($this->options[$key]) ? $this->options[$key] : $value);
		}

		public function set($key, $value)
		{
			if ($value === null)
				unset($this->options[$key]);
			else
				$this->options[$key] = $value;
		}

		public function label()
		{
			return U::string($this->label, array('self' => $this));
		}

		public function name()
		{
			return strtr($this->id, '@/', '_-');
		}

		public function id()
		{
			return strtr($this->id, '@/', '_-');
		}

		public function group()
		{
			return $this->group;
		}

		public function form()
		{
			if ($this instanceof Form)
				return $this;
			else if ($this->group)
				return $this->group()->form();
			else
				return null;
		}

		public function attributeGet($key, $value = null, $type = 'element')
		{
			$attributes = $this->get('attributes-'.$type, array());
			return (isset($attributes[$key]) ? $attributes[$key] : $value);
		}

		public function attributeSet($key, $value, $type = 'element')
		{
			$attributes = $this->get('attributes-'.$type, array());
			if ($value === null)
				unset($attributes[$key]);
			else
				$attributes[$key] = $value;
			$this->set('attributes-'.$type, $attributes);
		}

		public function attributesToString($type)
		{
			$attributes = $this->get('attributes-'.$type, array());
			$result = array();
			foreach ($attributes as $k => $v)
				$result[] = $k . '="' . HTML::e($v) . '"';
			return implode(' ', $result);
		}

		public function loadValue()
		{
		}

		public function load()
		{
			if ($this->form()->submitted() && !$this->frozen)
			{
				$this->loadValue();
				$this->apply();
			}
			if ($this->readonly || $this->disabled || $this->view)
				$this->value = $this->default;
			if ($this->valueDisplay === null)
				$this->valueDisplay = $this->value;
			if ($this->valueDisplay === null)
				$this->valueDisplay = $this->default;
		}

		public function filter($filter)
		{
			if (!is_object($filter))
			{
				$class = 'FormFilter_' . $filter;
				$args = func_get_args();
				$filter = new $class();
				call_user_func_array(array($filter, 'construct'), array_slice($args, 1));
			}
			$this->filters[] = $filter;
			return $filter;
		}

		public function applyOne($filter)
		{
			$this->value = $filter->apply($this->value, $this);
		}

		public function apply()
		{
			foreach ($this->filters as $filter)
				$this->applyOne($filter);
		}

		public function rule($rule)
		{
			if (!is_object($rule))
			{
				$class = 'FormRule_' . $rule;
				$args = func_get_args();
				$rule = new $class();
				call_user_func_array(array($rule, 'construct'), array_slice($args, 1));
			}
			$this->rules[] = $rule;
			return $rule;
		}

		public function validateOne($rule)
		{
			return $rule->validate($this->value, $this);
		}

		public function validate()
		{
			$result = true;
			$this->errors = array();
			foreach ($this->rules as $rule)
				$result = $this->validateOne($rule) && $result;
			return $result;
		}
		
		public function renderPrototype($part = null)
		{
			$this->rendered[$part] = true;
			return $this->renderPart($part);
		}

		public function renderPart($part = null)
		{
			$key = 'render';
			if ($part)
				$key .= '-' . $part;
			if (!$this->get($key, true))
				return '';

			$result = '';
			if ($part === null)
			{
				$result .= $this->renderPrototype('begin');
				$result .= $this->renderPrototype('main');
				$result .= $this->renderPrototype('additional');
				$result .= $this->renderPrototype('end');
			}
			else if ($part == 'main')
			{
				$result .= '<div class="label"'.$this->attributesToString('label-container').'>';
				$result .= $this->renderPrototype('label');
				$result .= '</div>';
				$result .= '<div class="element"'.$this->attributesToString('element-container').'>';
				$result .= $this->renderPrototype('element');
				$result .= '</div>';
			}
			else if ($part == 'additional')
			{
				$result .= $this->renderPrototype('specific');
				$result .= $this->renderPrototype('tooltip');
				$result .= $this->renderPrototype('javascripts');
				$result .= $this->renderPrototype('notes');
				$result .= $this->renderPrototype('errors');
			}
			else if ($part == 'begin')
			{
				$style = '';
				if ($this->get('hidden'))
					$style .= 'display: none;';
				$class = 'form_'.$this->type() . ' element_' . $this->id();
				foreach ($this->rules as $rule)
					if ($rule->type() == 'required')
						$class .= ' required';
				if ($this->errors)
					$class .= ' error';
				if ($this->readonly)
					$class .= ' readonly';
				if ($this->disabled)
					$class .= ' disabled';
				if ($this->view)
					$class .= ' view';
				$result .= '<div class="'.$class.'" style="'.$style.'"'.$this->attributesToString('container').'>';
			}
			else if ($part == 'end')
				$result .= '</div>';
			else if ($part == 'label')
			{
				$label = trim($this->label());
				if (!$label)
					$label = '&nbsp;';
				$for = ($this->view ? '' : ('for="'.$this->id().'"'));
				$result .= '<label '.$for.' '.$this->attributesToString('label').'>' . $label . '</label>';
			}
			else if ($part == 'element')
				;
			else if ($part == 'specific')
				;
			else if ($part == 'javascripts')
			{
				foreach ($this->javascripts as $javascript)
					$result .= HTML::js($javascript);
			}
			else if ($part == 'tooltip')
			{
				if ($tooltip = $this->get('tooltip'))
				{
					$genid = strtr($this->name(), '-', '_') . mt_rand(100000, 999999);
					$result .= '<div class="tooltip" id="'.$genid.'-tooltip">' . $tooltip . '</div><span class="tooltip" onmouseover="TagToTip(\''.$genid.'-tooltip\')">?</span>';
				}
			}
			else if ($part == 'notes')
			{
				$result .= '<div class="notes_container">';
				if ($this->notes)
				{
					$result .= '<div class="notes">';
					foreach ($this->notes as $note)
					{
						$note = str_replace('{$label}', $this->label(), $note);
						$result .= '<div class="note">' . U::string($note, array('self' => $this)) . '</div>';
					}
					$result .= '</div>';
				}
				$result .= '</div>';
			}
			else if ($part == 'errors')
			{
				$result .= '<div class="errors_container">';
				if ($this->errors)
				{
					$result .= '<div class="errors">';
					foreach ($this->errors as $error)
					{
						$error = str_replace('{$label}', $this->label(), $error);
						$result .= '<div class="error">' . U::string($error, array('self' => $this)) . '</div>';
					}
					$result .= '</div>';
				}
				$result .= '</div>';
			}
			return $result;
		}

		public function renderTemplate($options = array())
		{
			if (!class_exists('Template'))
				return '[[ Template class missing ]]';
			
			$template_data = ':'.$this->template.':';
			$options['self'] = $this;
			if ($this instanceof Form)
			{
				$template_data .= 'form';
				$options['form'] = $this;
				$options['group'] = $this;
			}
			else if ($this instanceof FormGroup)
			{
				$template_data .= 'group';
				$options['group'] = $this;
			}
			else
			{
				$template_data .= 'element';
				$options['element'] = $this;
			}
			if ($this->layout)
				$template_data .= '-' . $this->layout;
			if (is_object($this->template))
				return $this->template->process($options);
			else
				return Template::quick(null, null, $template_data, $options);
		}

		public function render()
		{
			$result = '';
			if ($this->template)
				$result .= $this->renderTemplate();
			else
				$result .= $this->renderPrototype();
			return $result;
		}

		public function toString()
		{
			return $this->render();
		}
	}

	class FormElement_input extends FormElement
	{
		public function construct($id = null, $label = null)
		{
			$this->id = $id;
			$this->label = $label;
		}

		public function loadValue()
		{
			$form = $this->form();
			$this->value = @$form->valuesIn[$this->name()];
			$form->values[$this->name()] = $this->value;
		}

		public function renderPart($part = null)
		{
			if ($part == 'element')
			{
				if ($this->view)
				{
					$asHtml = $this->get('view-as-html', false);
					$result = $this->valueDisplay;
					if (!$asHtml)
						$result = HTML::e($result);
				}
				else
				{
					if ($this->readonly)
						$this->attributeSet('readonly', true);
					if ($this->disabled)
						$this->attributeSet('disabled', true);
					$result = '<input type="'.$this->inputType().'" id="'.$this->name().'" name="'.$this->name().'" value="'.HTML::e($this->valueDisplay).'" '.$this->attributesToString('element').' />';
				}
			}
			else
				$result = parent::renderPart($part);
			return $result;
		}

		public function inputType()
		{
			return $this->type();
		}
	}

	class FormElement_hidden extends FormElement_input
	{
		public function construct($id = null, $value = null)
		{
			$this->id = $id;
			$this->default = $value;
		}
	}

	class FormElement_text extends FormElement_input
	{

	}

	class FormElement_password extends FormElement_input
	{
		public function load()
		{
			parent::load();
			$this->valueDisplay = '';
		}
	}

	class FormElement_date extends FormElement_input
	{
		public function format()
		{
			return $this->get('format', __('date-format', '@core'));
		}

		public function setFormat($format)
		{
			if ($format == 'd.m.Y')
				$format = '%d.%m.%Y';
			if ($format == 'Y-m-d')
				$format = '%Y-%m-%d';
			if ($format == 'Y-m-d H:i:s')
				$format = '%Y-%m-%d';
			$this->set('format', $format);
		}

		public function load()
		{
			parent::load();
			if ($this->valueDisplay)
				$this->valueDisplay = strftime($this->format(), strtotime($this->valueDisplay));
		}

		public function loadValue()
		{
			$form = $this->form();
			$temp = @$form->valuesIn[$this->name()];
			if ($temp)
				$this->value = strftime('%Y-%m-%d', strtotime($temp));
			$form->values[$this->name()] = $this->value;
		}

		public function inputType()
		{
			return 'text';
		}

		public function renderPart($part = null)
		{
			if ($part == 'specific')
			{
				if (!$this->view && !$this->readonly && !$this->disabled)
				{
					$GLOBALS['page']['flags']['jscalendar'] = true;
					$genid = strtr($this->name(), '-', '_') . mt_rand(100000, 999999);
					$result = '<img src="'.$GLOBALS['site']['path'] . 'web/_images/calendar.gif" id="'.$genid.'_button" style="cursor: pointer;" title="Calendar" alt="Calendar" />
						'.HTML::js(0).'
						    Calendar.setup({
						        inputField : "'.$this->name().'",
						        ifFormat : "'.$this->format().'",
						        button : "'.$genid.'_button",
						        singleClick : true
						    });'.HTML::js(1);
				}
				else
					$result = '';
			}
			else
				$result = parent::renderPart($part);
			return $result;
		}
	}

	class FormElement_submit extends FormElement_input
	{
		public function construct($id = null, $value = null, $label = null)
		{
			$this->id = $id;
			$this->valueDisplay = $this->default = $value;
			$this->label = $label;
			$this->attributeSet('class', 'submit');
		}
	}

	class FormElement_reset extends FormElement_input
	{
		public function construct($id = null, $value = null, $label = null)
		{
			$this->id = $id;
			$this->default = $value;
			$this->label = $label;
		}
	}

	class FormElement_checkbox extends FormElement_input
	{
		public					$option				= null;

		public function construct($id = null, $label = null, $option = null)
		{
			parent::construct($id, $label);
			$this->option = ($option ? $option : $this->id);
		}

		public function loadValue()
		{
			$form = $this->form();
			$temp = @$form->valuesIn[$this->name()];
			if (($temp === '0') || ($temp === 0))
				$this->value = false;
			else if (($temp === '1') || ($temp === 1))
				$this->value = true;
			else
				$this->value = null;
			$form->values[$this->name()] = $this->value;
		}

		public function renderPart($part = null)
		{
			if ($part == 'element')
			{
				if ($this->view)
					$result = HTML::e($this->valueDisplay ? __('true', '@core') : __('false', '@core'));
				else
				{
					$result = '<input type="hidden" name="'.$this->name().'" value="0" />';
					if ($this->readonly)
						$this->attributeSet('readonly', true);
					if ($this->disabled)
						$this->attributeSet('disabled', true);
					$this->attributeSet('checked', ($this->valueDisplay ? 'checked' : null));
					$result .= '<input type="'.substr(get_class($this), 12).'" id="'.$this->name().'" name="'.$this->name().'" value="1" '.$this->attributesToString('element').' />';
				}
			}
			else
				$result = parent::renderPart($part);
			return $result;
		}
	}

	class FormElement_radio extends FormElement_input
	{
		public					$name				= null;
		public					$option				= null;

		public function construct($id = null, $name = null, $option = null, $label = null)
		{
			$this->id = $id;
			$this->option = $option;
			$this->name = $name;
			$this->label = $label;
		}

		public function name()
		{
			return $this->name;
		}

		public function loadValue()
		{
			$form = $this->form();
			$temp = @$form->valuesIn[$this->name()];
			if ($this->option == $temp)
				$this->value = true;
			$form->values[$this->id] = $this->value;
		}

		public function renderPart($part = null)
		{
			if ($part == 'element')
			{
				if ($this->view)
					$result = HTML::e($this->valueDisplay ? __('true', '@core') : __('false', '@core'));
				else
				{
					if ($this->readonly)
						$this->attributeSet('readonly', true);
					if ($this->disabled)
						$this->attributeSet('disabled', true);
					$this->attributeSet('checked', ($this->valueDisplay ? 'checked' : null));
					$result = '<input type="'.substr(get_class($this), 12).'" id="'.$this->id().'" name="'.$this->name().'" value="'.HTML::e($this->option).'" '.$this->attributesToString('element').' />';
				}
			}
			else
				$result = parent::renderPart($part);
			return $result;
		}
	}

	class FormElement_file extends FormElement_input
	{
		public					$remove				= null;
		public					$error				= null;
		public					$name				= null;
		public					$type				= null;
		public					$size				= null;
		public					$allow_upload		= true;
		public					$allow_remove		= true;

		public function loadValue()
		{
			$form = $this->form();
			if ($this->allow_remove)
			{
				$temp = @$form->valuesIn[$this->name().'-remove'];
				if ($temp)
				{
					$this->value = '';
					$this->remove = true;
				}
			}
			if ($this->allow_upload)
			{
				$temp = @$form->valuesIn[$this->name()];
				$this->value = $temp['tmp_name'];
				$this->error = $temp['error'];
				$this->name = $temp['name'];
				$this->type = $temp['type'];
				$this->size = $temp['size'];
				$form->values[$this->name()] = $this->value;
			}
		}

		public function validate()
		{
			if (($this->error == UPLOAD_ERR_OK) || ($this->error == UPLOAD_ERR_NO_FILE))
				return parent::validate();
			else
				return false;
		}

		public function blobulize()
		{
			if (!is_readable($this->value) || !$this->field || !$this->object)
				return false;

			$prefix = $this->get('prefix', $this->field->get('prefix', $this->object->i . '-'));
			$postfix = $this->get('postfix', $this->field->get('postfix', '-#name#.#extension#'));

			$extension = substr(strrchr($this->name, '.'), 1);
			$name = substr($this->name, 0, strlen($this->name) - strlen($extension) - 1);
			$replace_from = array('#name#', '#extension#');
			$replace_to = array(U::urlize($name), U::urlize($extension));
			$prefix = str_replace($replace_from, $replace_to, $prefix);
			$postfix = str_replace($replace_from, $replace_to, $postfix);
			$new_value = $this->field->blob($this->value, $this->type, $prefix, $postfix, true, $this->name);
			unlink($this->value);
			$this->value = $new_value;

			return $new_value;
		}

		public function renderPart($part = null)
		{
			if ($part === null)
			{
				if ($this->allow_upload)
				{
					foreach ($this->rules as $rule)
					{
						if ($rule instanceof FormRule_file_size)
							$this->notes[] .= sprintf(__('rule-file-limit-size-allowed', '@core'), $rule->max);
						if (($rule instanceof FormRule_file_type) || ($rule instanceof FormRule_file_type_regex))
							$this->notes[] .= sprintf(__('rule-file-limit-type-allowed', '@core'), $rule->typesText());
					}
				}
				return parent::renderPart($part);
			}
			else if ($part == 'element-view')
			{
				if ($this->valueDisplay)
				{
					$result = '';
					if ($this->field && $this->object)
					{
						if ($temp = $this->field->get('name'))
							$result .= $this->object->$temp;
						if ($temp = $this->field->get('size'))
							$result .= ' (' . $this->object->$temp . ' B)';
						else
							$result .= ' (' . filesize($GLOBALS['site']['data'] . 'blob/' . $this->valueDisplay) . ' B)';
						if ($temp = $this->field->get('type'))
							$result .= ' [' . $this->object->$temp . ']';
					}
					$result .= ' <a href="' . $GLOBALS['site']['path'] . 'data/blob/' . $this->valueDisplay . '">' . __('files-get', '@core') . '</a>';
				}
				else
					$result = __('files-empty', '@core');
			}
			else if ($part == 'element')
			{
				if ($this->view)
					$result = $this->renderPrototype('element-view');
				else
				{
					if ($this->readonly)
						$this->attributeSet('readonly', true);
					if ($this->disabled)
						$this->attributeSet('disabled', true);
					$result = '';
					if ($this->allow_upload)
						$result .= '<input type="'.substr(get_class($this), 12).'" id="'.$this->name().'" name="'.$this->name().'" '.$this->attributesToString('element').' />';
					if ($this->allow_remove)
						if ($this->valueDisplay && !$this->readonly && !$this->disabled)
							$result .= ' <input type="checkbox" id="'.$this->name().'-remove" name="'.$this->name().'-remove" />' .
								'<label for="'.$this->name().'-remove">' . __('remove', '@core') . '</label> ';
					if ($this->valueDisplay)
						$result .= $this->renderPrototype('element-view');
				}
			}
			else
				$result = parent::renderPart($part);
			return $result;
		}
	}

	class FormElement_checkboxgroup extends FormElement
	{
		public					$items				= array();

		public function construct($id = null, $items = array())
		{
			$this->id = $id;
			$this->items = $items;
		}

		public function fixItems()
		{
			$form = $this->form();
			$temp0 = $this->items;
			$this->items = array(); $temp2 = array();
			foreach ($temp0 as $temp1)
			{
				if (is_string($temp1))
					$temp1 = $form->find($temp1);
				if (!in_array($temp1->id, $temp2))
				{
					$this->items[$temp1->id] = $temp1;
					$temp2[] = $temp1->id;
				}
			}
		}

		public function loadValue()
		{
			$this->fixItems();
			$form = $this->form();
			$value = array();
			foreach ($this->items as $item)
				if ($item->value)
					$value[] = $item->option;
			$this->value = $value;
			$form->values[$this->name()] = $this->value;
		}

		public function renderPart($part = null)
		{
			if (($part == 'element') || ($part == 'label'))
				$result = '';
			else
				$result = parent::renderPart($part);
			return $result;
		}
	}

	class FormElement_radiogroup extends FormElement_checkboxgroup
	{
	}

	class FormElement_static extends FormElement
	{
		public function construct($value = null, $id = null)
		{
			if ($id === null)
				$id = 'static_' . mt_rand(1000000, 9999999);
			$this->id = $id;
			$this->default = $value;
		}

		public function renderPart($part = null)
		{
			if ($part == 'label')
				$result = '';
			else if ($part == 'element')
				$result = $this->valueDisplay;
			else
				$result = parent::renderPart($part);
			return $result;
		}
	}

	// IMPORTANT: texy is GPL ! don't use
	class FormElement_textarea extends FormElement
	{
		public					$formatting			= null;

		public function construct($id = null, $label = null, $formatting = null)
		{
			$this->id = $id;
			$this->label = $label;
			$this->formatting = $formatting;
		}

		public function loadValue()
		{
			$form = $this->form();
			$this->value = @$form->valuesIn[$this->name()];
			$form->values[$this->name()] = $this->value;
		}

		public function renderPart($part = null)
		{
			if ($part == 'element')
			{
				if ($this->view)
				{
					$asHtml = false;
					if ($this->field)
						$asHtml = $this->field->get('view-as-html', $asHtml);
					$asHtml = $this->get('view-as-html', $asHtml);
					if ($asHtml)
						$result = $this->valueDisplay;
					else
					{
						$temp = $this->valueDisplay;
						if ($this->formatting == 'html')
							$temp = strip_tags(str_replace(array('<br />', '</p>', '</div>'), array("\n", "\n", "\n"), $temp));
						$result = nl2br(HTML::e($this->valueDisplay));
					}
				}
				else
				{
					if ($this->readonly)
						$this->attributeSet('readonly', true);
					if ($this->disabled)
						$this->attributeSet('disabled', true);
					if (!$this->attributeGet('cols'))
						$this->attributeSet('cols', 40);
					if (!$this->attributeGet('rows'))
						$this->attributeSet('rows', 5);
					$result = '<textarea id="'.$this->name().'" name="'.$this->name().'" '.$this->attributesToString('element').'>'.HTML::e($this->valueDisplay).'</textarea>';
					if ($this->formatting == 'html')
					{
						echo HTML::js('tinyMCE.editors["'.$this->name().'"] = null; tinyMCE.execCommand("mceAddControl", true, "'.$this->name().'");');
						$GLOBALS['page']['flags']['tiny_mce'] = true;
					}
				}
			}
			else
				$result = parent::renderPart($part);
			return $result;
		}
	}

	class FormElement_captcha extends FormElement
	{
		public					$captchaId			= null;

		public function construct($id = null, $label = null, $captchaId = null)
		{
			if ($id === null)
				$id = 'captcha';
			if ($label === null)
				$label = __('captcha', '@core');
			$this->id = $id;
			$this->label = $label;
			if (!$captchaId)
				$captchaId = $this->form()->id . '-' . $this->id;
			$this->captchaId = $captchaId;
		}

		public function loadValue()
		{
			$form = $this->form();
			$this->value = @$form->valuesIn[$this->name()];
			$form->values[$this->name()] = $this->value;
		}

		public function validate()
		{
			if (Core::captchaOK($this->value, $this->captchaId))
				return parent::validate();
			else
			{
				$this->errors['captcha'] = __('rule-captcha', '@core');
				return false;
			}
		}

		public function renderPart($part = null)
		{
			if ($part == 'element')
			{
				if ($this->view)
					$result = '';
				else
				{
					$result = '<img id="'.$this->name().'_image" title="reload captcha" alt="captcha" src="'.$GLOBALS['site']['path'].'captcha?id='.$this->captchaId.'&amp;time='.time().'" onclick="this.src = \''.$GLOBALS['site']['path'].'captcha?id='.$this->captchaId.'&amp;time=\' + ((new Date()).getTime());" />';
					$result .= '<br />';
					$result .= '<input type="text" id="'.$this->name().'" name="'.$this->name().'" value="" '.$this->attributesToString('element').' />';
				}
			}
			else
				$result = parent::renderPart($part);
			return $result;
		}
	}

	class FormElement_select extends FormElement
	{
		public					$available			= array();
		public					$multiple			= false;
		public					$first				= null;

		public function construct($id = null, $label = null, $available = array(), $multiple = false)
		{
			$this->id = $id;
			$this->label = $label;
			$this->available = $available;
			$this->multiple = $multiple;
		}

		public function load()
		{
			if (!is_array($this->default))
				$this->default = array($this->default);
			parent::load();
		}

		public function loadValue()
		{
			$form = $this->form();
			$value = @$form->valuesIn[$this->name()];
			if ($this->multiple)
			{
				$this->value = array();
				foreach ($value as $item)
					if (isset($this->available[$item]))
						$this->value[] = $item;
			}
			else
				if (isset($this->available[$value]))
					$this->value = array($value);
			$this->first = (isset($this->value[0]) ? $this->value[0] : null);
			$form->values[$this->name()] = $this->value;
			$form->values[$this->name().'|first'] = $this->first;
		}

		public function renderPart($part = null)
		{
			if ($part == 'element')
			{
				if ($this->view)
				{
					$result = array();
					foreach ($this->valueDisplay as $option)
						if (isset($this->available[$option]))
							$result[] = HTML::e($this->available[$option]);
					$result = implode($this->get('view-separator', ', '), $result);
				}
				else
				{
					if ($this->readonly)
						$this->attributeSet('disabled', true);
					if ($this->disabled)
						$this->attributeSet('disabled', true);
					if ($this->multiple)
					{
						$this->attributeSet('multiple', 'multiple');
						/*if (!$this->attributeGet('size'))
							$this->attributeSet('size', 5);*/
					}
					$result = '<select id="'.$this->name().'" name="'.$this->name().($this->multiple ? '[]' : '').'" '.$this->attributesToString('element').'>';
					foreach ($this->available as $k => $v)
						$result .= '<option value="'.HTML::e($k).'"' . (in_array($k, $this->valueDisplay) ? ' selected="selected"' : '') . '>' . HTML::e($v) . '</option>';
					$result	.= '</select>';
				}
			}
			else
				$result = parent::renderPart($part);
			return $result;
		}
	}

	class FormGroup extends FormElement
	{
		public					$items				= array();

		public function type()
		{
			return 'group';
		}

		/*public function __get($key)
		{
			return $this->items[$key];
		}

		public function __set($key, $value)
		{
			$this->items[$key] = $value;
		}*/

		public function i($key)
		{
			return (isset($this->items[$key]) ? $this->items[$key] : null);
		}

		public function find($id)
		{
			if (isset($this->items[$id]))
				return $this->items[$id];
			foreach ($this->items as $item)
				if ($item instanceof FormGroup)
					if ($temp = $item->find($id))
						return $temp;
			return null;
		}

		public function valueOf($id)
		{
			$element = $this->find($id);
			if (!$element)
				return null;
			return $element->value;
		}

		public function items()
		{
			return new ContainerIterator($this, array_keys($this->items), 'i');
		}

		public function itemsRecursive()
		{
			$result = array();
			foreach ($this->items as $item)
			{
				$result[$item->id] = $item;
				if ($item instanceof FormGroup)
					$result = array_merge($result, $item->itemsRecursive());
			}
			return $result;
		}

		public function elements()
		{
			$keys = array();
			foreach ($this->items as $key => $item)
				if (!($item instanceof FormGroup))
					$keys[] = $key;
			return new ContainerIterator($this, $keys, 'i');
		}

		public function elementsRecursive()
		{
			$result = array();
			foreach ($this->items as $key => $item)
				if ($item instanceof FormGroup)
					$result = array_merge($result, $item->elementsRecursive());
				else
					$result[$key] = $item;
			return $result;
		}
		
		public static function arrayOfItemsIds($items)
		{
			$result = array();
			foreach ($items as $item)
				$result[] = $item->id;
			return $result;
		}

		public function groups()
		{
			$keys = array();
			foreach ($this->items as $key => $item)
				if ($item instanceof FormGroup)
					$keys[] = $key;
			return new ContainerIterator($this, $keys, 'i');
		}

		public function add($item)
		{
			if (!is_object($item))
			{
				$class = 'FormElement_' . $item;
				$args = func_get_args();
				$item = new $class();
				$item->group = $this;
				call_user_func_array(array($item, 'construct'), array_slice($args, 1));
			}
			$item->group = $this;
			$this->items[$item->id] = $item;
			if (!$item->object && $this->object)
				$item->object = $this->object;
			if (!$item->template && $this->template)
				$item->template = $this->template;
			return $item;
		}

		public function applyOne($filter)
		{
			foreach ($this->items() as $item)
				$item->applyOne($filter);
		}

		public function load()
		{
			foreach ($this->items() as $item)
				$item->load();
		}

		public function validate()
		{
			$result = true;
			foreach ($this->items() as $item)
				$result = $item->validate() && $result;
			$result = parent::validate() && $result;
			return $result;
		}

		public function reorder()
		{
			$all = array();
			foreach ($this->items as $k => $v)
				$all[$k] = array('id' => $v->id, 'before' => $v->get('before'), 'after' => $v->get('after'));
			$remaining = $all;
			$order = array();
			$order['^'] = null;
			foreach ($all as $k => $v)
				if (!$v['before'] && !$v['after'])
				{
					$order[$k] = $all[$k];
					unset($remaining[$k]);
				}
			$order['$'] = null;

			$work = true;
			while ($work)
			{
				$work = false;
				foreach ($remaining as $k => $v)
				{
					if ($v['before'] && isset($order[$v['before']]))
					{
						$order = array_insert($order, $v['before'], array($k => $v), true);
						unset($remaining[$k]);
						$work = true;
					}
					if ($v['after'] && isset($order[$v['after']]))
					{
						$order = array_insert($order, $v['after'], array($k => $v), false);
						unset($remaining[$k]);
						$work = true;
					}
				}
			}
			unset($order['^']);
			unset($order['$']);

			$old = $this->items; $this->items = array();
			foreach ($order as $k => $v)
				$this->items[$k] = $old[$k];
		}

		public function renderPart($part = null)
		{
			$result = '';
			if ($part === null)
			{
				$result .= $this->renderPrototype('begin');
				$result .= $this->renderPrototype('additional');
				$result .= $this->renderPrototype('main');
				$result .= $this->renderPrototype('end');
			}
			else if ($part == 'begin')
			{
				if ($this->get('hide-empty') && (count($this->items) == 0))
					;
				else
				{
					$form = $this->form();
					$layout_container = new HTMLElement();
					if ($this->layout == 'template')
						$layout_container->name = 'div';
					else if (($this->layout == 'horizontal') || ($this->layout == 'vertical'))
						$layout_container->name = 'table';
					else
						$layout_container->name = 'fieldset';
					$layout_container->attributeSet('class', 'layout_container layout_'.$this->layout.' ' . $this->name() . ' ' . $this->get('layout-container-class'));
					$style = '';
					if ($this->get('hidden') || ($this->name() == '_hidden'))
						$style .= 'display: none;';
					$layout_container->attributeSet('style', $this->get('layout-container-style', $style));
					$layout_container->attributeSet('id', $this->get('layout-container-id', $form->name() . '_' . $this->name()));

					$result = $layout_container->renderOpen();

					if ($this->layout == 'default')
					{
						$id = $layout_container->attributeGet('id');
						$label = $this->label();
						if ($label)
						{
							$result .= '<legend style="cursor: pointer;" onclick="$(\'#'.$id.'_content\').toggle();">'.HTML::e($label).'</legend>';
							$result .= '<div class="legend" style="cursor: pointer;" onclick="$(\'#'.$id.'_content\').toggle();">'.HTML::e($label).'</div>';
						}
						$result .= '<div id="'.$id.'_content" style="display: '.($this->get('collapsed') ? 'none' : 'block').';">';
					}
				}
			}
			else if ($part == 'end')
			{
				if ($this->get('hide-empty') && (count($this->items) == 0))
					;
				else if ($this->layout == 'template')
					$result = '</div>';
				else if (($this->layout == 'horizontal') || ($this->layout == 'vertical'))
					$result = '</table>';
				else
					$result = '</div></fieldset>';
			}
			else if ($part == 'main')
			{
				$this->reorder();
				if ($this->get('hide-empty') && (count($this->items) == 0))
					;
				else if ($this->layout == 'template')
				{
					$result = $this->get('template');
					$args = array();
					foreach ($this->items() as $item)
						$args[$item->id] = $item->render();
					$result = U::pseudoSmarty($result, $args);
				}
				else if ($this->layout == 'horizontal')
				{
					$layout_group = new HTMLElement('tr');
					$layout_group->attributeSet('class', 'layout_group ' . $this->get('layout-group-class'));
					$layout_group->attributeSet('style', $this->get('layout-group-style'));
					$layout_group->attributeSet('id', $this->get('layout-group-id'));

					$result .= $layout_group->renderOpen();
					foreach ($this->items() as $item)
					{
						$layout_item = new HTMLElement('td');
						$layout_item->attributeSet('class', 'layout_item ' . $item->get('layout-item-class'));
						$layout_item->attributeSet('style', $item->get('layout-item-style'));
						$layout_item->attributeSet('id', $item->get('layout-item-id'));

						$result .= $layout_item->renderOpen();
						$result .= $item->render();
						$result .= $layout_item->renderClose();
					}
					$result .= $layout_group->renderClose();
				}
				else if ($this->layout == 'vertical')
				{
					foreach ($this->items() as $item)
					{
						$layout_group = new HTMLElement('tr');
						$layout_group->attributeSet('class', 'layout_group ' . $item->get('layout-group-class'));
						$layout_group->attributeSet('style', $item->get('layout-group-style'));
						$layout_group->attributeSet('id', $item->get('layout-group-id'));
						$layout_item = new HTMLElement('td');
						$layout_item->attributeSet('class', 'layout_item ' . $item->get('layout-item-class'));
						$layout_item->attributeSet('style', $item->get('layout-item-style'));
						$layout_item->attributeSet('id', $item->get('layout-item-id'));

						$result .= $layout_group->renderOpen();
						$result .= $layout_item->renderOpen();
						$result .= $item->render();
						$result .= $layout_item->renderClose();
						$result .= $layout_group->renderClose();
					}
				}
				else
				{
					foreach ($this->items() as $item)
					{
						$layout_item = new HTMLElement('div');
						$layout_item->attributeSet('class', 'layout_item ' . $item->get('layout-item-class'));
						$layout_item->attributeSet('style', $item->get('layout-item-style'));
						$layout_item->attributeSet('id', $item->get('layout-item-id'));

						$result .= $layout_item->renderOpen();
						$result .= $item->render();
						$result .= $layout_item->renderClose();
					}
				}
			}
			else if ($part == 'additional')
			{
				if (($this->layout == 'horizontal') || ($this->layout == 'vertical'))
					$result .= '<tr><td>';
				$result .= $this->renderPrototype('specific');
				//$result .= $this->renderPrototype('tooltip');
				$result .= $this->renderPrototype('javascripts');
				$result .= $this->renderPrototype('errors');
				$result .= $this->renderPrototype('notes');
				if (($this->layout == 'horizontal') || ($this->layout == 'vertical'))
					$result .= '</td></tr>';
			}
			else
				$result = parent::renderPart($part);
			return $result;
		}
	}

	function stripslashes_deep($value)
	{
		$value = is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
		return $value;
	}

	class Form extends FormGroup
	{
		public					$invocation			= '';
		public					$action				= '';
		public					$method				= 'post';
		public					$submittedName		= null;
		public					$valuesIn			= array();
		public					$values				= array();
		public					$display			= true;
		public					$continue			= false;

		public function construct($id = null)
		{
			$this->id = $id;
			$this->submittedName = '__form_submitted_' . $this->id;
			$group_hidden = $this->add(new FormGroup('_hidden'));
			$group_hidden->set('style', 'display: none;');
			$group_main = $this->add(new FormGroup('_main'));
			$group_main->set('hide-empty', true);
			$group_submit = $this->add(new FormGroup('_submit'));
			$group_submit->set('hide-empty', true);
		}

		public function type()
		{
			return 'form';
		}

		public function invocation()
		{
			return (isset($GLOBALS['invocations'][$this->invocation]) ? $GLOBALS['invocations'][$this->invocation] : null);
		}

		public function action()
		{
			if (!$this->invocation)
				return null;
			return $this->invocation()->action();
		}

		public function module()
		{
			if (!$this->invocation)
				return null;
			return $this->action()->module();
		}

		public function loadValuesIn()
		{
			if ($this->valuesIn)
				return;
			if ($this->method == 'get')
				$this->valuesIn = $_GET;
			else
				$this->valuesIn = array_merge($_POST, $_FILES);
			if (get_magic_quotes_gpc())
				$this->valuesIn = stripslashes_deep($this->valuesIn);
		}

		public function load()
		{
			$this->loadValuesIn();
			parent::load();
		}

		public function submitted()
		{
			return (bool) @$this->valuesIn[$this->submittedName];
		}

		public function validate()
		{
			$this->load();
			if ($this->submitted())
				return parent::validate();
			else
				return false;
		}

		public function render()
		{
			if (!class_exists('Template'))
				return $this->renderPrototype(null);
			
			if (($this->template !== false) && !is_object($this->template))
			{
				$template = '';
				if ($this->id)
					$template .= $this->id;
				else if ($action = $this->action())
					$template .= $action->module()->id . '/' . $action->id;
				$template = strtr($template, '/', '-') . '-form';
				//$template = ':'.strtr($template, '/', ':') . '-form';
			}
			else
				$template = $this->template;
			if ($template && !is_object($template))
				$template = Template::prepare($template); //new Template(null, $template);
			if ($template)
				$result = $template->process(array('form' => $this));
			else
				$result = $this->renderPrototype(null);
			return $result;
		}

		public function renderPart($part = null)
		{
			$result = '';
			if ($part === null)
			{
				$result = parent::renderPart($part);
			}
			else if ($part == 'begin')
			{
				$this->load();
				
				$this->set('enctype', 'application/x-www-form-urlencoded');
				foreach ($this->elementsRecursive() as $element)
					if ($element->type() == 'file')
						$this->set('enctype', 'multipart/form-data');
				if (isset($GLOBALS['page']) && $GLOBALS['page']['administration'])
				{
					if ($GLOBALS['page']['panel'])
						$this->action .= '&page.panel=' . $GLOBALS['page']['panel'];
					/*if ($GLOBALS['page']['viewstate'])
						$this->action .= '&page.viewstate=' . $GLOBALS['page']['viewstate'];*/
				}
				$result .= '<form action="'.HTML::e($this->action).'" method="'.$this->method.'" enctype="'.$this->get('enctype').'" name="'.$this->name().'" id="'.$this->id().'" '.$this->attributesToString('form').'><div><input type="hidden" name="'.$this->submittedName.'" value="1" />';
			}
			else if ($part == 'end')
				$result = '</div></form>';
			else
				$result = parent::renderPart($part);
			return $result;
		}

		public function renderElement($id, $ignore = false, $part = null)
		{
			$element = $this->find($id);
			if (!$element && ($ignore === false))
				return '[[ERROR: '.$id.']]';
			if (!$element && ($ignore === 1))
				return '<span style="color: gray;">' . __('unavailable') . '</span>';
			if (!$element)
				return '';
				
			if ($part === null)
				return $element->render();
			else
				return $element->renderPrototype($part);
		}
		
		public function renderRemainingElements()
		{
			$result = '';
			$elements = $this->elementsRecursive();
			foreach ($elements as $element)
				if (!isset($element->rendered['element']) || !$element->rendered['element'])
					$result .= $element->render();
			return $result;
		}

		public function autoHeader($text = null, $postfix = '')
		{
			if (!$text && $this->invocation)
				$text = __($this->action()->id . '-header', null, false);
			if (!$text && $this->invocation)
				$text = __($this->action()->id, null, false);
			$this->i('_main')->label = $text . $postfix;
		}

		public function autoSubmit($text = null, $name = null)
		{
			if (!$text && $this->invocation)
				$text = __($this->action()->id . '-submit', null, false);
			if (!$text)
				$text = __('submit', null, false);
			$name = ($name !== null) ? $name : 'submit';
			$element = $this->i('_submit')->add('submit', $name, $text);
			return $element;
		}
	}

	class FormFilter
	{
		//public					$options			= array();

		public function __construct()
		{
			$args = func_get_args();
			call_user_func_array(array($this, 'construct'), $args);
		}

		public function construct()
		{
		}

		/*public function get($key, $value = null)
		{
			return (isset($this->options[$key]) ? $this->options[$key] : $value);
		}

		public function set($key, $value)
		{
			if ($value === null)
				unset($this->options[$key]);
			else
				$this->options[$key] = $value;
		}*/

		public function type()
		{
			return substr(get_class($this), 11);
		}

		public function apply($value, $element)
		{
			return $value;
		}
	}

	class FormFilter_function
	{
		public					$function			= null;

		public function construct($function = null)
		{
			$this->function = $function;
		}

		public function apply($value, $element)
		{
			return callback($this->function, array('value' => $value, 'element' => $element));
		}
	}

	class FormFilter_standard_function extends FormFilter_function
	{
		public					$arg1				= null;
		public					$arg2				= null;
		public					$arg3				= null;

		public function construct($function = null, $arg1 = null, $arg2 = null, $arg3 = null)
		{
			$this->function = $function;
			$this->arg1 = $arg1;
			$this->arg2 = $arg2;
			$this->arg3 = $arg3;
		}

		public function apply($value, $element)
		{
			$_x_function = $this->function;
			if ($this->arg3)
				return $_x_function($value, $this->arg1, $this->arg2, $this->arg3);
			else if ($this->arg2)
				return $_x_function($value, $this->arg1, $this->arg2);
			else if ($this->arg1)
				return $_x_function($value, $this->arg1);
			else
				return $_x_function($value);
		}
	}

	class FormRule
	{
		public					$message			= null;

		public function message($default = null)
		{
			if ($this->message)
				return $this->message;
			if ($default)
				return $default;
			return '???';
		}

		public function __construct()
		{
			$args = func_get_args();
			call_user_func_array(array($this, 'construct'), $args);
		}

		public function construct($message = null)
		{
			$this->message = $message;
		}

		public function type()
		{
			return str_replace('FormRule_', '', get_class($this));
		}
	}

	class FormElementRule extends FormRule
	{

	}

	class FormRule_required extends FormElementRule
	{
		public function validate($value, $element)
		{
			if ($value === '')
			{
				$element->errors[] = $this->message(__('rule-required', '@core'));
				return false;
			}
			return true;
		}
	}

	class FormRule_regex extends FormElementRule
	{
		public					$expression			= null;

		public function construct($message = null, $expression = null)
		{
			$this->message = $message;
			$this->expression = $expression;
		}

		public function validate($value, $element)
		{
			if (!preg_match($this->expression, $value))
			{
				$element->errors[] = $this->message();
				return false;
			}
			return true;
		}
	}

	class FormRule_email extends FormRule_regex
	{
		public function construct($message = null)
		{
			$this->message = $message;
			if (!$this->message)
				$this->message = __('rule-email', '@core');
			$this->expression = '/^((\"[^\"\f\n\r\t\v\b]+\")|([\w\!\#\$\%\&\'\*\+\-\~\/\^\`\|\{\}]+(\.[\w\!\#\$\%\&\'\*\+\-\~\/\^\`\|\{\}]+)*))@((\[(((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9])))\])|(((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9])))|((([A-Za-z0-9\-])+\.)+[A-Za-z\-]+))$/D';
		}
	}

	class FormRule_id extends FormRule_regex
	{
		public function construct($message = null)
		{
			$this->message = $message;
			if (!$this->message)
				$this->message = __('rule-id', '@core');
			$this->expression = '/^[a-zA-Z0-9_@\.-]+$/D';
		}
	}

	class FormRule_numeric extends FormRule_regex
	{
		public function construct($message = null)
		{
			$this->message = $message;
			if (!$this->message)
				$this->message = __('rule-numeric', '@core');
			$this->expression = '/(^-?\d\d*\.\d*$)|(^-?\d\d*$)|(^-?\.\d\d*$)/D';
		}
	}

	class FormRule_integer extends FormRule_regex
	{
		public function construct($message = null)
		{
			$this->message = $message;
			if (!$this->message)
				$this->message = __('rule-integer', '@core');
			$this->expression = '/[-+]?\d+/D';
		}
	}
	
	class FormRule_callback extends FormElementRule
	{
		public					$callback			= null;

		public function construct($message = null, $callback = null)
		{
			$this->message = $message;
			$this->callback = $callback;
		}

		public function validate($value, $element)
		{
			$result = $this->callback->evaluate(array(
				'value' => $value,
				'element' => $element,
				'rule' => $this,
			));
			
			if (!$result)
			{
				$element->errors[] = $this->message();
				return false;
			}
			
			return true;
		}
	}

	class FormRule_length extends FormElementRule
	{
		public					$min				= null;
		public					$max				= null;

		public function construct($message = null, $min = null, $max = null)
		{
			$this->message = $message;
			$this->min = $min;
			$this->max = $max;
		}

		public function validate($value, $element)
		{
			$length = mb_strlen($value);
			if ($this->max === null)
			{
				if ($length < $this->min)
				{
					$element->errors[] = $this->message(sprintf(__('rule-length-min', '@core'), $this->min));
					return false;
				}
			}
			else if (($this->min === null))
			{
				if ($length > $this->max)
				{
					$element->errors[] = $this->message(sprintf(__('rule-length-max', '@core'), $this->max));
					return false;
				}
			}
			else
			{
				if (($length < $this->min) || ($length > $this->max))
				{
					$element->errors[] = $this->message(sprintf(__('rule-length', '@core'), $this->min, $this->max));
					return false;
				}
			}
			return true;
		}
	}

	class FormRule_compare extends FormElementRule
	{
		public					$value				= null;
		public					$operator			= null;

		public function construct($message = null, $value = null, $operator = '==')
		{
			$this->message = $message;
			$this->value = $value;
			$this->operator = $operator;
		}

		public function validate($value, $element)
		{
			if (!U::compare($value, $this->value, $this->operator))
			{
				$op = __('operator'.$this->operator, '@core');
				$m = sprintf(__('rule-compare', '@core'), $op, (string) $this->value);
				$element->errors[] = $this->message($m);
				return false;
			}
			return true;
		}
	}

	class FormRule_compare_element extends FormRule_compare
	{
		public function validate($value, $element)
		{
			$value2 = $element->form()->find($this->value);
			if ($value2)
				$value2 = $value2->value;
			if (!U::compare($value, $value2, $this->operator))
			{
				$op = __('operator'.$this->operator, '@core');
				$m = sprintf(__('rule-compare_element', '@core'), $op, (string) $this->value);
				$element->errors[] = $this->message($m);
				return false;
			}
			return true;
		}
	}

	class FormRule_unique extends FormElementRule
	{
		public					$elements			= null;

		public function construct($message = null, $elements = null)
		{
			$this->message = $message;
			$this->elements = $elements;
		}

		public function validate($value, $element)
		{
			$form = $element->form();
			if (!$this->elements)
				$elements = array($element);
			else
			{
				$elements = array();
				foreach ($this->elements as $e)
					if (is_object($e))
						$elements[$e->id] = $e;
					else
						$elements[$e] = $form->find($e);
			}

			$filter = array();
			if ($element->object->ostatus != 'new')
				$filter[] = Fnot(Feq('oid', $element->object->oid));
			foreach ($elements as $k => $e)
			{
				if (!$e)
				{
					if (!isset($element->object->fields()->$k))
						$k = substr($k, strlen($element->object->i) + 1);
					$elements[$k] = $e = new FormElement_hidden($k, $element->object->$k);
					$e->object = $element->object;
					$e->field = $e->object->fields()->$k;
					$e->label = $e->field->label();
					$e->value = $e->object->$k;
				}
				if (!$e->field)
				{
					$element->errors[] = 'invalid element field: ' . $e->id;
					return false;
				}
				else
					$filter[] = Feq($e->field->id, $e->value);
			}
			$count = $element->object->count(new Fand($filter));
			if ($count)
			{
				$m = array();
				foreach ($elements as $e)
					$m[] = $e->label();
				if (count($m) > 1)
					$m = sprintf(__('rule-unique', '@core')) . ': ' . implode(', ', $m);
				else
					$m = sprintf(__('rule-unique-one', '@core')) . ': ' . implode(', ', $m);
				$element->errors[] = $this->message($m);
				return false;
			}
			return true;
		}
	}

	class FormRule_file_size extends FormElementRule
	{
		public					$max				= null;

		public function construct($message = null, $max = null)
		{
			$this->message = $message;
			$this->max = $max;
		}

		public function validate($value, $element)
		{
			if (($element->error == UPLOAD_ERR_FORM_SIZE) || ($element->error == UPLOAD_ERR_INI_SIZE))
			{
				$this->errors[] = $this->message(__('rule-file-limit-size'));
				return false;
			}
			else if ($element->error == UPLOAD_ERR_OK)
			{
				if ($element->size > $this->max)
				{
					$this->errors[] = $this->message(__('rule-file-limit-size'));
					return false;
				}
			}
			return true;
		}
	}

	class FormRule_file_type extends FormElementRule
	{
		public					$types				= null;

		public function construct($message = null, $types = null)
		{
			$this->message = $message;
			$this->types = $types;
		}

		public function typesText()
		{
			$result = array();
			foreach ($types as $type)
				$result[] = __('rule-file-limit-type-'.$type, '@core');
			return implode(', ', $result);
		}

		public function validate($value, $element)
		{
			if ($element->error == UPLOAD_ERR_OK)
			{
				$ok = false;
				foreach ($this->types as $type)
					if ($element->type == $type)
						$ok = true;
				if (!$ok)
				{
					$this->errors[] = $this->message(__('rule-file-limit-type'));
					return false;
				}
			}
			return true;
		}
	}

	class FormRule_file_typeregex extends FormRule_file_type
	{
		public function validate($value, $element)
		{
			if ($element->error == UPLOAD_ERR_OK)
			{
				$ok = false;
				foreach ($this->types as $type)
					if (preg_match('~^'.$type.'$~', $element->type))
						$ok = true;
				if (!$ok)
				{
					$this->errors[] = $this->message(__('rule-file-limit-type'));
					return false;
				}
			}
			return true;
		}
	}
?>