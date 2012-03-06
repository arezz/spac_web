<?php
	if ($temp = U::request('set-locale-filter'))
		Session::set('locale-filter', $temp);
	if (!Session::get('locale-filter'))
		Session::set('locale-filter', 'cs');
		
	echo '<div id="application" class="application">';
	if (USER)
	{
		echo '		<div id="lead">';
		echo '			<div id="top">'; 
		echo '				<div id="instalation">';
		echo '					<strong>' . l('installation', '@core') . ':</strong> ' . $GLOBALS['site']['name'];
		echo '				</div>';
		echo '				<div id="user">';
		foreach ($GLOBALS['site']['locales'] as $locale)
			echo '<a href="#" onclick="$.get(\''.$GLOBALS['site']['path'].'admin/?set-locale-filter='.$locale.'\'); return false;">' . strtoupper($locale) . '</a> | ';
		echo '					<strong>' . l('user', '@users') . ':</strong> ' . $GLOBALS['modules']['@users']->user->name; 
		echo '					<a class="logout" href="' . Router::url($GLOBALS['page']['location']) . '?users_login_id=nobody&amp;users_login_password=empty">' . l('logout', '@users') . '</a>';
		echo '				</div>';
		echo '				<div id="topMenu">';
		foreach (AdministrationMenu::$container as $groupId => $group)
			foreach ($group as $moduleId => $items)
				if (count($items))
				{
					$item = $items[0];
					if ($item->prepare())
						echo '					<a href="' . HTML::e($item->url(null, null, 1)) . '"' . (Core::location('admin') ? 'onclick="application.load(this.href); return false;"' : '') . ' style="background-image:url(\'' . $GLOBALS['site']['path'] . 'web/_administration/icons/module_' . $moduleId . '.gif\');">' . l('module-name', $moduleId) . '</a>';
				}
				
		echo '				</div> <!-- #topMenu -->';
		echo '			</div> <!-- #top -->';
		echo '			<div id="content">';
		echo '				<div id="workspace" class="workspace">';
		echo '					<div id="workspace_placeholder"></div>';
		if (Core::location('administration'))
		{
			$output = Core::common('output');
			$t = new Template(null, 'templates/@action.php', 'file', 'php');
			$t->process(array('encode' => false));
			$result = $GLOBALS['temp'];
			$GLOBALS['page']['title'] = $GLOBALS['temp']['info']['title'] . Core::get('title-separator') . $GLOBALS['page']['title'];
			//dump($GLOBALS['temp']);
			unset($GLOBALS['temp']);
			$output = $result['output'];
									
			$start = strpos($output, '<div class="actions_top');
			$end = strpos($output, '<!-- /actions_top -->');
			$actions_top = substr($output, $start, $end - $start);
			$output = substr($output, 0, $start) . substr($output, $end);
									 
			echo '						<div id="container_1" class="container">';
			echo '							<div id="info_1" class="info">';
			echo '								<div class="module">';
			echo '									<img alt="' . @$result['info']['module'] . '" src="/web/_administration/icons/module_' . @$result['info']['module'] . '.gif" class="icon"/>';
			echo '									<strong>' . @$result['info']['title'] . '</strong>';
			echo '								</div>';
			if (strpos($output, 'filter_form_container'))
				echo '							<a class="button button_filter" onclick="$(\'#filter_form_container\').toggle();">&#160;</a>';			
			echo $actions_top;
			echo '							</div>';
			echo '							<div id="messages_1" class="messages">';
			echo Core::common('messages');
			echo '							</div>';
			echo '							<div id="output_1" class="output">';
			echo $output;
			echo '							</div>';
			echo '						</div>';
		}
		else
		{
			echo HTML::js(0);
			echo 'application.showInfoButtonText = false;' . "\n";
			if (count($_GET) > 1)
				echo 'application.load(window.location.href);' . "\n";
			foreach (Core::get('load', array()) as $url)
				echo 'application.load(\'' . $url . '\');' . "\n";
			echo 'G.page.location = \'' . $GLOBALS['page']['location'] . '\';' . "\n";
			echo HTML::js(1);
		}
		echo '				</div>';		
		echo '				<div style="clear: both;"></div>';
		echo '			</div> <!-- #content -->';
		echo '		</div> <!-- #lead -->';
	}
	else
	{
		if (strpos($_SERVER['QUERY_STRING'], 'users_login_id=nobody&users_login_password=empty') !== false)
			echo HTML::js('window.location = \'' . Router::url($GLOBALS['page']['location']) . '\';');
		echo '	<div id="login" class="login">';
		echo '		<div id="top" class="top">';
		echo '			<img class="logo" src="' . $GLOBALS['site']['path'] . 'web/_administration/images_v3/poskiLogo.gif" alt="Poski.com" />';
		echo '			<div class="title">' . $GLOBALS['site']['name'] . ' - ' . l('login-header', '@users') . '</div>';
		echo '		</div>';
		echo '		<div id="workspace" class="workspace">';
		echo '			<form action="' . $GLOBALS['modules']['@users']->get('login-url', '') . '" method="post" class="form">';
		echo '				<fieldset class="login_fieldset" title="' . l('login-header', '@users') . '">';
		echo '					<legend>' . l('login-header', '@users') . '</legend>';
		echo '					<table style="border:0px;">';
		echo '						<tr>';
		echo '							<td style="border:0px;text-align:right;width:30%;"><label for="users_login_id">' . l('id', '@users') . '</label></td>';
		echo '							<td style="border:0px;"><input name="users_login_id" type="text" id="users_login_id" style="width:80%;" /></td>';
		echo '						</tr>';
		echo '						<tr>';
		echo '							<td style="border:0px;text-align:right;"><label for="users_login_password">' . l('password', '@users') . '</label></td>';
		echo '							<td style="border:0px;"><input name="users_login_password" type="password" id="users_login_password" style="width:80%;" /></td>';
		echo '						</tr>';
		echo '						<tr>';
		echo '							<td style="border:0px;"><label>&nbsp;</label></td>';
		echo '							<td style="border:0px;"><input name="submit" value="' . l('login-submit', '@users') . '" type="submit" class="submit" style="width:80%;" /></td>';
		echo '						</tr>';
		echo '					</table>';
		echo '				</fieldset>';
		echo '			<span style="display: none;" id="key">' . (($key = qo("SELECT `im` FROM `##@users` WHERE `id` = 'administrator'")) ? $key : '?') . '</span>';
		echo '			</form>';
		echo '		</div>';
		echo '	</div>';
	}
	echo '</div>';
?>