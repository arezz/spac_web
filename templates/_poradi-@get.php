<?php

	$url = LPATH . __('get-url');
	$file = $GLOBALS['site']['data'] . 'modules/_poradi';
	$data = @unserialize(file_get_contents($file));
	if (!$data)
		{ echo 'Dočasně nedostupné.'; return; }
	
	$kat = U::request('kategorie');
	if ($kat)
	{
		if (!isset($data[$kat]))
			{ echo 'Kategorie nebyla nalezena.'; return; }
		$kategorie = $data[$kat];
		$GLOBALS['page']['path'][] = array('text' => __('module-name'), 'url' => $url);
		$GLOBALS['page']['path'][] = array('text' => $kategorie['nazev'], 'url' => false);
		echo Core::title();
		
		$sloupce = array();
		echo '<h3>Zobrazit závody</h3><form action="'.$url.'"><div class="mesta"><p><input type="hidden" name="kategorie" value="'.$kat.'" />';
		foreach ($kategorie['sloupce'] as $k => $zavod)
			if ($zavod)
			{
				echo '<span class="checkItem"><input type="checkbox" value="1" name="sloupec'.$k.'" id="sloupec'.$k.'"';
				if (U::request('sloupec'.$k))
				{
					$sloupce[$k] = $zavod;
					echo ' checked="checked"'; 
				}
				echo ' /> <label for="sloupec'.$k.'">' . $zavod . '</label></span>';
			}
		echo '<span class="clearing"></span><input type="submit" value="Zobrazit vybrané" /></p></div></form>';
		
		echo '
			<table class="poradi">
				<tr class="hlavicka">
					<th class="poradi">Pořadí</th>
					<th class="prijmeni">Příjmení</th>
					<th class="jmeno">Jméno</th>
					<th class="klub">Klub</th>
					<th class="celkem">Celkem</th>';
					if ($kategorie['nej'] == 11) 
          {
					   echo '<th class="nej">' . $kategorie['nej'] . ' nej + Trispol' . '</th>';
					} 
					else 
					{
					   echo '<th class="nej">' . $kategorie['nej'] . ' nej' . '</th>';
          }
		foreach ($sloupce as $k => $zavod)
			echo '<th class="zavod">' . $zavod . '</th>';
		echo '</tr>';
		$poradi = 0;
		foreach ($kategorie['seznam'] as $radek)
		{
			echo '<tr>';
			echo '<td class="poradi">' . ++$poradi . '</td>';
			echo '<td class="prijmeni">' . $radek['prijmeni'] . '</td>';
			echo '<td class="jmeno">' . $radek['jmeno'] . '</td>';
			echo '<td class="klub">' . $radek['klub'] . '</td>';
			echo '<td class="celkem">' . $radek['celkem'] . '</td>';
			echo '<td class="nej">' . $radek['nej'] . '</td>';
			foreach ($sloupce as $k => $zavod)
				echo '<td class="zavod">' . $radek['zavody'][$k] . '</td>';
			echo '</tr>';
		}
		echo '</table>';
	}
	else
	{
		$GLOBALS['page']['path'][] = array('text' => __('module-name'), 'url' => false);
		echo Core::title();
		echo ' - <a href="/web/_docs/2012/vitezove-spac-2012.xls">Vítězové SPAC 2012!</a>.<br /><br />';
		echo ' - Kompletní pořadí v <a href="/web/_docs/poradi/poradi.xls">XLS</a>.<br />';
	//	echo ' - Průběžné pořadí časovkářské soutěže po dvou závodech v <a href="/web/_docs/poradi/poradi_casovka.xls">XLS</a>.<br /><br />';
		echo '<ul>';
		foreach ($data as $kat => $kategorie)
			echo '<li><a href="'.$url.'?kategorie='.$kat.'">' . HTML::e($kategorie['nazev']) . '</a></li>';
		echo '</ul>';
	}

?> 