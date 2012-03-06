<?php
	if (USER)
	{
		echo '<strong>' . l('user', '@users') . ':</strong> ' . $GLOBALS['modules']['@users']->user->name; 
		echo ' | <a href="' . Router::url($GLOBALS['page']['location']) . '?users_login_id=nobody&amp;users_login_password=empty">' . l('logout', '@users') . '</a>';
		echo ' | <a href="' . $GLOBALS['site']['path'] . '">Web</a><br />';
		
		echo '<div id="menu">';
		foreach (AdministrationMenu::$container as $groupId => $group)
			foreach ($group as $moduleId => $items)
				if (count($items))
				{
					$item = $items[0];
					if ($item->prepare())
						echo '<a href="' . HTML::e($item->url(null, null, null, null, 1)) . '">' . l('module-name', $moduleId) . '</a> ';
				}
		
		echo '</div><hr /><div id="workspace" class="workspace">';
		
		$output = Core::common('output');
		$t = new Template(null, 'templates/@action.php', 'file', 'php');
		$t->process(array('encode' => false));
		$result = $GLOBALS['temp'];
		$GLOBALS['page']['title'] = $GLOBALS['temp']['info']['title'] . Core::get('title-separator') . $GLOBALS['page']['title'];
		unset($GLOBALS['temp']);
		$output = $result['output'];
		
		echo '<div class="messages">' . Core::common('messages') . '</div>';
		echo '<div class="output">' . $output . '</div>';
		
		echo '</div>';
	}
	else
	{
		echo '<form action="' . $GLOBALS['modules']['@users']->get('login-url', '') . '" method="post">';
		echo '<fieldset title="' . l('login-header', '@users') . '">';
		echo '<legend>' . l('login-header', '@users') . '</legend>';
		echo '<div>';
		echo l('id', '@users').': <input name="users_login_id" type="text" id="users_login_id" /><br />';
		echo l('password', '@users').': <input name="users_login_password" type="password" id="users_login_password" />';
		echo '<input name="submit" value="' . l('login-submit', '@users') . '" type="submit" />';
		echo '</div>';
		echo '</fieldset>';
		echo '</form>';		
	}
?>