<?php
	global $_title, $_preview, $_user;
	$user = $_user;

	print('<div class="topicContainer">');
	print("<div class=\"topicHeader\"><span>&nbsp;&rarr;&nbsp;</span><h3>${_title}</h3></div>");
	print('<div class="post topPost postBackgroundA">');
	print("\n<div class=\"postUser\"><a class=\"userLink\" name=\"${user['id']}\"></a><a class=\"userLink\" href=\"./?action=viewProfile&amp;user=${user['id']}\">${user['username']}</a>");

	// Display the user's tagline
	if($user['banned'])
		print("<div class=\"userTagline taglineBanned finetext\">${user['tagline']}</div>");
	else if($user['usergroup'] == 'admin')
		print("<div class=\"userTagline taglineAdmin finetext\">${user['tagline']}</div>");
	else
		print("<div class=\"userTagline tagline finetext\">${user['tagline']}</div>");


	// Display the user's avatar and the post date
	$date = date("F d, Y H:i:s");
	print("<img class=\"avatar\" src=\"./avatar.php?user=${user['id']}&amp;cb=${user['avatarUpdated']}\" /><div class=\"postDate finetext\">${date}</div><div class=\"userPostSeperator\"></div></div>");

	// Display the post body
	print("\n<div class=\"postBody\"><div class=\"postText\">${_preview}</div><div class=\"postFooter\">&nbsp;</div></div></div>");

	print('<div class="topicFooter"></div></div>');
?>