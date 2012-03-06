{** BLOCK detail **}
	{ax object=$G.result.object}
	<div class="text">
		{$object->text}
	</div>
	{foreach item=thumbnail from=$G.result.objects.tree}
		{if (!$thumbnail->g)}
			{$Core::common('thumbnail', $thumbnail, $thumbnail->title, false)}
		{/if}
	{/foreach}
{** BLOCK list-group **}
	{a url='get-url'|l:$this->group}{a url=#lpath#|cat:$url}
	<a class="title" href="{$url}{$object->url|h}">{$object->title|h}</a>
{** BLOCK list-groups **}
	{foreach item=object from=$G.result.objects.tree}
		{if ($object->g)}
			{template __file='::list-group' object=$object}
		{/if}
	{/foreach}	
{** BLOCK list **}
	{*ax count=5}
	{sub name='list-sub'}
		<div class="items level{$level}">
		{foreach item=object from=$objects}
			{if ($object->g)}
				<div class="item">
					<a class="title" href="{$url}{$object->url|h}">{$object->title|h}</a>
					{counter assign=i start=0}
					{foreach item=thumbnail from=$object->x.tree}
						{if (($i < $count) && (!$thumbnail->g))}
							{$Core::common('thumbnail', $thumbnail, $thumbnail->title, false)}
							{counter assign=i}
						{/if}
					{/foreach}
					{subuse name='list-sub' objects=$object->x.tree level=$level+1}
				</div>
			{/if}
		{/foreach}
		</div>
	{/sub}
	{subuse name='list-sub' objects=$G.result.objects.tree level=0*}
	
	{template __file='::list-groups'}
{** BLOCK @get **}
	{a result=$G.invocation->dispatch()}
	{$Core::title()}
	{$Core::common('messages-output')}

	{template __file='::detail'}
	{template __file='::list'}

	{if (!$G.result.object->oid)}
		{$G.site.base[0]|cat:'web/@fotogalerie.html'|file_get_contents}
	{/if}
{** ENDBLOCK **}
