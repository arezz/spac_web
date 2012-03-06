<?php
	if (!isset($module_id))
		$module_id = 'files';
	Locale::module('files');
	Locale::module($module_id);
	qconnect();
	$rows = qa("SELECT * FROM `##$module_id` ORDER BY `priority`, `title_#L#`");
	
	if (!function_exists('files_simple_recursive'))
	{
		function files_simple_recursive($rows, $pid = '')
		{
			$output = '';
			foreach ($rows as $row)
				if ($row['pid'] == $pid)
				{
					$output .= '<li>';
					$size = round($row['blobsize'] / 1024) . ' kB';
					if ($row['blob'])
						$output .= '<a href="'.$GLOBALS['site']['path'].'data/blob/' . $row['blob'] . '">' . $row['title'.LL] . '</a> ' . $size;
					else
						$output .= '<a href="#"><strong>' . $row['title'.LL] . '</strong></a>';
					$output .= files_simple_recursive($rows, $row['oid']);
					$output .= '</li>';
				}
			if ($output)
				$output = '<ul>' . $output . '</ul>';
			return $output;
		}
	}
	
	echo '<div class="'.$module_id.'_simple '.$module_id.' simple">';
	echo files_simple_recursive($rows);
	echo '</div>';
?>