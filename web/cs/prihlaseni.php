 <h2>Přihlášení k závodu</h2>
 
 <?php
 	$form = new Form('prihlaseni');
 	$_main = $form->i('_main');
 	
 	//$form->autoHeader();
 	$e = $_main->add('select', 'zavod', 'Závod', qc("SELECT `nazev`, `id` FROM `##_kalendar` WHERE `datum` > CURRENT_DATE()", array(), 'nazev', 'id'));
 	$e->rule('required');
 	if ($temp0 = U::request('zavod'))
 		$e->default = $temp0;
 	$e = $_main->add('text', 'kategorie', 'Ročník / kategorie');
 	$e->rule('required');
 	$e = $_main->add('text', 'tym', 'Tým / klub');
 	$e->rule('required');
 	$e = $_main->add('text', 'jmeno', 'Jméno');
 	$e->rule('required');
 	$e = $_main->add('text', 'prijmeni', 'Příjmení');
 	$e->rule('required');
 	$e = $_main->add('text', 'email', 'E-mail');
 	$e->rule('required');
 	/*$e = $_main->add('text', 'telefon', 'Telefon');
 	$e->rule('required');*/
 	$e = $_main->add('textarea', 'poznamka', 'Poznámka');
 	
 	$e = $form->autoSubmit('Odeslat přihlášku');
 	
 	if ($form->validate())
 	{
 		$zavod = qr("SELECT * FROM `##_kalendar` WHERE `id` = :id", array('id' => $form->values['zavod'][0]));
 		$subject = 'Přihláška k závodu: ' . $zavod['nazev'];
 		$body = 
 			"Závod: " . $zavod['nazev'] . "\n" .
 			"Datum závodu: " . date('d.m.Y', strtotime($zavod['datum'])) . "\n" .
 			"Ročník / kategorie: " . $form->values['kategorie'] . "\n" .
 			"Tým / klub: " . $form->values['tym'] . "\n" .
 			"Jméno: " . $form->values['jmeno'] . "\n" .
 			"Příjmení: " . $form->values['prijmeni'] . "\n" . 
 			"E-mail: " . $form->values['email'] . "\n" . 
 			//"Telefon: " . $form->values['telefon'] . "\n" . 
 			"\nPoznámka: \n\n" . $form->values['poznamka'] . "\n";
 		
 		U::mail($zavod['poradatel_email'], null, $subject, $body, $form->values['email']); 
 		//TODO povolit, az bude znama cilova adresa   U::mail('prihlasky@spac.cz', null, $subject, $body, $form->values['email']); 
 		U::mail($form->values['email'], null, $subject, $body, $zavod['poradatel_email']); 
 		echo 'Přihláška byla odeslána.';
 		$form->display = false;
 	}
 	
 	if ($form->display)
 		echo $form->render();
 ?>