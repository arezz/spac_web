<div class="homeObsah">
	<table>
    <tr>
      <td>
              <!-- BlueBoard.cz Anketa -->
              <div id="blok-bbsluzba-969800"></div>
              <!-- <a id="odkaz-bbsluzba-969800" href="http://miniaplikace.blueboard.cz">Miniaplikace</a> -->
              <script type="text/javascript" src="http://miniaplikace.blueboard.cz/widget-anketa-969800"></script>
              <!-- BlueBoard.cz Anketa KONEC -->
              
              <br />
               <!-- BlueBoard.cz Anketa -->
               <div id="blok-bbsluzba-968962">  
              <!-- <a id="odkaz-bbsluzba-968962" href="http://blueboard.cz">BlueBoard.cz</a>     -->
              <script type="text/javascript" src="http://miniaplikace.blueboard.cz/widget-anketa-968962"></script> 
              <!-- BlueBoard.cz Anketa KONEC -->       
      </td>
      <td>
        &nbsp;
      </td>
      <td align="left">
      	<h1><a href="http://www.spac-os.cz/aktuality/"><span>Aktuality</span></a></h1>
      	<br />
        
      	<ul>
      		<?php
      			foreach (qa("SELECT `id`, `title_cs` FROM `##news` ORDER BY `ocreated` DESC LIMIT 3") as $row)
      				echo '<li><a href="/aktuality/'.U::urlize($row['title_cs']).'-'.$row['id'].'">' . HTML::e($row['title_cs']) . '</a></li>';
      		?>
      	</ul>
        
      	<div>
      	  <h3>Novinky: </h3>
      		<?php
      			$temp0 = m('pages_simple')->load('dalsi-zavod');
      			echo $temp0['text'];
      		?>
      	</div>   
	
	      </td>
    </tr>
	</table>
	
	<!--
  <h2>Alešův koutek</h2>
  
	<ul>      
		<?php
			foreach (qa("SELECT `id`, `title_cs` FROM `##_mistrovstvi_cr` ORDER BY `ocreated` DESC LIMIT 1") as $row)
				echo '<li><a href="/mistrovstvi-cr/'.U::urlize($row['title_cs']).'-'.$row['id'].'">' . HTML::e($row['title_cs']) . '</a></li>';
		?>
		 <li><a href="/mistrovstvi-cr/">Vše možné za světa cyklistiky</a></li> 
	</ul>             -->
        
</div>