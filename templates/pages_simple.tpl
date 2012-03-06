{** BLOCK @get **}
	{$Core::title()}
	{$Core::common('messages-output')}

	{a data=$Core::get('pages_simple-data')}
	{if ($data)}
		{$data.text}
	{/if}
{** ENDBLOCK **}
