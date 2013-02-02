{** BLOCK detail **}
	{ax object=$G.result.object}
	{a url='get-url'|l:$this->group}{a url=#lpath#|cat:$url}
	
	<a href="{$url}">zpět na seznam</a><br /><br />

	<div class="info">
		<h3>Informace</h3>
		<strong>Datum:</strong> {$object->datum|date_format:$date_format}<br />
		<strong>Typ:</strong> {$object->typ}<br />
		<br />
	</div>
	
	<div class="organizer">
		<h3>Pořadatel</h3>
		{$object->poradatel_jmeno}<br />
		{if ($object->poradatel_telefon)}{$object->poradatel_telefon}<br />{/if}
		{$object->poradatel_email}<br />
		{if ($object->poradatel_web)}<a href="{$object->poradatel_web}">{$object->poradatel_web}</a><br />{/if}
		{if ($object->poradatel_text)}{$object->poradatel_text}<br />{/if}
		<br />
	</div>
	
	<div class="text">
		{$object->text}
	</div>
{** BLOCK list **}
	{a url='get-url'|l:$this->group}{a url=#lpath#|cat:$url}
	<table>
		<tr>
			<th>Datum:</th>
			<th>Den:</th>
			<th>Název:</th>
			<th>Typ:</th>
			<th>Pořadatel:</th>
			<th>&nbsp;</th>
		</tr>
	{foreach item=object from=$G.result.objects.list}
		<tr class="{cycle values='grey,'}">
    		<td class="date">{$object->datum|date_format:$date_format}</td>
    		<td class="day">{a d=$object->datum|getdate}{a d='wday-'|cat:$d.wday}{$d|l:'@core'}</td>
    		<td class="name"><a href="{$url}{$object->url|h}">{$object->nazev|h}</a></td>
    		<td class="type">{$object->typ|h}</td>
    		<td class="organizer">{$object->poradatel_jmeno}</td>
		    <td class="actions">
		    	{if ($object->vysledky)}
		    		<a href="/data/blob-rename/{$object->vysledky}/{$object->nazev|urlize}-vysledky.{$object->vysledky|regex_replace:'~.*\.([a-zA-Z]+)$~':'\1'}">výsledky</a>
		    	{/if}
		    	{if ($object->propozice)}
		    		<a href="/data/blob-rename/{$object->propozice}/{$object->nazev|urlize}-propozice.{$object->propozice|regex_replace:'~.*\.([a-zA-Z]+)$~':'\1'}">propozice</a>
		    	{/if}
		    	{if (($object->datum > time()) && ($object->datum < (time()+3600*24*14)))}
		    		<a href="http://igmk.xf.cz/cyklistika/prihlaseni-zavodnika.php">přihlášení</a>
		    	{/if}
		    </td>
		</tr>
	{/foreach}
</table>
<br>
Jiné závody najdete na webu <a href="http://mssc.websnadno.cz/Uvod.html">MSSC</a><br />
Také na stránkách <a href="http://cyklo.matera.cz/">Cyklo Matera</a><br />
Zde naleznete závody <a href="http://www.jesenickysnek.com/index.php/kalenda">Jesenického šneka</a><br />
<br />
Další závody v roce 2013:<br />
8.5. st - Orlová  <br />
25.-26.5. - krajské přebory  <br />
5.7. pá - Farma tour Hlubočec    <br />


{** BLOCK @get **}
	{a result=$G.invocation->dispatch()}
	{$Core::title()}
	{$Core::common('messages-output')}

	{if ($G.result.object->g)}
		{template __file='::list'}
	{else}
		{template __file='::detail'}
	{/if}
{** ENDBLOCK **}
 