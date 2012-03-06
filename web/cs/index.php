<div class="hImg">
	<img src="/web/_images/homeImg.jpg" alt="" title="" class="homeImg" />	
</div>

<div class="homeObsah">
	<table>
    <tr>
      <td>
        <h2><a href="http://www.spac-os.cz/aktuality/"><span>Aktuality</span></a></h2>      
      </td>
      <td align="right">
        <h3><a href="http://www.spac-os.cz/mistrovstvi-cr/">Alešův koutek</a></h3>
      </td>
    </tr>
	</table>
  
	<ul>
		<?php
			foreach (qa("SELECT `id`, `title_cs` FROM `##news` ORDER BY `ocreated` DESC LIMIT 3") as $row)
				echo '<li><a href="/aktuality/'.U::urlize($row['title_cs']).'-'.$row['id'].'">' . HTML::e($row['title_cs']) . '</a></li>';
		?>
	</ul>
  
	<div>
		<?php
			$temp0 = m('pages_simple')->load('dalsi-zavod');
			echo $temp0['text'];
		?>
	</div>
	
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