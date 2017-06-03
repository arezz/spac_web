<?php
	$main = $GLOBALS['templates']['main']->process();
	if ($GLOBALS['page']['simple'])
		$title = Core::title();
	if (!isset($GLOBALS['templates']['frame']) || @$GLOBALS['page']['flags']['no-frame'])
	{
		echo $main;
		return;
	}
?>

<div id="hlava"></div>
  
  <div id="lead">
    
    <div id="logo">
      <h1><a href="/" title="Návrat na úvodní stránku">Slezský Pohár Amatérských Cyklistů<span></span></a></h1>
    </div> <!-- #logo -->
    
    <div class="flash">
      <img src="/web/_images/flash.jpg" alt="" title="" />
    </div>
    
    <div class="menu">
      <ul class="level1">
        <li><a href="/" class="home<?php if ($GLOBALS['page']['location'] == "index") echo ' active'; ?>"><span>Home</span></a></li>
        <li><img src="/web/_images/menuSeparator.gif" alt="" /></li>
        <li><a href="/o-nas" class="onas<?php if ($GLOBALS['page']['location'] == "soupiska-tymu") echo ' active'; ?><?php if ($GLOBALS['page']['location'] == "stanovy") echo ' active'; ?><?php if ($GLOBALS['page']['location'] == "soutezni-rad") echo ' active'; ?><?php if ($GLOBALS['page']['location'] == "pravidla") echo ' active'; ?><?php if ($GLOBALS['page']['location'] == "historie") echo ' active'; ?><?php if ($GLOBALS['page']['location'] == "o-nas") echo ' active'; ?>"><span>O nás</span></a>
          <ul class="level2">
            <li><a href="/historie">Historie</a></li>
            <li><a href="/pravidla">Pravidla</a></li>
            <li><a href="/soutezni-rad">Soutěžní řád</a></li>
            <li><a href="/stanovy">Stanovy</a></li>
            <li><a href="/partneri">Sponzoři</a></li>
          </ul>
        </li>
        <li><img src="/web/_images/menuSeparator.gif" alt="" /></li>
        <li><a href="/kontakty" class="kontakty<?php if ($GLOBALS['page']['location'] == "kontakty") echo ' active'; ?>"><span>Kontakty</span></a></li>
        <li><img src="/web/_images/menuSeparator.gif" alt="" /></li>
        <li><a href="/aktuality/" class="aktuality<?php if ($GLOBALS['page']['location'] == "news/get") echo ' active'; ?>"><span>Aktuality</span></a></li>
        <li><img src="/web/_images/menuSeparator.gif" alt="" /></li>
        <li><a href="/kalendar/" class="kalendar"><span>Kalendář</span></a></li>
        <li><img src="/web/_images/menuSeparator.gif" alt="" /></li>
        <li><a href="/poradi/" class="poradi"><span>Pořadí</span></a></li>
        <li><img src="/web/_images/menuSeparator.gif" alt="" /></li>
        <li><a href="/fotogalerie/" class="fotogalerie"><span>Fotogalerie</span></a></li>
        <li><img src="/web/_images/menuSeparator.gif" alt="" /></li>
        <li><a href="/kronika/" class="kronika"><span>Kronika</span></a>
          <ul class="level2">
            <li><a href="/kronika/kron-uvod">Úvod</a></li>
			<li><a href="/kronika/kron-2016">2016</a></li>
			<li><a href="/kronika/kron-2015">2015</a></li>
            <li><a href="/kronika/kron-2014">2014</a></li>
            <li><a href="/kronika/kron-2013">2013</a></li>
            <li><a href="/kronika/kron-2012">2012</a></li>
            <li><a href="/kronika/kron-2011">2011</a></li>
            <li><a href="/kronika/kron-2010">2010</a></li>
            <li><a href="/kronika/kron-2009">2009</a></li>
            <li><a href="/kronika/kron-2008">2008</a></li>
            <li><a href="/kronika/kron-2007">2007</a></li>            
          </ul>
        </li>
       <!-- <li><img src="/web/_images/menuSeparator.gif" alt="" /></li>
        <li><a href="" class="archiv"><span>Archív</span></a></li> -->
        <li><img src="/web/_images/menuSeparator.gif" alt="" /></li>
        <li><a href="/uzitecne" class="uzitecne<?php if ($GLOBALS['page']['location'] == "uzitecne") echo ' active'; ?>"><span>Užitečné</span></a>
           <ul class="level2">
                <li><a href="/uzitecne">Dokumenty</a></li>
                <li><a href="/ankety">Ankety</a></li>
				<li><a href="/rocnik-2016">Ročník 2016</a></li>
				<li><a href="/rocnik-2015">Ročník 2015</a></li>
                <li><a href="/rocnik-2014">Ročník 2014</a></li>
				<li><a href="/rocnik-2013">Ročník 2013</a></li>
                <li><a href="/rocnik-2012">Ročník 2012</a></li>
                <li><a href="/rocnik-2011">Ročník 2011</a></li>
                <li><a href="http://www.spac.ic.cz/historie/historie.htm" target="blank">Starší ročníky</a></li>
          </ul>
        </li>
        <li><img src="/web/_images/menuSeparator.gif" alt="" /></li>
        <li><a href="/diskuze" class="diskuze<?php if ($GLOBALS['page']['location'] == "diskuze") echo ' active'; ?>"><span>Diskuze</span></a>
            <ul class="level2">
                <li><a href="/diskuze">Oficiální</a></li>
                <!-- <li><a href="/diskuze-puvodni">Necenzurovaná</a></li>  -->
            </ul>
        </li>
      </ul>
    </div>
    
    <div class="content">

