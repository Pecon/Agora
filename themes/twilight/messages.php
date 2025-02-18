<?php
	global $items_per_page, $_page, $_sent;
	$sent = $_sent;
	$page = intval($_page);
	$start = ($page * $items_per_page);
	$num = $items_per_page;
	$user = findUserByID($_SESSION['userid']);

	$sql = 'SELECT * FROM `privateMessages` WHERE ' . ($sent ? '`senderID` = ?' : '`recipientID` = ?') . ($sent ? '' : ' AND `deleted` = 0') . ' ORDER BY `messageDate` DESC LIMIT ?, ?';
	$result = DBConnection::execute($sql, [$_SESSION['userid'], $start, $num]);

	$description = "Message inbox for {$user['username']}.";
	setPageTitle("Message " . ($sent ? "outbox" : "inbox"));

	$threadStatus = "";

	if($result -> num_rows > 0)
	{
		$description = $description . ($sent ? "\nRecently sent messages:" : "\nRecent messages:");
		?>
	<div class="boardContainer">
		<div class="boardHeader">
		<?php
		print('<h2>' . ($sent ? "Outbox" : "Inbox") . '</h2>');

		$messages = $result -> fetch_all(MYSQLI_ASSOC);

		$sql = 'SELECT COUNT(*) AS `count` FROM `privateMessages` WHERE ' . ($sent ? '`senderID`' : '`recipientID`') . ' = ?';
		$totalMessages = DBConnection::execute($sql, [$_SESSION['userid']]) -> fetch_assoc()['count'];

		displayPageNavigationButtons($page, $totalMessages, "action=" . ($sent ? "outbox" : "messaging"), true);
		print('</div>');

		foreach($messages as $index => $row)
		{
			$messageID = $row['messageID'];
			$subject = $row['subject'];

			$creator = findUserByID($sent ? $row['recipientID'] : $row['senderID']);
			$creatorName = $creator['username'];
			$description = $description . "\n$subject, " . ($sent ? "to" : "from") . " $creatorName";

			switch(intval($row['read']))
			{
				case 0:
					$threadStatus = '<span class="icon messageUnread" title="Unread message"></span>';
					break;

				case 1:
					$threadStatus = '<span class="icon messageRead" title="Message has been read"></span>';
					break;

				case 2:
					$threadStatus = '<span class="icon messageReplied" title="Message has been replied to"></span>';
					break;

				default:
					$threadStatus = '<span class="icon messageRead"></span>';
					break;


			}
			if($index == 0)
				$topicEntryClass = " firstTopicEntry";
			else if($index == count($messages) - 1)
				$topicEntryClass = " lastTopicEntry";
			else
				$topicEntryClass = "";

			$sentTime = date("F d, Y H:i:s", $row['messageDate']);
			print("<div class=\"topicEntry{$topicEntryClass}\"><div class=\"topicTitle\">{$threadStatus} <a href=\"./?action=messaging&amp;id=$messageID \">$subject</a><br /><span class=\"finetext\">From <a href=\"./?action=viewProfile&amp;user={$creator['id']}\">{$creatorName}</a></span></div><div class=\"topicDate\">$sentTime</div>" . (!$sent ? '<div class="topicReplies"><form method="POST" action="./?action=deletemessage"><input type="hidden" name="id" value="' . $messageID . '" /><input type="hidden" name="actionSecret" value="' . $_SESSION['actionSecret'] . '"><input type="submit" value="Delete" /></form></div>' : '') . '</div>');
		}

		print('<div class="boardFooter">');
		displayPageNavigationButtons($page, $totalMessages, "action=" . ($sent ? "outbox" : "messaging"), true);
		print('</div></div>');
	}
	else
		info("No messages.", "Messaging");

	print("<br /><br /><a href=\"./?action=" . ($sent ? 'messaging' : 'outbox') . "\">View " . ($sent ? 'inbox' : 'outbox') . "</a><br /><br />");
	print("<a href=\"./?action=composemessage\">Compose message</a><br /><br />");

	setPageDescription($description);