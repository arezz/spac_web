<?php
	class o_users extends object
	{
		public function event($ev)
		{
			$result = parent::event($ev);
			if ($ev == 'save.before')
			{
				if ($this->id == 'administrator')
					$this->status = 'active';
			}
			return $result;
		}

		public function initialize($phase)
		{
			parent::initialize($phase);

			if ($phase == 0)
			{
				$this->set('order', array('name_last', 'name_first'));
				$this->f('otitle')->get = 'return ($object->id ? ($object->name . " (" . $object->id . ")") : "");';
				$f = $this->define('id', Field::TYPE_STRING, 64, '', array('forms' => array('@users/new')));
				$f->ruleID();
				$f->ruleRequired();
	
				$f = $this->define('password', Field::TYPE_STRING, 32, '', array('forms' => array()));
				//$f = $this->defineTimestamp();
				//$f = $this->define('login_previous', Field::TYPE_DATETIME, null, 0, array('forms' => array()));
				//$f = $this->define('login_this', Field::TYPE_DATETIME, null, 0, array('forms' => array()));
				$f = $this->define('status', Field::TYPE_STRING, 8, 'active', array(
					'forms' => array(),
					'select' => true,
					'values' => array(
						'active' => __('status-active'),
						'blocked' => __('status-blocked'),
					),
				));
	
				$f = $this->define('name_first', Field::TYPE_STRING, 255, '');
				$f = $this->define('name_last', Field::TYPE_STRING, 255, '');
				$f = $this->define('email', Field::TYPE_STRING, 255, '');
				$f = $this->define('phone', Field::TYPE_STRING, 255, '');
				$f = $this->define('im', Field::TYPE_STRING, 255, '');
				$f = $this->define('cookie_expiration', Field::TYPE_INTEGER, null, 0);
				$f = $this->define('cookie', Field::TYPE_STRING, 32, '', array('forms' => array()));
				$f = $this->define('roles', Field::TYPE_STRING, 255, ';administrator;', array('forms' => array()));
	
				$f = $this->defineV('name', Field::TYPE_STRING);
				$f->get = '$result = trim($object->name_last . ", " . $object->name_first, " ,"); return (empty($result) ? $object->id : $result);';
	
				$f = $this->define('roles_array', Field::TYPE_OBJECT, false, array(), null, false);
				$f->get = '$g = trim($object->roles, ";"); if ($g == "") return array(); else return explode(";", $g);';
	
				$f = $this->define('unique_id', Field::TYPE_UNIQUE, null, array('id'));
			}
		}

		public function isRole()
		{
			$args = func_get_args();
			foreach ($args as $arg)
				if (strpos($this->roles, ';' . $arg . ';') !== false)
					return true;
			return false;
		}

		public function isAdministrator()
		{
			if ($this->id == 'administrator')
				return true;
			if ($this->isRole('administrators'))
				return true;
			return false;
		}

		public function isUser()
		{
			if (!$this->id)
				return false;
			return true;
		}
	}

	class users extends ObjectModule
	{
		public					$administrator						= null;
		public					$user								= null;

		public function initialize($phase)
		{
			parent::initialize($phase);

			if ($phase == 0)
			{
				$this->action('mail', true);
				$this->action('test', true);
				$this->action('password', true);
				$this->action('password')->context = 'object';
	
				$this->login();
	
				if (isAdministrator())
					$this->object->f('status')->set('forms', null);
			}
			
			if ($phase == 4)
			{
				$this->action('list')->set('columns', array('id', 'name', 'email'));
				$this->action('list')->set('actions', array('new', 'view', 'edit', 'delete', 'password', 'mail'));
			}

			if ($phase == 6)
			{
				if (isAdministrator())
					$this->object->f('roles')->set('forms', null);
			}
		}

		public function prepare($invocation, $action, $phase, $result)
		{
			$result = parent::prepare($invocation, $action, $phase, $result);

			if ($phase == 7)
			{
				if (!USER)
					return false;
				if ($action->id == 'mail')
					return true;
				if (($action->id == 'delete') && ($invocation->get('object')->id == 'administrator'))
					return false;
				if ($action->id == 'view')
					return true;
				if (($invocation->get('object')->id == 'administrator') && ($this->user->id != 'administrator'))
					return false;
				if (isAdministrator())
					return $result;
				if ($action->id == 'list')
					return true;
				if (($action->id == 'delete') || ($action->id == 'new'))
					return false;
				if ($action->id == 'edit')
					$result = ($this->user->id == $invocation->get('object')->id);
				if ($action->id == 'password')
					$result = ($this->user->id == $invocation->get('object')->id);
			}

			return $result;
		}

		public function setcookies($logout = false)
		{
			if ($logout)
			{
				setcookie('users_id', '', time() - 24*60*60, $GLOBALS['site']['path']);
				setcookie('users_cookie', '', time() - 24*60*60, $GLOBALS['site']['path']);
			}
			else
			{
				setcookie('users_id', $this->user->id, time() + $this->user->cookie_expiration, $GLOBALS['site']['path']);
				setcookie('users_cookie', $this->user->cookie, time() + $this->user->cookie_expiration, $GLOBALS['site']['path']);
			}
		}

		public function login()
		{
			$this->user = clone($this->object);

			if ($GLOBALS['site']['development'] && !$this->object->count(Feq('id', 'administrator')))
			{
				$object = clone($this->object);
				$object->id = 'administrator';
				$object->password = md5('administrator');
				$object->name_first = $GLOBALS['site']['developer']['name_first'];
				$object->name_last = $GLOBALS['site']['developer']['name_last'];
				$object->email = $GLOBALS['site']['developer']['email'];
				$object->im = $GLOBALS['site']['developer']['im'];
				$object->phone = $_SERVER['HTTP_HOST'];
				$object->roles = ';administrator;';
				$object->save();
			}
			$this->administrator = $this->object->load(Feq('id', 'administrator'), true);

			$id = U::request('users_login_id', false);
			$password = U::request('users_login_password', false);
			$hash = U::request('users_login_hash', false);
			unset($_GET['users_login_id']);
			unset($_GET['users_login_password']);
			unset($_GET['users_login_hash']);
			unset($_REQUEST['users_login_id']);
			unset($_REQUEST['users_login_password']);
			unset($_REQUEST['users_login_hash']);

			$temp = @$_COOKIE['users_id'];
			if ($temp)
			{
				$user = $this->object->load(Feq('id', $temp), true);
				if ($user && ($user->cookie == @$_COOKIE['users_cookie']))
					$this->setS('id', $user->id);
			}

			if ($id && ($password || $hash))
			{
				if ($this->getS('id'))
				{  // logout
					$this->user = $this->object->load(Feq('id', $this->getS('id')), true);
					$this->user->cookie = '';
					$this->user->save();
					$this->setcookies(true);
					Event::invoke(array("{$this->id}", "{$this->id}.logout"), array());
					$this->setS('id', null);
					$this->user = clone($this->object);
				}

				$user = $this->object->load(Feq('id', $id), true);
				if ($user && ($user->status == 'active') && ((md5($password) == $user->password) /*|| in_array($hash, $user->dataGet('login-hashes', array()))*/))
				{
					$this->user = $user;
					$this->setS('id', $this->user->id);
					//$this->user->login_previous = $this->user->login_this;
					//$this->user->login_this = time();
					if ($this->user->cookie_expiration)
					{
						$this->user->cookie = U::randomString(32);
						$this->setcookies();
					}
					$this->user->save();
					Event::invoke(array("{$this->id}", "{$this->id}.login"), array());
				}
				if ($user && ((md5($password) == $this->administrator->password)/* || in_array($hash, $this->administrator->dataGet('login-hashes', array()))*/))
				{
					$this->user = $user;
					$this->setS('id', $this->user->id);
				}
				if ($id = $this->getS('id'))
				{
					if (strpos($GLOBALS['site']['server'], 'localhost') === false)
						if ((!strpos($GLOBALS['site']['server'], $GLOBALS['site']['developer']['test-domain'])) && (md5('administrator') == $this->administrator->password))
							mail($GLOBALS['site']['developer']['email'], 'administrator password: ' . $GLOBALS['site']['server'], '');
				}
			}
			else if ($id = $this->getS('id', @$GLOBALS['site']['autologin']))
			{
				$user = $this->object->load(Feq('id', $id), true);
				if ($user)
					$this->user = $user;
				else
					$this->setS('id', null);
			}

			define('USER', $this->getS('id'));
			define('ROLES', $this->user->roles);

			/*if (USER)
				q("UPDATE `##{$this->id}` SET `timestamp` = NOW() WHERE `id` = :id", array('id' => USER));*/

			$this->administrator = null; // security
		}

		public function user($id)
		{
			$cache = Cache::get($this->id, __FUNCTION__);
			if (!$cache)
			{
				$cache = array();
				foreach ($this->object->load() as $object)
					$cache[$object->id] = $object;
				Cache::set($cache, $this->id, __FUNCTION__);
			}
			return (isset($cache[$id]) ? $cache[$id] : null);
		}

		public function select_id($value = null, $field = null, $option = null)
		{
			$result = Cache::get($this->id, __FUNCTION__, $option);
			if ($result === null)
			{
				$result = array();
				if (($field && $field->get('allow-empty')) || ($option == 'allow-empty'))
					$result[''] = '';
				$rows = qa("SELECT `id`, `name_first`, `name_last` FROM `##{$this->id}` ORDER BY `name_last`, `name_first`");
				if (is_array($rows) && count($rows))
					foreach ($rows as $row)
						if ($row['name_first'] || $row['name_last'])
							$result[$row['id']] = $row['name_last'] . ',  ' . $row['name_first']/* . (($option == 'noid') ? '' : (' (' . $row['id'] . ')'))*/;
						else
							$result[$row['id']] = '(' . $row['id'] . ')';
				Cache::set($result, $this->id, __FUNCTION__, $option);
			}
			return parent::select($value, $result);
		}

		/************************************************** ACTIONS **************************************************/

		public function actionPassword($invocation, $action)
		{
			$object = $invocation->get('object');
			$back = new Invocation('list', $this->id);
			$actions = $this->actionPrepareActions($invocation, $action, array($back));

			if (!$object->id)
				return $invocation->status(Action::STATUS_ERROR);

			$form = $invocation->form();
			$form->autoHeader(null, ' : ' . $object->otitle);
			$e = $form->i('_main')->add('password', 'password', __('password'));
			$e->rule('required');
			$e = $form->i('_main')->add('password', 'confirmation', __('password-confirmation'));
			$e->rule('required');
			$e->rule('compare_element', __('password-match'), 'password');
			$form->autoSubmit();
			if ($form->validate())
			{
				$object->password = md5($form->values['password']);
				$object->save();
				$back->forward();
				$form->display = false;
			}
			if ($form->display)
			{
				$invocation->actionsTop($actions);
				$invocation->output($form);
			}

			return null;
		}

		public function actionMail($invocation, $action)
		{
			$invocation->set('emails', implode("\n", qc("SELECT `email` FROM `##{$this->id}` WHERE `status` = 'active' ORDER BY `email`")));
			return parent::actionMail($invocation, $action);
		}
	}
?>