<?php if ($GLOBALS['page']['location'] == "_poradi/get") 
{       
 echo '<!--';
}
?>

       <div class="obsah">
         <div class="mainCol"> 

<?php if ($GLOBALS['page']['location'] == "_poradi/get")
{       
 echo '-->';
}
?>
   
          
          <?php   
          	   echo $main;    
          ?>

<?php if ($GLOBALS['page']['location'] == "_poradi/get")
{       
 echo '<!--';
}
?>
            <div class="clearing"></div>
         </div>
        
         <div class="partneri">
           <div class="partnerItemPrihlas">
             <!-- <a href="http://cyklistika.martinstriz.cz/prihlaseni-zavodnika.php" onclick="return !window.open(this.href)"><h4>Přihlašování na závody</h4></a> -->
			 <a href="http://www.atletikauni.cz/cz/s1516/Kalendar-akci/c2129-Seznam-akci" onclick="return !window.open(this.href)"><h4>Přihlašování na závody</h4></a>
             <span class="clearing"></span>
           </div>  
          
           <div class="partnerItem">
                 <a href="http://www.toplist.cz/stat/467931">
<script type="text/javascript" language="JavaScript">
&lt;!--
document.write ('&lt;img src="http://toplist.cz/count.asp?id=467931&amp;logo=mc&amp;start=1709&amp;http='+escape(document.referrer)+'&amp;wi='+escape(window.screen.width)+'&amp;he='+escape(window.screen.height)+'&amp;t='+escape(document.title)+'" width="88" height="60" border=0 alt="TOPlist" /&gt;');
//--&gt;</script><img width="88" height="60" border="0" alt="TOPlist" src="http://toplist.cz/count.asp?id=467931&amp;logo=mc&amp;start=1709&amp;http=http%3A//www.spac-os.cz/uzitecne&amp;wi=1280&amp;he=800&amp;t=Slezsk%FD%20poh%E1r%20amat%E9rsk%FDch%20cyklist%u016F"/>
                    <noscript>
                      &lt;img src="http://toplist.cz/count.asp?id=467931&amp;logo=mc&amp;start=1709" border="0" alt="TOPlist" width="88" height="60" /&gt;</noscript></a>
            <span class="clearing"></span>
           </div>
           <div class="partnerItem">
             <a href="http://www.fitnessobchod.com/" onclick="return !window.open(this.href)"><img src="/web/_images/logo-fitness.gif" alt="" title="" /></a>
             <span class="clearing"></span>
           </div>
            <div class="partnerItem">
             <a href="http://www.force.cz/" onclick="return !window.open(this.href)"><img src="/web/_images/logo_force_gray.gif" alt="" title="" /></a>
             <span class="clearing"></span>
           </div>
           <div class="partnerItem">
             <a href="http://www.maxbike.cz/" onclick="return !window.open(this.href)"><img src="/web/_images/logo_maxbike.gif" alt="" title="" /></a>
             <span class="clearing"></span>
           </div>
           <div class="partnerItem">
             <a href="http://www.lawi.cz/" onclick="return !window.open(this.href)"><img src="/web/_images/logo_08_Lawi.gif" alt="" title="" /></a>
             <span class="clearing"></span>
           </div>
           <div class="partnerItem">
             <a href="http://www.pohary-bauer.cz/" onclick="return !window.open(this.href)"><img src="/web/_images/logo_11_Bauer.gif" alt="" title="" /></a>
             <span class="clearing"></span>
           </div>
           <div class="partnerItem">
             <a href="http://http://www.repronis.cz/" onclick="return !window.open(this.href)"><img src="/web/_images/logo_01_repronis.gif" alt="" title="" /></a>
             <span class="clearing"></span>
           </div>
           <div class="partnerItem">
             <a href="http://www.idnes.cz/" onclick="return !window.open(this.href)"><img src="/web/_images/logo_dnes.gif" alt="" title="" /></a>
             <span class="clearing"></span>
           </div>
           <div class="partnerItem">
             <a href="http://www.facebook.com/groups/376329552380236/" onclick="return !window.open(this.href)"><img src="/web/_images/fb.png" alt="SPAC na Facebooku" title="SPAC na Facebooku" /></a>
             <span class="clearing"></span>
           </div>          
          
           
