{a url='get-url'|l:$this->group}{a url=#lpath#|cat:$url}
<a href="{$url}">zpět</a><br /><br />

{ax object=$G.result.object}
{ae pieces='image;ocreated;summary;text;images;attachment;'|explode:';'}
{foreach item=piece from=$pieces}
	{if ($piece|piece:$skip:true)}
		{'template-'|cat:$this->part|cat:'-piece-'|cat:$piece|cat:'-before'|l:$this->group:false}
	{/if}
	{if ($piece|piece:$skip:'image')}
		{$Core::common('thumbnail', $object, null, false)}
	{/if}
	{if ($piece|piece:$skip:'ocreated')}
		<span class="{$piece}">{$object->ocreated|date_format:$date_format}</span>
	{/if}
	{if ($piece|piece:$skip:'title')}
		<span class="{$piece}">{$object->title|h}</span>
	{/if}
	{if ($piece|piece:$skip:'summary')}
		<div class="{$piece}">
			{$object->summary|cut:$cut_length|h}
		</div>
	{/if}
	{if ($piece|piece:$skip:'text')}
		<div class="{$piece}">
			{$object->text}
		</div>
	{/if}
	{if ($piece|piece:$skip:'images')}
		{$Core::common('images', $object)}
	{/if}
	{if ($piece|piece:$skip:'attachment')}
		{if ($object->attachment)}
			<a href="/data/blob-rename/{$object->attachment}/">příloha</a> 
		{/if} 
	{/if}
	{if ($piece|piece:$skip:true)}
		{'template-'|cat:$this->part|cat:'-piece-'|cat:$piece|cat:'-after'|l:$this->group:false}
	{/if}
{/foreach}
