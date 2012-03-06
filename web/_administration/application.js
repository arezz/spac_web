Application.prototype = {
};

function Application()
{
}

Application.prototype.load = function(url)
{	
	this.loadPanel(url, Math.round(Math.random() * 10000000));
}

Application.prototype.loadPanel = function (url, panel)
{
	if ($('#panel_' + panel).length == 0)
		$('#workspace_placeholder').after('<iframe name="panel_'+panel+'" src="about:blank" id="panel_'+panel+ '" onload="application.loaded(\''+panel+'\')"></iframe>');
	var iframe = document.getElementById('panel_' + panel);
	iframe.originalUrl = url;
	iframe.panel = panel;
	url += '&page.panel=' + panel;
	if (iframe.contentWindow.G)
		url += '&page.viewstate=' + iframe.contentWindow.G.page.viewstate;
	
	iframe.contentDocument.body.innerHTML = '<img class="wait" src="'+G.site.path+'web/_administration/images/wait.gif" title="Loading ..." alt="Loading ..." />';
	iframe.src = url; 
}

Application.prototype.loaded = function(panel)
{
	var messages = $('#messages');
	var iframe = document.getElementById('panel_' + panel);

	iframe.contentDocument.body.style.margin = '0';

	var title = iframe.contentDocument.title;
	if (!title)
		title = iframe.src;

	var info = iframe.contentDocument.createElement('div');
	info.className = 'info'; 
	info.id = 'info';
	info.style.padding = '1ex';
	info.style.background = 'black';
	info.style.color = 'white';
	info.style.fontFamily = 'Verdana,Arial,Helvetica,sans-serif';
	info.style.fontSize = '10pt';
	info.style.fontWeight = 'bold';
	info.innerHTML = '';
	info.innerHTML += '<span style="margin-right: 1ex; padding: 0 3px; border: 1px solid white; cursor: pointer;" onclick="d = window.parent.document; p = d.getElementById(\'panel_'+panel+'\'); p.parentNode.removeChild(p);">X</span>';
	info.innerHTML += '<span style="margin-right: 1ex; padding: 0 3px; border: 1px solid white; cursor: pointer;" onclick="d = window.parent.document; p = d.getElementById(\'panel_'+panel+'\'); window.parent.application.loadPanel(p.originalUrl, p.panel);">O</span>';
	info.innerHTML += '<strong>'+title+'</strong>'; 
	iframe.contentDocument.body.insertBefore(info, iframe.contentDocument.body.childNodes[0]); 	

	var messages_html = iframe.contentDocument.getElementById('messages');
	if (messages_html && messages_html.innerHTML)
	{
		var messages_block_id = Math.round(Math.random() * 10000000);
		messages.append('<div id="messages_'+messages_block_id+'">' + messages_html.innerHTML + '</div>');
		setTimeout("$('#messages_"+messages_block_id+"').slideUp(500)", 5000);
	}
	
	iframe.style.height = (iframe.contentDocument.body.scrollHeight + 25) + 'px';
	
}

var application;
if (window.parent == window)
	application = new Application();
else
	application = window.parent.application;
