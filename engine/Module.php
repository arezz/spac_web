<?php
	// Author: Jakub Macek, CZ; Copyright: Poski.com s.r.o.; Code is 100% my work. Do not copy.

	class AdministrationMenu
	{
		public static			$container							= array();
		
		public static function addItem($invocation, $moduleId, $groupId = 'default')
		{
			if (!isset(self::$container[$groupId]))
				self::$container[$groupId] = array();
			if (!isset(self::$container[$groupId][$moduleId]))
				self::$container[$groupId][$moduleId] = array();
			self::$container[$groupId][$moduleId][] = $invocation;
		}
	}

	class Module
	{
		public					$id									= null;
		public					$acl								= null;
		public					$actions							= array();

		public					$parent								= null;
		public					$children							= array();

		public function __construct() {}
		
		public function name()
		{
			return __('module-name', $this->id);
		}

		public function initialize($phase)
		{
			// $phase == 4		$this->action('xxx')->set('x', 'y');
			// $phase == 6		Setting::register()
			// $phase == 9		callbacks
			
			if ($phase == 0)
			{
				$this->acl = new acl($this->get('ouid', 'administrator'), $this->get('orid', 'administrator'), $this->get('oacl', array()));
				$this->action('test-data', true);
			}
			
			if ($phase == 9)
			{
				Event::invoke(array("module.initialize", "{$this->id}.initialize"), array('module' => $this));
			}
		}
		
		public function process($phase)
		{
			// $phase == 9		callbacks
			
			if ($phase == 9)
			{
				return Event::invoke(array("module.process", "{$this->id}.process"), array('module' => $this));
			}
		}
		
		public function prepare($invocation, $action, $phase, $result)
		{
			// $phase == 2		preload
			// $phase == 4		$invocation->set('x', 'y');
			// $phase == 7		check
			// $phase == 9		callbacks
			
			$result = null;
			
			if ($phase == 7)
			{
				if ($result === null)
				{
					switch ($action->id)
					{
						case 'test-data':
							$result = (USER == 'administrator') && $GLOBALS['site']['development'] && $action->get('present');
							break;
						default:
							$result = (U::acl($invocation, $this->acl) ? true : null);
							break;
					}
				}
			}			
			
			if ($phase == 9)
			{
				$temp = Event::invoke(array("module.action.check", "{$this->id}.action.check", "module.{$action->id}.check", "{$this->id}.{$action->id}.check"), array('module' => $this, 'action' => $action, 'invocation' => $invocation));
				if ($temp !== null)
					$result = $temp;
			}

			return $result;
		}
		
		public function action($id, $value = null)
		{
			if ($value === true)
			{
				$this->actions[$id] = new Action($this->id, $id);
				acl::add($this->id, $id);
			}
			return (isset($this->actions[$id]) ? $this->actions[$id] : null);
		}

		public function select($value = null, $values = array())
		{
			if ($value === null)
				return $values;
			else if (isset($values[$value]))
				return $values[$value];
			else
				return false;
		}
		
		public function generateAdministrationMenu()
		{
		}

		public function get($key, $value = null)
		{
			if (isset($GLOBALS['settings'][$this->id][$key]))
				return $GLOBALS['settings'][$this->id][$key];
			else
				return $value;
		}

		public function set($key, $value)
		{
			if ($value === null)
				unset($GLOBALS['settings'][$this->id][$key]);
			else
				$GLOBALS['settings'][$this->id][$key] = $value;
		}

		public function setIfNotSet($key, $value)
		{
			if (!isset($GLOBALS['settings'][$this->id][$key]))
				$GLOBALS['settings'][$this->id][$key] = $value;
		}

		public function getS($key, $value = null)
		{
			$full_name = $this->id . '/' . $key;
			return Session::get($full_name, $value);
		}

		public function setS($key, $value)
		{
			$full_name = $this->id . '/' . $key;
			Session::set($full_name, $value);
		}

		public function getV($key, $value = null)
		{
			$full_name = $this->id . '/' . $key;
			return ViewState::get($full_name, $value);
		}

		public function setV($key, $value)
		{
			$full_name = $this->id . '/' . $key;
			ViewState::set($full_name, $value);
		}

		public function url($action, $options = array(), $type = null)
		{
			return Router::url($this->id.'/'.$action, $options);
		}
		
		public function index($options = array())
		{
			//error("module '{$this->id}' doesn't implement index");
		}
		
		public function page($options = array())
		{
			//error("module '{$this->id}' doesn't implement page");
		}
		
		public function setting($field)
		{
			Setting::register(new Setting($this->id, $field));
			return $field;
		}
		
		public function mainTemplate($part = null)
		{
			$tpl_name = ':' . $this->id;
			if ($part === null)
				$part = '@' . $GLOBALS['page']['file'];
			if ($part)
				$tpl_name .= ':' . $part;
			Core::frame($this->get('template', $tpl_name), ':');
		}
		
		public function mainTemplate0($part = null)
		{
			$this->mainTemplate($part);
			$GLOBALS['templates'][0] = $GLOBALS['templates']['main'];
			unset($GLOBALS['templates']['frame']); 
			unset($GLOBALS['templates']['main']); 
		}
		
		protected function actionPrepareActions($invocation, $action, $actions)
		{
			$result = array();
			foreach ($actions as $aid)
			{
				if (is_string($aid))
				{
					$modifiers = preg_replace('/^(\W*).*$/', '\1', $aid);
					$aid = preg_replace('/^(\W*)/', '', $aid);
					$i = new Invocation($aid, $this->id);
				}
				else
				$i = $aid;
				$result[] = $i;
			}
			return $result;
		}

		public function actionTestDataFetch($invocation, $action, $type, $args = array())
		{
			$index = $invocation->get('data-index', array());
			if (!$index)
			{
				$index = @file_get_contents($GLOBALS['site']['test-data-url']);
				if (!$index)
					die('test data not available');
				else
					$index = @json_decode($index, true);
				if (!$index)
					die('test data not available');
				else
					$invocation->set('data-index', $index);
			}
			
			$selected = array();
			foreach ($index as $item)
			{
				if ($item['type'] != $type)
					continue;
				$okay = true;
				$argstemp = $args;
				while ($okay && ($key = array_shift($argstemp)))
				{
					$operation = array_shift($argstemp);			
					$value = array_shift($argstemp);
					if ($operation == '==')
						$okay = ($item[$key] == $value);
					else if ($operation == '!=')
						$okay = ($item[$key] != $value);
					else if ($operation == '>')
						$okay = ($item[$key] > $value);
					else if ($operation == '<')
						$okay = ($item[$key] < $value);
					else if ($operation == '>=')
						$okay = ($item[$key] >= $value);
					else if ($operation == '<=')
						$okay = ($item[$key] <= $value);
				}
				
				if ($okay)
					$selected[] = $item;
			}
			
			if ($selected)
			{
				$temp = array_slice($selected, mt_rand(0, count($selected) - 1), 1);
				list($item) = $temp;
				
				$result = new stdClass();
				$result->item = $item;
				$result->result = file_get_contents($GLOBALS['site']['test-data-url'] . $item['file']);
				return $result; 
			}
			else
				return null;
		}
		
		public function actionTestData($invocation, $action)
		{
			$invocation->output(__('not-implemented', 'code'));
		}		
	}
?>