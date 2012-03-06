<?php
	if (!isset($module_id))
		$module_id = 'news';
	Locale::module($module_id);
	qconnect();
	if (!isset($count))
		$count = 3;
	if (!isset($pieces))
		$pieces = explode(';', 'image;ocreated;title;summary;more');
	if (!isset($skip))
		$skip = '';
	if (!isset($cut_length))
		$cut_length = (int) __('cut-length', '@core');
	if (!isset($date_format))
		$date_format =  __('date-format', '@core');
	$count = (int) $count;
	$rows = qa("SELECT * FROM `##$module_id` WHERE `title_#L#` != '' ORDER BY `ocreated` DESC LIMIT $count");
	$url = __('get-url', $module_id);
	
	echo '<div class="'.$module_id.'_column '.$module_id.' column">';
	foreach ($rows as $row)
	{
		$u = LPATH.$url.U::urlize($row['title'.LL]).'-'.$row['id'];
		$t = HTML::e($row['title'.LL]);
		if (isset($before))
			echo $before;
		echo '<div class="'.$module_id.'_list_item '.$module_id.' list_item">';
		foreach ($pieces as $piece)
		{
			if (U::piece($piece, $skip, true))
				echo __('template-column-piece-'.$piece.'-before', null, false);
			if (U::piece($piece, $skip, 'image'))
				echo Core::common('thumbnail', $row['image'], $t, $u);
			if (U::piece($piece, $skip, 'ocreated'))
				echo '<span class="ocreated">'.HTML::e(strftime($date_format, strtotime($row['ocreated']))).'</span>';
			if (U::piece($piece, $skip, 'title'))
				echo '<a class="title" href="'.$u.'">'.$t.'</a>';
			if (U::piece($piece, $skip, 'title-span'))
				echo '<span class="title">'.$t.'</span>';
			if (U::piece($piece, $skip, 'summary'))
				echo '<div class="summary">'.HTML::e(U::cut($row['summary'.LL], $cut_length)).'</div>';
			if (U::piece($piece, $skip, 'text'))
				echo '<div class="text">'.HTML::e(U::cut(trim(strip_tags($row['text'.LL])), $cut_length)).'</div>';
			if (U::piece($piece, $skip, 'more'))
				echo '<a class="more" href="'.$u.'">'.__('more', $module_id) . '<span></span></a>';
			if (U::piece($piece, $skip, true))
				echo __('template-column-piece-'.$piece.'-after', null, false);
		}
		echo '</div>';
		if (isset($after))
			echo $after;
	}
	echo '</div>';
?>