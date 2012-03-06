<div class="loga">
</div>

{$Core::common('pages', $G.result.pager, 'top')}
{foreach item=object from=$G.result.objects.list}
	{template __file='::list-item' object=$object}
{/foreach}
{$Core::common('pages', $G.result.pager, 'bottom')} 