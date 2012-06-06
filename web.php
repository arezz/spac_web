<?php
	$GLOBALS['site']['name']					= 'spac-os.cz';
	$GLOBALS['site']['title']					= 'spac-os.cz';
	$GLOBALS['site']['email']					= 'info@spac-os.cz';

	Router::addE('', 'cs', 'index', '#locale#/index');
	
	Router::addE('prihlaseni', 'cs', 'prihlaseni', '#locale#/prihlaseni');
	
	//Router::addE('kalendar', 'cs', 'kalendar', '#locale#/s/kalendar', true);
	Router::addE('diskuze', 'cs', 'diskuze', '#locale#/s/diskuze', true);
                Router::addE('diskuze-puvodni', 'cs', 'diskuze-puvodni', '#locale#/s/diskuze-puvodni', true);
	Router::addE('o-nas', 'cs', 'o-nas', '#locale#/s/o-nas', true);
		Router::addE('historie', 'cs', 'historie', '#locale#/s/historie', true);
		Router::addE('pravidla', 'cs', 'pravidla', '#locale#/s/pravidla', true);
		Router::addE('soutezni-rad', 'cs', 'soutezni-rad', '#locale#/s/soutezni-rad', true);
		Router::addE('stanovy', 'cs', 'stanovy', '#locale#/s/stanovy', true);
		Router::addE('soupiska-tymu', 'cs', 'soupiska-tymu', '#locale#/s/soupiska-tymu', true);
	Router::addE('kronika/', 'cs', 'kronika/index', '#locale#/s/kronika', true);
		Router::addE('kronika/kron-uvod', 'cs', 'kronika/kron-uvod', '#locale#/s/kronika_files/kron-uvod', true);
		Router::addE('kronika/kron-2007', 'cs', 'kronika/kron-2007', '#locale#/s/kronika_files/kron-2007', true);
		Router::addE('kronika/kron-2008', 'cs', 'kronika/kron-2008', '#locale#/s/kronika_files/kron-2008', true);
		Router::addE('kronika/kron-2009', 'cs', 'kronika/kron-2009', '#locale#/s/kronika_files/kron-2009', true);
		Router::addE('kronika/kron-2010', 'cs', 'kronika/kron-2010', '#locale#/s/kronika_files/kron-2010', true);
	Router::addE('kontakty', 'cs', 'kontakty', '#locale#/s/kontakty', true);
	Router::addE('uzitecne', 'cs', 'uzitecne', '#locale#/s/uzitecne', true);
                Router::addE('ankety', 'cs', 'ankety', '#locale#/s/ankety', true);
                Router::addE('rocnik-2011', 'cs', 'rocnik-2011', '#locale#/s/rocnik-2011', true);
	Router::addE('partneri', 'cs', 'partneri', '#locale#/s/partneri', true);
	Router::process();
?>