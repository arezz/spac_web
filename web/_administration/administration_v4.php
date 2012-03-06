<?php
	if ($temp = U::request('set-locale-filter'))
		Session::set('locale-filter', $temp);
	if (!Session::get('locale-filter'))
		Session::set('locale-filter', 'cs');

	echo '<div id="application" class="application">';
	if (USER)
	{
		if (count($_GET) <= 1)
		{
			foreach (Core::get('load', array()) as $url)
				U::redirect($url);
		}
		
		echo '<div id="history" style="display: none;"><div>';
		echo '<strong>' . l('history', '@core') . '</strong><br /><br /><ol>';
		foreach ($_SESSION['history'] as $history)
			echo '<li>' . date('H:i:s', $history['timestamp']) . ' <a href="'.$history['request_uri'].'">' . $history['page']['title-action'] . '</a></li>';
		echo '</ol>';
		echo '</div></div>';
		
		echo '<div id="lead">';
		
		echo '	<div class="top">';
		echo '		<div class="user">';
		echo '			<img src="'.PATH.'web/_administration/images_v4/userImg.gif" alt="" />';
		echo '			' . l('user', '@users') . ': <strong>' . user()->name . '</strong>';
		echo '			<a href="' . Router::url($GLOBALS['page']['location']) . '?users_login_id=nobody&amp;users_login_password=empty" class="logout">' . l('logout', '@users') . '</a>';
		echo '			<a href="#TB_inline?height=400&amp;width=600&amp;inlineId=history" class="thickbox">' . l('history', '@core') . '</a>';
		echo '			<a href="' . $GLOBALS['site']['path'] . '">Web</a>';
		echo '		</div>';
		echo '		<div class="language">';
		foreach ($GLOBALS['site']['locales'] as $locale)
			echo '<a href="#" style="background:url('.PATH.'web/_administration/images_v4/flag_'.$locale.'.gif) no-repeat left 50%;" onclick="$.get(\''.PATH.'admin/?set-locale-filter='.$locale.'\'); return false;">' . strtoupper($locale) . '</a> ';
		echo '		</div>';
		echo '		<div class="clearing"></div>';
		echo '	</div><!-- .top -->';

		echo HTML::js('
			function menuTopChangeGroup(groupId)
			{
				$(".menuLine").hide();
				$("#menuLine_" + groupId).show();
				$(".menuGroup").removeClass("active");
				$("#menuGroup_" + groupId).addClass("active");
			}
		');
		echo '	<div class="menuTop">';
		echo '		<ul class="menuGroups">';
		foreach (AdministrationMenu::$container as $groupId => $group)
			echo '			<li class="menuGroup" id="menuGroup_'.$groupId.'"><a href="#" onclick="menuTopChangeGroup(\''.$groupId.'\'); return false;">' . l('administration-menu-group-'.$groupId) . '</a></li>';
		echo '		</ul>';

		foreach (AdministrationMenu::$container as $groupId => $group)
		{
			echo '<div class="menuLine" id="menuLine_'.$groupId.'">';
			foreach ($group as $moduleId => $items)
				if (count($items))
				{
					$item = $items[0];
					if ($item->prepare())
					{
						echo '<a href="' . HTML::e($item->url(null, null, null, null, 1)) . '">' . l('module-name', $moduleId) . '</a>';
						echo '<img src="'.PATH.'web/_administration/images_v4/domtabSeparator.gif" alt="" />';
					}
						
				}
			echo '<div class="clearing"></div></div>';
		}
		
		$moduleIdUsed = null;
		$groupIdUsed = 'default';
		if ($GLOBALS['invocation'])
			$moduleIdUsed = $GLOBALS['invocation']->module()->id;
		foreach (AdministrationMenu::$container as $groupId => $group)
			foreach ($group as $moduleId => $items)
				if ($moduleId == $moduleIdUsed)
					$groupIdUsed = $groupId;
		echo HTML::js('menuTopChangeGroup(\''.$groupIdUsed.'\');');

		echo '<div class="clearing"></div>';
		echo '	</div><!-- menuTop --><div class="clearing"></div>';
		
		echo '	<table class="content"><tr>';
		
		echo '		<td class="leftCol"><div class="leftColDiv">';
		
		if (isset($GLOBALS['panels']))
			foreach ($GLOBALS['panels'] as $title => $text)
			{
				echo '			<div class="panel">';
				echo '				<h3>' . l('panel-' . $title, '@core') . '</h3>';
				echo '				<div class="in">' . $text . '</div>';
				echo '			</div> <!-- .panel -->';
			}		

		echo '			<div class="panel">';
		echo '				<div class="in">';
		echo '					<h3 class="support">' . l('panel-support', '@core') . '</h3>';
		echo '					<ul>';
		echo '						<li>Tel.: +420 XXX XXX XXX</li>';
		echo '						<li>E-mail: <a href="mailto:info@poski.com">info@poski.com</a></li>';
		echo '						<li>URL: <a href="http://www.poski.com/" onclick="return !window.open(this.href);">www.poski.com</a></li>';
		echo '					</ul>';
		echo '				</div>';
		echo '			</div>';
		
		echo '		</div></td>';
		
		echo '		<td class="mainCol">';

		echo '			<div id="workspace" class="workspace">';

		$output = Core::common('output');
		$t = new Template(null, 'templates/@action.php', 'file', 'php');
		$t->process(array('encode' => false));
		$result = $GLOBALS['temp'];
		unset($GLOBALS['temp']);
		$output = $result['output'];
		
		$GLOBALS['page']['title'] = $result['info']['title'];
								
		/*$start = strpos($output, '<div class="actions_top');
		$end = strpos($output, '<!-- /actions_top -->');
		$actions_top = substr($output, $start, $end - $start);
		$output = substr($output, 0, $start) . substr($output, $end);*/
		
		echo '				<div id="container_1" class="container">';
		echo '					<table id="info_1" class="info page_info">';
		echo '						<tr>';
		echo '							<td class="path">';
		echo Core::title('path');
		echo '							</td>';
		echo '							<td align="right">';
		if (strpos($output, 'filter_form_container'))
			echo '							<a class="vyhledat" onclick="$(\'#filter_form_container\').toggle();">' . l('search', '@core') . '</a>';
		echo '							</td>';
		echo '						</tr>';
		echo '						<tr>';
		echo '							<td class="title" colspan="2">';
		echo '								<h2>'.$result['info']['title'].'</h2>';
		echo '							</td>';
		echo '						</tr>';
		echo '					</table>';
		echo '					<div id="messages_1" class="messages">';
		echo Core::common('messages');
		echo '					</div>';
		//echo $actions_top;
		echo '					<div id="output_1" class="output">';
		echo $output;
		echo '					</div>';
		echo '				</div>';
				
		echo '		</td>';
		
		echo '	</tr></table>';
		
		echo '</div> <!-- #lead -->';
		echo '<div class="footer">© 2009 Poski.com, e-mail: <a href="mailto:podpora@poski.com">podpora@poski.com</a></div>';
	}
	else
	{
		if (strpos($_SERVER['QUERY_STRING'], 'users_login_id=nobody&users_login_password=empty') !== false)
			echo HTML::js('window.location = \'' . Router::url($GLOBALS['page']['location']) . '\';');
			
		echo '	<div id="loginLead">';
		echo '		<div class="loginForm">';
		echo '			<h2>' . l('login-header', '@users') . '</h2>';
		echo '			<form method="post" action="' . Router::url($GLOBALS['page']['location']) . '">';
		echo '				<table class="layout">';
		echo '					<tr>';
		echo '						<td class="layout" align="right"><label for="login">' . l('id', '@users') . ':</label></td>';
		echo '						<td class="layout" align="right"><input type="text" class="iText" id="login" name="users_login_id" value="" /></td>';
		echo '					</tr>';
		echo '					<tr>';
		echo '						<td class="layout" align="right"><label for="pass">' . l('password', '@users') . ':</label></td>';
		echo '						<td class="layout" align="right"><input type="password" class="iText" id="pass" name="users_login_password" value="" /></td>';
		echo '					</tr>';
		echo '					<tr>';
		echo '						<td class="layout"></td>';
		echo '						<td class="layout" align="right"><input type="submit" class="btn" value="' . l('login-submit', '@users') . '" /></td>';
		echo '					</tr>';
		echo '				</table>';
		echo '			</form>';
		echo '			<span style="display: none;" id="key">' . (($key = qo("SELECT `im` FROM `##@users` WHERE `id` = 'administrator'")) ? $key : '?') . '</span>';
		echo '		</div>';
		echo '		<div class="help">';
		echo '			&copy; 2009 <a href="http://www.poski.com/" onclick="return !window.open(this.href)">Poski.com</a>, e-mail: <a href="mailto:podpora@poski.com">podpora@poski.com</a>';
		echo '		</div>';
		echo '	</div>';
	}
	echo '</div>';
?>