<?php if ($GLOBALS['page']['location'] == "_poradi/get")  
{       
 echo '-->';
}
?>
          <!--  <div class="partnerItem">
             <a href="http://www.saccr.cz/novinky/" onclick="return !window.open(this.href)"><img src="/web/_images/logo_SAC_CR_web.gif" alt="" title="" /></a>
             <span class="clearing"></span>
           </div> -->
          <!-- <div class="partnerItem">
             <a href="http://www.rozhlas.cz/ostrava/portal/" onclick="return !window.open(this.href)"><img src="/web/_images/logo_02_cesky_rozhlas.gif" alt="" title="" /></a>
             <span class="clearing"></span>
           </div>   -->
            <!--<div class="partnerItem">
             <a href="http://www.sabe.cz/" onclick="return !window.open(this.href)"><img src="/web/_images/logo_09_Sabe.gif" alt="" title="" /></a>
             <span class="clearing"></span>
           </div>-->
           <!--<div class="partnerItem">
             <a href="http://www.klusport.com/" onclick="return !window.open(this.href)"><img src="/web/_images/logo_07_Klusport_Aminostar2_s.gif" alt="" title="" /></a>
             <span class="clearing"></span>
           </div>    -->
            <!--<div class="partnerItem">
             <a href="http://www.kolik.org/" onclick="return !window.open(this.href)"><img src="/web/_images/logo_03_kolik.gif" alt="" title="" /></a>
             <span class="clearing"></span>
           </div>
           <div class="partnerItem">
             <a href="http://www.repronis.cz/" onclick="return !window.open(this.href)"><img src="/web/_images/logo_01_repronis.gif" alt="" title="" /></a>
             <span class="clearing"></span>
           </div>
           <div class="partnerItem">
             <a href="http://www.poharykabourek.cz/" onclick="return !window.open(this.href)"><img src="/web/_images/logo_10_Kabourek.gif" alt="" title="" /></a>
             <span class="clearing"></span>
           </div>
            <div class="partnerItem">
             <a href="http://www.namlyne.cz/" onclick="return !window.open(this.href)"><img src="/web/_images/logo_mlyn_cmyk.gif" alt="" title="" /></a>
             <span class="clearing"></span>
           </div>   -->
           
<?php if ($GLOBALS['page']['location'] == "_poradi/get") 
{       
 echo '<!--';
}
?>

           <div class="partnerItem">
             <a href="http://www.poski.com/" onclick="return !window.open(this.href)"><img src="/web/_images/logo_00_poski.gif" alt="Poski.com s.r.o." title="Poski.com s.r.o." /></a>
             <span class="clearing"></span>
           </div>
          
           <p>
             <a href="/partneri">všichni partneři</a>
           </p>
         </div>
        
         <div class="clearing"></div>
       </div>
      
       <div class="footer">
         <p>
           &copy; Copyright 2011 - <strong>Vedení SPAC  -  Pavel Krchňák</strong> - ředitel soutěže<br />
           Tel. (+420) 776 251 705 , E-mail: <a href="mailto: PavelKrchnak@seznam.cz">PavelKrchnak@seznam.cz</a>
         </p>
         <iframe src="http://www.facebook.com/plugins/like.php?href=http://www.spac-os.cz/"
        scrolling="no" frameborder="0" style="border:none; width:450px; height:80px"></iframe>
        
        <a href="http://www.poski.com/" onclick="return !window.open(this.href)">Webdesign Poski.com</a>   
        
         <div class="clearing"></div>
       </div>
   
       
<?php if ($GLOBALS['page']['location'] == "_poradi/get") 
{       
 echo '-->';
}
?>

 </div> <!-- #content -->
    
  </div> <!-- #lead -->