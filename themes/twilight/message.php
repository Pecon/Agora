<?php
	global $_id;
	$message = fetchSingleMessage($_id);

	if($message === false)
	{
		error("Could not find that message.");
		return false;
	}

	$sender = findUserbyID($message['senderID']);
	$recipient = $message['recipientID'];

	if($_SESSION['userid'] != $sender['id'] && $_SESSION['userid'] != $recipient && !$_SESSION['admin'])
	{
		error("You do not have permission to view this message.");
		return false;
	}

	setPageTitle($message['subject']);

	print('<div class="topicContainer">');
	print("<div class=\"topicHeader\"><span>&nbsp;&rarr;&nbsp;</span><h3>Viewing private message: {$message['subject']}</h3></div>");
	print('<div class="post topPost postBackgroundA">');
	print("\n<div class=\"postUser\"><a class=\"userLink\" name=\"{$sender['id']}\"></a><a class=\"userLink\" href=\"./?action=viewProfile&amp;user={$sender['id']}\">{$sender['username']}</a>");

	// Display the user's tagline
	if($sender['banned'])
		print("<div class=\"userTagline taglineBanned finetext\">{$sender['tagline']}</div>");
	else if($sender['usergroup'] == 'admin')
		print("<div class=\"userTagline taglineAdmin finetext\">{$sender['tagline']}</div>");
	else
		print("<div class=\"userTagline tagline finetext\">{$sender['tagline']}</div>");


	// Display the user's avatar and the post date
	$date = date("F d, Y H:i:s", $message['messageDate']);
	print("<img class=\"avatar\" src=\"./avatar.php?user={$sender['id']}&amp;cb={$sender['avatarUpdated']}\" /><div class=\"postDate finetext\">{$date}</div><div class=\"userPostSeperator\"></div></div>");

	// Display the post body
	print("\n<div class=\"postBody\"><div class=\"postText\">{$message['messagePreparsed']}</div></div>");

	print('</div><div class="topicFooter"></div></div>');


	if($_SESSION['userid'] == $recipient)
	{
		if(!$message['read'])
		{
			// Mark as read
			$sql = "UPDATE privateMessages SET `read` = '1' WHERE `messageID` = '{$message['messageID']}';";
			$result = querySQL($sql);

			if($result === false)
				error("Failed to set message as read.");
			else
				$_SESSION['unreadMessages'] -= 1;
		}

		global $_postContentPrefill, $_recipientPrefill, $_subjectPrefill;
		$_postContentPrefill = "[quote {$sender['username']}]\n{$message['messageData']}\n[/quote]";
		$_recipientPrefill = $sender['username'];
		$_subjectPrefill = 'RE: ' . $message['subject'];

		directLoadThemePart("form-messagereply");
	}
		
	return true;