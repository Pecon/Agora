<?php
global $items_per_page, $_page;
$page = intval($_page);
$start = ceil($page * $items_per_page);
$num = $items_per_page;

global $site_name;
$description = "Welcome to $site_name!";

// Get the data of all displayed threads
$sql = 'SELECT * FROM `topics` ORDER BY `sticky` DESC, `lastposttime` DESC LIMIT ?, ?';
$result = DBConnection::execute($sql, [$start, $num]);

$threads = Array();
while($thread = $result -> fetch_assoc())
	array_push($threads, $thread);

$totalTopics = DBConnection::execute('SELECT COUNT(*) AS `count` FROM `topics`') -> fetch_assoc()['count'];

if(count($threads) > 0)
{
	// Get all the creators for all these threads
	$sql = 'SELECT `id`, `username` FROM `users` WHERE ';
	$inputBindings = [];

	foreach($threads as $thread)
	{
		$sql = $sql . '`id` = ? OR ';
		array_push($inputBindings, $thread['creatorUserID']);
	}

	$sql = $sql . ' `id` IS NULL';

	$result = DBConnection::execute($sql, $inputBindings);
	$users = Array();
	while($user = $result -> fetch_assoc())
		array_push($users, $user);

	$allUserID = Array();
	foreach($users as $user)
		$allUserID[$user['id']] = $user;

	unset($users);
	unset($user);

	// Get all last posts for these threads
	$sql = 'SELECT `postID`, `userID`, `postDate` FROM `posts` WHERE ';
	$inputBindings = [];

	foreach($threads as $thread)
	{
		$sql = $sql . '`postID` = ? OR ';
		array_push($inputBindings, $thread['lastpostid']);
	}
	$sql = $sql . ' `postID` IS NULL';

	$result = DBConnection::execute($sql, $inputBindings);
	$posts = Array();
	while($post = $result -> fetch_assoc())
	{
		array_push($posts, $post);
	}

	$allPostID = Array();
	foreach($posts as $post)
		$allPostID[$post['postID']] = $post;

	unset($posts);
	unset($post);

	$description = $description . "\nRecent topics:";
	print('<div class="boardContainer">');
	// print("<tr><td>Topic name</td><td class=\"startedby\">Author</td><td>Last post by</td></tr>");
	
	print('<div class="boardHeader">');
	displayPageNavigationButtons($page, $totalTopics, null, true);
	print('</div>');

	foreach($threads as $index => $row)
	{
		$topicID = $row['topicID'];
		$topicName = $row['topicName'];

		$numPosts = DBConnection::execute('SELECT COUNT(*) AS `count` FROM `posts` WHERE `topicID` = ?', [$topicID]) -> fetch_assoc()['count'];
		$creator = $allUserID[$row['creatorUserID']];
		$creatorName = $creator['username'];
		$description = $description . "\n$topicName, by $creatorName";

		if(!boolval($row['locked']) && !boolval($row['sticky']))
			$threadStatus = "";
		else
			$threadStatus = (boolval($row['sticky']) ? '<span class="icon stickyTopic" title="This thread is sticky and will always stay at the top of the board."></span>' : "") . (boolval($row['locked']) ? '<span class="icon lockedTopic" title="This thread is locked and cannot be posted in."></span>' : "");


		$lastPost = $allPostID[$row['lastpostid']];
		$lastPostTime = date("F d, Y H:i:s", $lastPost['postDate']);
		$postUserName = findUserByID($lastPost['userID'])['username'];

		if($index == 0)
			$topicEntryClass = " firstTopicEntry";
		else if($index == count($threads) - 1)
			$topicEntryClass = " lastTopicEntry";
		else
			$topicEntryClass = "";

		print("<div class=\"topicEntry{$topicEntryClass}\"><div class=\"topicTitle\">{$threadStatus} <a href=\"./?topic={$topicID}\">{$topicName}</a> <span class=finetext>");

		global $items_per_page;
		if($numPosts > $items_per_page) // Don't show page nav buttons if there is only one page
			displayPageNavigationButtons(0, $numPosts, "topic={$topicID}", true);

		$numReplies = $numPosts - 1;

		print("</span><br /><span class=\"finetext\">Started by <a href=\"./?action=viewProfile&amp;user={$row['creatorUserID']}\">{$creatorName}</a></span></div><div class=\"topicReplies\">{$numReplies} " . ($numReplies == 1 ? "Reply" : "Replies") . "</div><div class=\"topicDate\">Last post by <a href=\"./?action=viewProfile&amp;user={$lastPost['userID']}\">{$postUserName}</a> on {$lastPostTime}</div></div>\n");
	}

	print('<div class="boardFooter">');
	displayPageNavigationButtons($page, $totalTopics, null, true);
	print('</div></div>');
}
else
	print("There are no threads to display!");

setPageDescription($description);
?>