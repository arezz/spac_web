<?php
	if (USER)
	{
		function administration_main_panel()
		{
			ob_start();
			$output = Core::common('output');
			$t = new Template(null, 'templates/@action.php', 'file', 'php');
			$t->process(array('encode' => false));
			$result = $GLOBALS['temp'];
			$GLOBALS['page']['title'] = $GLOBALS['temp']['info']['title'];
			$GLOBALS['site']['title'] = '';
			unset($GLOBALS['temp']);
			$output = $result['output'];
			/*$start = strpos($output, '<div class="actions_top');
			$end = strpos($output, '<!-- /actions_top -->');
			$actions_top = substr($output, $start, $end - $start);
			$output = substr($output, 0, $start) . substr($output, $end);*/

			echo '		<div id="messages" class="messages">' . Core::common('messages') . '</div>'; //  style="border-bottom: 2px solid black;"
			echo '		<div class="output">';
			echo $output;
			echo '		</div>';
			return ob_get_clean();
		}
		
		if ($GLOBALS['page']['panel'])
		{
			echo '<div id="application" class="application">';
			echo '	<div id="workspace" class="workspace">';
			echo administration_main_panel();
			echo '	</div>';
			echo '</div>';
		}
		else
		{
			echo '<div id="application" class="application">';
			echo '	<div id="top" class="top">';
			echo '		<span class="title">' . $GLOBALS['site']['name'] . '</span>';
			echo '		<a class="logout" href="' . Router::url($GLOBALS['page']['location']) . '?users_login_id=nobody&amp;users_login_password=empty" style="float: right;">' . l('logout', '@users') . '</a>';
			echo '		<span style="float: right;">&nbsp;&nbsp;</span>';
			echo '		<a class="logout" href="' . $GLOBALS['site']['path'] . '" style="float: right;">Web</a>';
			echo '		<span style="float: right;">&nbsp;&nbsp;</span>';
			echo '		<a class="logout" href="' . Router::url('apda') . '" style="float: right;">PDA</a>';
			echo '		<span style="float: right;">&nbsp;&nbsp;</span>';
			echo '		<a class="logout" href="' . Router::url('administration') . '" style="float: right;">Admin</a>';
			echo '		<span style="float: right;">&nbsp;&nbsp;</span>';
			echo '		<a class="logout" href="' . Router::url('aa') . '" style="float: right;">AA</a>';
			echo '		<span style="float: right;">&nbsp;&nbsp;</span>';
			echo '		<a class="logout" href="' . Router::url('a') . '" style="float: right;">A</a>';
			echo '		<div class="info">';
			echo '			<span class="user"><strong>' . l('user', '@users') . ':</strong> ' . user()->name . '</span>';
			//echo '			<span class="last_login"><strong>' . l('login_previous', '@users') . ':</strong> ' . strftime(l('datetime-format-Y-m-d H:i:s', '@core'), user()->login_previous) . '</span>';
			echo '		</div>';
			echo '	</div>';
			echo '	<div id="topmenu" class="topmenu">';
			foreach (AdministrationMenu::$container as $groupId => $group)
			{
				echo '<div class="menu_group"><strong>' . l('administration-menu-group-'.$groupId) . '</strong>';
				foreach ($group as $moduleId => $items)
					if (count($items))
					{
						$item = $items[0];
						if ($item->prepare())
						{
							$onclick = ($GLOBALS['page']['location'] == 'a') ? 'onclick="application.load(this.href); return false;"' : '';
							echo '<a href="' . HTML::e($item->url(null, null, 1)) . '" '.$onclick.'>' . l('module-name', $moduleId) . '</a>';
						}
					}
				echo '</div>';
			}
			echo '		<div style="clear: both;"></div>';
			echo '	</div>';
			echo Core::common('messages-output', true);
			echo '	<div id="workspace" class="workspace">';
			if ($GLOBALS['page']['location'] == 'a')
			{
				echo '		<div id="workspace_placeholder"></div>';
				echo '	' . HTML::js(0);
				if (count($_GET) > 1)
					echo '			application.load(window.location.href);' . "\n";
				foreach (Core::get('load', array()) as $url)
					echo '			application.load(\'' . $url . '\');' . "\n";
				echo '		G.page.location = \'' . $GLOBALS['page']['location'] . '\';' . "\n";
				echo '	' . HTML::js(1);
			}
			else
			{
				$temp = administration_main_panel();
				echo '<div class="info" style="padding: 1ex; background: black; color: white; font-family: Verdana,Arial,Helvetica,sans-serif; font-size: 10pt; font-weight: bold;">';
				echo '<span style="margin-right: 1ex; padding: 0 3px; border: 1px solid white; cursor: pointer;" onclick="return false;">&nbsp;</span>';
				echo '<span style="margin-right: 1ex; padding: 0 3px; border: 1px solid white; cursor: pointer;" onclick="window.location = window.location;">O</span>';
				echo '<strong>' . $GLOBALS['page']['title'] . '</strong>';
				echo '</div>'; 
				echo $temp;
			}
			echo '	<div style="clear: both;"></div>';
			echo '	</div>';
			echo '</div>';
		}
	}
	else
	{
		if (strpos($_SERVER['QUERY_STRING'], 'users_login_id=nobody&users_login_password=empty') !== false)
			echo HTML::js('window.location = \'' . Router::url($GLOBALS['page']['location']) . '\';');
		echo '<div id="application" class="application">';
		echo '	<div id="login" class="login">';
		echo '		<div id="top" class="top">';
		echo '			<!--<img class="logo" src="' . $GLOBALS['site']['path'] . 'web/_administration/images/j/smalllogo.png" alt="Poski.com" style="float: left;" />-->';
		echo '			<div class="title">' . $GLOBALS['site']['name'] . '</div>';
		echo '		</div>';
		echo '		<div id="workspace" class="workspace">';
		echo '			<form action="' . m('@users')->get('login-url', '') . '" method="post" class="form" target="_top">';
		echo '				<fieldset title="' . l('login-header', '@users') . '">';
		echo '					<legend>' . l('login-header', '@users') . '</legend><div>';
		echo '					<div>';
		echo '						<div class="label"><label for="users_login_id">' . l('id', '@users') . '</label></div>';
		echo '						<div class="element"><input name="users_login_id" type="text" id="users_login_id" /></div>';
		echo '					</div>';
		echo '					<div>';
		echo '						<div class="label"><label for="users_login_password">' . l('password', '@users') . '</label></div>';
		echo '						<div class="element"><input name="users_login_password" type="password" id="users_login_password" /></div>';
		echo '					</div>';
		echo '					<div>';
		echo '						<div class="label"><label>&nbsp;</label></div>';
		echo '						<div class="element"><input name="submit" value="' . l('login-submit', '@users') . '" type="submit" class="submit" /></div>';
		echo '					</div>';
		echo '				</div></fieldset>';
		echo '			</form>';
		echo '			<span style="display: none;" id="key">' . (($key = qo("SELECT `im` FROM `##@users` WHERE `id` = 'administrator'")) ? $key : '?') . '</span>';
		echo '		</div>';
		echo '	</div>';
		echo '</div>';
	}
?>