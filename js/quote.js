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

	
	var quote = "\n[quote " + author + "]" + post + "\n[/quote]";
	var postbox = document.getElementById("replytext");

	var line;
	if(postbox.innerHTML.length == 0)
		line = "";
	else
		line = "\n";

	postbox.innerHTML = postbox.innerHTML + line + quote;
}