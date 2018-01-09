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
		Router::addE('kronika/kron-2011', 'cs', 'kronika/kron-2011', '#locale#/s/kronika_files/kron-2011', true);
		Router::addE('kronika/kron-2012', 'cs', 'kronika/kron-2012', '#locale#/s/kronika_files/kron-2012', true);
		Router::addE('kronika/kron-2013', 'cs', 'kronika/kron-2013', '#locale#/s/kronika_files/kron-2013', true);
		Router::addE('kronika/kron-2014', 'cs', 'kronika/kron-2014', '#locale#/s/kronika_files/kron-2014', true);
		Router::addE('kronika/kron-2015', 'cs', 'kronika/kron-2015', '#locale#/s/kronika_files/kron-2015', true);
		Router::addE('kronika/kron-2016', 'cs', 'kronika/kron-2016', '#locale#/s/kronika_files/kron-2016', true);
		Router::addE('kronika/kron-2017', 'cs', 'kronika/kron-2017', '#locale#/s/kronika_files/kron-2017', true);
	Router::addE('kontakty', 'cs', 'kontakty', '#locale#/s/kontakty', true);
	Router::addE('uzitecne', 'cs', 'uzitecne', '#locale#/s/uzitecne', true);
    Router::addE('ankety', 'cs', 'ankety', '#locale#/s/ankety', true);
    Router::addE('rocnik-2011', 'cs', 'rocnik-2011', '#locale#/s/rocnik-2011', true);
    Router::addE('rocnik-2012', 'cs', 'rocnik-2012', '#locale#/s/rocnik-2012', true);
    Router::addE('rocnik-2013', 'cs', 'rocnik-2013', '#locale#/s/rocnik-2013', true);
	Router::addE('rocnik-2014', 'cs', 'rocnik-2014', '#locale#/s/rocnik-2014', true);
	Router::addE('rocnik-2015', 'cs', 'rocnik-2015', '#locale#/s/rocnik-2015', true);
	Router::addE('rocnik-2016', 'cs', 'rocnik-2016', '#locale#/s/rocnik-2016', true);
	Router::addE('rocnik-2017', 'cs', 'rocnik-2016', '#locale#/s/rocnik-2017', true);
	Router::addE('partneri', 'cs', 'partneri', '#locale#/s/partneri', true);
	Router::process();
?>