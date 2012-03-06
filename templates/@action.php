<?php
	//header('Content-Type: text/plain; charset=utf-8');

	$invocation_ids = array_keys($GLOBALS['invocations']);
	foreach ($invocation_ids as $invocation_id)
		if (isset($GLOBALS['invocations'][$invocation_id]) && $GLOBALS['invocations'][$invocation_id])
			$GLOBALS['invocations'][$invocation_id]->dispatch();

	$result = array();
	$result['info'] = array();
	if (isset($GLOBALS['invocation']) && $GLOBALS['invocation'])
		$url = $GLOBALS['invocation']->url(null, null, 1);
	else
		$url = substr($GLOBALS['site']['url'], 0, strlen($GLOBALS['site']['url']) - strlen($GLOBALS['site']['path']));
	$result['info']['url'] = $url;
	$result['info']['page'] = $GLOBALS['page'];
	$result['info']['title'] = $GLOBALS['page']['title-action'];
	$result['info']['module'] = '';

	$result['messages'] = array();
	if (@$GLOBALS['page']['message'])
		$result['messages'][] = $GLOBALS['page']['message'];
	foreach ($GLOBALS['invocations'] as $invocation)
			foreach ($invocation->messages as $message)
				$result['messages'][] = $message;
	$result['output'] = '';
	$result['output'] .= $GLOBALS['page']['content'];
	foreach ($GLOBALS['invocations'] as $invocation)
		if ($invocation->output[0] || (count($invocation->output) > 1))
		{
			$result['info']['module'] = $invocation->module()->id;
			$result['output'] .= '<div class="module_action module_'.$invocation->module()->id.' action_'.$invocation->action()->id.' module_'.$invocation->module()->id.'_action_'.$invocation->action()->id.'">';
			foreach ($invocation->output as $block => $text)
				$result['output'] .= $text;
			$result['output'] .= '</div>';
		}

	if (!isset($encode) || $encode)
	{
		echo '=' . base64_encode(json_encode($result));
	}
	else
	{
		$GLOBALS['temp'] = $result;
	}

	if ($GLOBALS['site']['development'])
	{
		$dump = '';
		//$dump = print_r($result['info'], true);
		$dump .= dump($result['info'], null, false, false);
		$dump .= $result['output'];
		file_put_contents($GLOBALS['site']['data'] . 'temp/action-output.html', $dump);
		chmod($GLOBALS['site']['data'] . 'temp/action-output.html', 0666);
	}

	$GLOBALS['page']['content'] = '';
?>