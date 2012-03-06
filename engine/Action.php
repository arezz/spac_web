<?php
	// Author: Jakub Macek, CZ; Copyright: Poski.com s.r.o.; Code is 100% my work. Do not copy.

	class Action
	{
		public					$id									= null;
		public					$module								= null;
		public					$method								= '';

		public					$options							= array();
		public					$optionsUnsafe						= array('oid', 'pid', 'id');
		public					$mappings							= array();
		public					$context							= null;
		
		public static function find($action, $module = null)
		{
			if (!is_object($action))
			{
				$temp = strpos($action, '/');
				$module = ($temp !== false) ? substr($action, 0, $temp) : $module;
				$action = ($temp !== false) ? substr($action, $temp + 1) : $action;
				if ($temp0 = m($module))
					$action = $temp0->action($action);
				else
					$action = null;
			}
			if (!($action instanceof Action))
				return null;
			return $action;
		}

		public function __construct($module, $id, $options = array())
		{
			$this->id = $id;
			$this->module = $module;
			$this->options = $options;
			$this->method = U::dashToUpper($this->id);
			$this->method = 'action' . strtoupper(substr($this->method, 0, 1)) . substr($this->method, 1);
			
			$this->mappings['pid'] = new E('return ((isset($parent) && $parent) ? $parent->oid : $invocation->get("pid"));');
			$this->mappings['oid'] = new E('return ((isset($object) && $object) ? $object->oid : $invocation->get("oid"));');
		}

		public function module()
		{
			return m($this->module);
		}

		public function prefix($text = '')
		{
			return $this->module . '-' . $this->id . '-' . $text;
		}

		public function icon()
		{
			if ($icon = $this->get('icon'))
				return $icon;
			foreach (array($this->id, $this->module.'_'.$this->id) as $icon)
				if (U::firstExistingFile($GLOBALS['site']['base'], ($temp = 'web/_administration/icons/' . $icon . '.png')))
					return $icon;
			return null;
		}

		public function set($key, $value = null)
		{
			$this->options[$key] = $value;
		}

		public function get($key, $value = null)
		{
			$result = $this->module()->get($this->id . '-' . $key);
			if ($result !== null)
				return $result;
			return isset($this->options[$key]) ? $this->options[$key] : $value;
		}

		public function getS($key, $default = null)
		{
			return $this->module()->getS($this->id . '-' . $key, $default);
		}

		public function setS($key, $value)
		{
			$this->module()->setS($this->id . '-' . $key, $value);
		}

		public function getV($key, $default = null)
		{
			return $this->module()->getV($this->id . '-' . $key, $default);
		}
		
		public function setV($key, $value)
		{
			$this->module()->setV($this->id . '-' . $key, $value);
		}

		public function __($message)
		{
			return __($this->id . '-' . $message, $this->module);
		}
	}

	class Invocation
	{
		const					STATUS_CHECK						= -2;
		const					STATUS_PREPARE						= -2;
		const					STATUS_ERROR						= -1;
		const					STATUS_OK							= 0;
		const					STATUS_SUCCESS						= 1;

		public					$id									= null;
		public					$invoker							= null;
		public					$action								= null;
		public					$module								= null;

		public					$active								= false;
		public					$status								= self::STATUS_OK;

		public					$options							= array();
		public					$mappings							= array();

		public					$messages							= array();
		public					$output								= array(0 => '');

		public					$icon								= null;
		public					$text								= null;
		public					$context							= null;
		public					$panel								= null;

		public static function instantiate($action, $module = null, $text = null, $id = null)
		{
			return new Invocation($action, $module, $text, $id);
		}

		public function __construct($action, $module = null, $text = null, $id = null, $invoker = null)
		{
			$a = Action::find($action, $module);
			$this->action = $a->id;
			$this->module = $a->module;
			$this->text = $text;
			$this->id = $id;
			$this->invoker = $invoker;
			if (!$this->id)
				$this->id = U::randomString(16, 'abcdefghijklmnopqrstuvwxyz');
			if (!isset($GLOBALS['invocations'][$this->id]))
				$GLOBALS['invocations'][$this->id] = $this;

			if ($this->action)
				$this->context = $this->action()->context;
		}

		public function action()
		{
			return a($this->action, $this->module);
		}

		public function module()
		{
			return $this->action()->module();
		}

		public function invoker()
		{
			return @$GLOBALS['invocations'][$this->invoker];
		}

		public function icon()
		{
			if ($this->icon)
			{
				foreach (array($this->icon, $this->module()->id.'_'.$this->icon) as $icon)
					if (U::firstExistingFile($GLOBALS['site']['base'], ($temp = 'web/_administration/icons/' . $icon . '.png')))
						return $icon;
				return null;
			}
			else
				return $this->action()->icon();
		}

		public function log($message)
		{
			$action = $this->action();
			U::log('modules', $action->module()->id . '/' . $action->id . ' (' . $this->id . ')', $message);
		}

		public function status($status, $result = null)
		{
			$this->status = $status;
			return $result;
		}

		public function error($message = null, $result = null)
		{
			if ($message === null)
				$message = __('unspecified-error', '@core');
			if ($message)
				$this->message($message, 'error');
			$this->status = self::STATUS_ERROR;
			return $result;
		}

		public function set($key, $value = null)
		{
			$this->options[$key] = $value;
		}

		public function get($key, $value = null)
		{
			if (isset($this->options[$key]))
				return $this->options[$key];
			else
				return $this->action()->get($key, $value);
		}

		public function getS($key, $default = null)
		{
			return $this->action()->getS($key, $default);
		}

		public function setS($key, $value)
		{
			$this->action()->setS($key, $value);
		}

		public function getV($key, $default = null)
		{
			return $this->action()->getV($key, $default);
		}

		public function setV($key, $value)
		{
			$this->action()->setV($key, $value);
		}
		
		public function message($text, $type = 'information')
		{
			$this->messages[] = array('type' => $type, 'text' => __($text, $this->id));
		}

		public function output($x, $block = 0)
		{
			if (is_object($x) && method_exists($x, 'output'))
				$o = $x->output();
			else if (is_object($x) && method_exists($x, 'toHtml'))
				$o = $x->toHtml();
			else if (is_object($x) && method_exists($x, 'toString'))
				$o = $x->toString();
			else
				$o = (string) $x;
			if (!isset($this->output[$block]))
				$this->output[$block] = '';
			$this->output[$block] .= $o;
		}

		public function dispatch($options = array(), $arguments = array(), $activate = false, $clone = false)
		{
			if ($clone)
			{
				$clone = clone($this);
				return $clone->dispatch($options, $arguments, $activate);
			}

			$result = null;
			if ($activate)
				$this->active = true;
			$this->status = self::STATUS_OK;
			if (!$this->active)
				return $result;

			if (!$this->prepare($options, $arguments, false))
			{
				$this->status = self::STATUS_PREPARE;
				if (isset($GLOBALS['invocation']) && ($GLOBALS['invocation'] === $this) && $GLOBALS['page']['administration'])
					$this->message(__('access-denied', '@core'), 'error');
				$result = false;
			}
			else
				$result = $this->call($options, $arguments, false);

			return $result;
		}

		public function prepare($options = array(), $arguments = array(), $clone = true)
		{
			if ($clone)
			{
				$clone = clone($this);
				return $clone->prepare($options, $arguments, false);
			}

			if ($options === null)
				$options = array();
			$this->options = array_merge($this->options, $options);
			$action = $this->action();
			$module = $this->module();
			Locale::module($module->id);

			$callback = $this->get('prepare');
			if (($callback === true) || ($callback === false))
				$result = $callback;
			else if ($callback)
				$result = callback($callback, array_merge(array('invocation' => $this)));
			else
			{
				$result = null;
				for ($phase = 0; $phase < 10; $phase++)
				{
					$temp = $module->prepare($this, $action, $phase, $result);
					if ($temp !== null)
						$result = $temp;
				}
			}

			return $result;
		}

		public function call($options = array(), $clone = true)
		{
			if ($clone)
			{
				$clone = clone($this);
				return $clone->call($options, false);
			}

			if ($options === null)
				$options = array();
			$this->options = array_merge($this->options, $options);
			$action = $this->action();
			$module = $this->module();
			$method = $action->method;
			Locale::module($module->id);

			ob_start();
			$this->output = array(0 => '');

			Event::invoke(array("module.action.before", "{$module->id}.action.before", "module.{$action->id}.before", "{$module->id}.{$action->id}.before"),
				array('invocation' => $this));
			$this->log('call');
			$result = $module->$method($this, $action);
			Event::invoke(array("module.action.after", "{$module->id}.action.after", "module.{$action->id}.after", "{$module->id}.{$action->id}.after"),
				array('action' => $this, 'result' => $result));
			$this->output[0] .= ob_get_clean();

			if (isset($GLOBALS['invocation']) && ($GLOBALS['invocation'] === $this))
				$GLOBALS['result'] = $result;

			return $result;
		}

		public function urlBase($options = array(), $flags = 0)
		{
			if ($options === null)
				$options = array();
			$action = $this->action();
			$module = $this->module();

			if (!($result = $this->get('request-uri-0')))
			{
				$this->options = array_merge($this->options, $options);
				$mappings = array_merge($action->mappings, $this->mappings);
				foreach ($mappings as $key => $expression)
					if ($expression)
						$this->options[$key] = $expression->evaluate(array_merge($this->options, array('invocation' => $this, 'action' => $action, 'module' => $module, '__url' => true)));
				$this->options = array_merge($this->options, $options);

				$result = '&page.invocation=' . $module->id . '/' . $action->id;
				foreach ($this->options as $k => $v)
					if (!is_array($v) && !is_object($v) && !is_null($v))
						$result .= '&' . $k . '=' . urlencode($v);
			}

			for ($i = 1; $i < 10; $i++)
				if ($temp = $this->get('request-uri-'.$i, ''))
					$result .= $temp;

			if ($this->invoker && $this->invoker())
				$result .= $this->invoker()->url();

			return $result;
		}

		public function urlAnchor($result, $text = null, $flags = 0)
		{
			$href = '';
			$panel = (($this->panel === null) ? $GLOBALS['page']['panel'] : $this->panel);
			if (($flags & 1))
			{
				$href = $_SERVER['SCRIPT_NAME'] . '?page.location=' . $GLOBALS['page']['location'];
				if (($panel === false) && $GLOBALS['page']['panel'])
					$href .= '&page.ppanel=' . $GLOBALS['page']['panel'];
				if ($GLOBALS['page']['viewstate'])
					$href .= '&page.viewstate=' . $GLOBALS['page']['viewstate'];
			}
			$href .= $result;

			if (($flags & 2) == 0)
				return $href;

			$action = $this->action();
			$module = $this->module();
			if ($text === null)
				$text = ($this->text ? $this->text : __($action->id, $module->id));
			if ($text === '')
				$text = ' ';

			if (substr($text, 0, 1) == chr(255))
			{
				$text = substr($text, 1);
				$tttt = HTML::e(trim(strip_tags($text)));
			}
			else
				$tttt = $text = HTML::e($text);

			$class = 'link_module_'.$this->module()->id . ' link_action_'.$this->action()->id.' link_module_'.$this->module()->id.'_action_'.$this->action()->id;
			$result = '<a href="' . HTML::e($href) . '" class="'.$class.'"';
			if ($flags & 4)
			{
				if ($panel === false)
					$result .= ' onclick="application.load(this.href); return false;"';
				else if ($panel)
					$result .= ' onclick="application.loadPanel(this.href, \'' . $panel . '\'); return false;"';
			}
			$result .= 'title="' . $tttt . '">';
			if ($icon = $this->icon())
				$result .= '<img src="'.$GLOBALS['site']['path'].'web/_administration/icons/'.$icon.'.png" alt="' . $tttt . '" title="' . $tttt . '" />';
			else
				$result .= $text;
			$result .= '</a>';

			return $result;
		}

		public function url($options = array(), $text = null, $flags = 0, $clone = true)
		{
			if ($clone)
			{
				$clone = clone($this);
				return $clone->url($options, $text, $flags, false);
			}

			if (is_string($text))
				$flags = $flags | 7;
			$result = $this->urlBase($options, $flags);
			$result = $this->urlAnchor($result, $text, $flags);
			return $result;
		}

		public function urlOP($object = null, $parent = null, $text = null, $flags = 0)
		{
			return $this->url(array('object' => $object, 'parent' => $parent), $text, $flags);
		}

		public function prepareOP($object = null, $parent = null)
		{
			return $this->prepare(array('object' => $object, 'parent' => $parent));
		}

		public function form($id = null)
		{
			if ($id === null)
				$id = $this->action()->id;
			$form = new Form($this->module()->id . '/' . $id);
			$form->invocation = $this->id;
			if ($temp = $this->get('form-action'))
				$form->action = $temp;
			else
				$form->action = $this->url(null, null, 1);
			return $form;
		}

		public function forward($options = array())
		{
			$panel = (($this->panel === null) ? $GLOBALS['page']['panel'] : $this->panel);
			$url = $this->url($options, null, 1);

			if ($panel)
			{
				echo '<script type="text/javascript"><!--//--><![CDATA[//><!--' . "\n";
				if ($panel === true)
					echo "\t" . 'setTimeout("application.load(\'' . $url . '\')", 1000);' . "\n";
				else
					echo "\t" . 'setTimeout("application.loadPanel(\'' . $url . '\', \'' . $panel . '\')", 1000);' . "\n";
	      		echo '//--><!]]></script>' . "\n";
			}
			else
				U::redirect($url);
		}

		public function actionsTop($refs, $options = array(), $arguments = array(), $get = array())
		{
			$panel = (($this->panel === null) ? $GLOBALS['page']['panel'] : $this->panel);
			$class = 'actions_top ';
			if ($panel)
				$class .= ' actions_top_' . $panel;
			$output = '';
			$output .= '<div class="'.$class.'">';
			foreach ($refs as $ref)
				$output .= $ref->url($options, null, 7);
			$output .= '</div><!-- /actions_top -->';
			$this->output($output);
		}
	}

	class WebInvocation extends Invocation
	{
		public function url($options = array(), $text = null, $flags = 0, $clone = true)
		{
			if ($flags & 1)
				$flags = $flags - 1;
			return parent::url($options, $text, $flags, $clone);
		}
	}
?>