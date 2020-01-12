var _quoteButtonsHooked = false;

function quotePost(id)
{
	var xmlhttp;
	if (window.XMLHttpRequest) 
		xmlhttp = new XMLHttpRequest();
	else 
		xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");

	xmlhttp.onreadystatechange = function()
	{
		if(xmlhttp.status >= 400)
		{
			window.alert("Error: Couldn't quote that post, got " + xmlhttp.status + " status code.");
			return;
		}

		if(xmlhttp.readyState == 4)
			updatePostboxText(xmlhttp.responseText);
	};
	
	xmlhttp.open("GET", "./quote.php?id=" + id, true);
	xmlhttp.send();
}

function updatePostboxText(text)
{
	var seperator = text.indexOf("\n");
	var author = text.substring(0, seperator);
	var post = text.substring(seperator + 1);

	if(author == "Error")
	{
		alert("The server couldn't retrive quote data for this post:\n" + post);
		return;
	}

	
	var quote = "[quote " + author + "]\n" + post + "\n[/quote]";
	var postbox = document.getElementById("replytext");

	var line = postbox.value.substring(0, postbox.selectionStart);
	if(postbox.value.length == 0)
		line += "";
	else
		line += "\n";

	postbox.value = line + quote + postbox.value.substring(line.length - 1);
	postbox.focus();
}

function hookQuoteButtons()
{
	if(_quoteButtonsHooked)
		return;

	var buttons = document.getElementsByClassName("quoteButtonClass");

	for(var i = 0; i < buttons.length; i++)
	{
		var button = buttons[i];
		var postID = button.quotePostID;

		button.addEventListener('click', function(event)
		{
			quotePost(event.target.attributes.quotepostid.value);
		});
	}

	_quoteButtonsHooked = true;
}

window.addEventListener('DOMContentLoaded', function()
{
	hookQuoteButtons();
});

window.addEventListener('load', function()
{
	hookQuoteButtons();
});