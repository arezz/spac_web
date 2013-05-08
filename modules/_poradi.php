<?php
	class _poradi extends Module
	{
		public function initialize($phase)
		{
			parent::initialize($phase);
			
			if ($phase == 0)
			{
				$this->action('edit', true);
			}
		}
		
		public function prepare($invocation, $action, $phase, $result)
		{
			$result = parent::prepare($invocation, $action, $phase, $result);

			if ($phase == 7)
			{
				if (isRole('administrator') || isRole($this->id))
					$result = true;
			}

			return $result;
		}

		public function generateAdministrationMenu()
		{
			AdministrationMenu::addItem(new Invocation('edit', $this->id), $this->id);
		}

		public function index($options = array())
		{
			Router::add(Router::TYPE_EQUALS, __('get-url'), '', array('page.location' => $this->id.'/get'));
			Router::process();

			if (Core::location("{$this->id}/get"))
				$this->mainTemplate();
		}

		public function page($options = array())
		{
			if (Core::location("{$this->id}/get"))
			{
				/*$GLOBALS['page']['path'][] = array(
					'text' => __('module-name', $this->id),
					'url' => $this->url('get'),
				);*/
				return true;
			}
		}

		/************************************************** ACTIONS **************************************************/

		public function actionEdit($invocation, $action)
		{
			$form = $invocation->form();
			$form->autoHeader();
			$e = $form->i('_main')->add('file', 'file', 'Soubor CSV s výsledky');
			$e = $form->autoSubmit();
			
			if ($form->validate())
			{
				$temp_file = $GLOBALS['site']['data'] . 'temp/import.csv';
				@unlink($temp_file);
				copy($form->values['file'], $temp_file);
				chmod($temp_file, 0666);
				
				$data = array();
				$kategorie = '';
				$sort_function = create_function('$a, $b', 'if ($a["nej"] > $b["nej"]) return -1; else if ($a["nej"] == $b["nej"]) return 0; else return 1;');
				$trim_function = create_function('&$a', '$a = trim($a, " \"");');
				
				foreach (file($temp_file) as $line)
				{
					$line = trim($line);
					if (!$line)
						continue;
					$line = explode(';', iconv('windows-1250', 'utf-8', $line));
					array_walk($line, $trim_function);
					
					if ($line[0] && !$line[2] && ($line[3] === ''))
					{
						if ($kategorie)
							uasort($data[$kategorie]['seznam'], $sort_function);
						$kategorie = U::urlize(trim($line[0]));
						if (!isset($data[$kategorie]))
							$data[$kategorie] = array('nazev' => $line[0], 'nej' => (int) $line[1], 'sloupce' => array(), 'seznam' => array());
					}
					else if ($kategorie && $line[0] && !$data[$kategorie]['sloupce'])
					{
						$data[$kategorie]['sloupce'] = array_slice($line, 3);
					}
					else if ($kategorie && $line[0])
					{
						$temp1 = array();
						foreach (array_slice($line, 3) as $temp2)
							$temp1[] = (int) $temp2;
						$temp0 = array(
							'prijmeni' => $line[0],
							'jmeno' => $line[1],
							'klub' => $line[2],
							'nej' => -1,
							'celkem' => -1,
							'zavody' => $temp1,
						);
						sort($temp1, SORT_NUMERIC);
						$temp1 = array_reverse($temp1);
						$temp0['celkem'] = array_sum($temp1);
						$temp1 = array_slice($temp1, 0, $data[$kategorie]['nej']);
						$temp0['nej'] = array_sum($temp1);
						$data[$kategorie]['seznam'][] = $temp0;
					}
					else { } // prazdne radky
				}
				uasort($data[$kategorie]['seznam'], $sort_function);
				
				dump(array_keys($data));
				
				file_put_contents($GLOBALS['site']['data'] . 'modules/_poradi', serialize($data));
				$invocation->message('Zpracováno celkem ' . count($data) . ' kategorií.');
			}

			$form->find('file')->valueDisplay = '';
			if ($form->display)
				$invocation->output($form);
		}
	}
?>