<div class="homeObsah">
	<table>
    <tr>
      <td>
             
              <div id="blok-bbsluzba-973381"></div>
              <!-- <a id="odkaz-bbsluzba-973381" href="http://miniaplikace.blueboard.cz">Miniaplikace</a>    -->
              <script type="text/javascript" src="http://miniaplikace.blueboard.cz/widget-anketa-973381"></script>

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