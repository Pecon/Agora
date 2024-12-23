<?php
global $items_per_page, $_page;
$page = intval($_page);
$start = ceil($page * $items_per_page);
$num = $items_per_page;

global $site_name;
$description = "Welcome to $site_name!";

// Get the data of all displayed threads
$sql = "SELECT * FROM topics ORDER BY sticky DESC, lastposttime DESC LIMIT {$start},{$num}";
$result = querySQL($sql);

$threads = Array();
while($thread = $result -> fetch_assoc())
	array_push($threads, $thread);

$totalTopics = querySQL("SELECT COUNT(*) FROM topics") -> fetch_assoc()['COUNT(*)'];

if(count($threads) > 0)
{
	// Get the post counts for all these threads
	// $sql = "SELECT COUNT(*) FROM topics ORDER BY sticky DESC, lastposttime DESC LIMIT {$start},{$num}";
	// $counts = querySQL($sql);
	// Nevermind I need to find a way to do this.

	// Get all the creators for all these threads
	$sql = "SELECT id, username FROM users WHERE ";

	foreach($threads as $thread)
		$sql = $sql . "ID='${thread['creatorUserID']}' OR ";
	$sql = $sql . " ID=null;";

	$result = querySQL($sql);
	$users = Array();
	while($user = $result -> fetch_assoc())
		array_push($users, $user);

	$allUserID = Array();
	foreach($users as $user)
		$allUserID[$user['id']] = $user;

	unset($users);
	unset($user);

	// Get all last posts for these threads
	$sql = "SELECT postID, userID, postDate FROM posts WHERE ";

	foreach($threads as $thread)
		$sql = $sql . "postID='${thread['lastpostid']}' OR ";
	$sql = $sql . " postID=null;";

	$result = querySQL($sql);
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

		$numPosts = querySQL("SELECT COUNT(*) FROM posts WHERE topicID=${topicID};") -> fetch_assoc()['COUNT(*)'];
		$numPosts = intval($numPosts);
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

		print("<div class=\"topicEntry${topicEntryClass}\"><div class=\"topicTitle\">${threadStatus} <a href=\"./?topic=${topicID}\">${topicName}</a> <span class=finetext>");

		global $items_per_page;
		if($numPosts > $items_per_page) // Don't show page nav buttons if there is only one page
			displayPageNavigationButtons(0, $numPosts, "topic=${topicID}", true);

		$numReplies = $numPosts - 1;

		print("</span><br /><span class=\"finetext\">Started by <a href=\"./?action=viewProfile&amp;user={$row['creatorUserID']}\">{$creatorName}</a></span></div><div class=\"topicReplies\">${numReplies} " . ($numReplies == 1 ? "Reply" : "Replies") . "</div><div class=\"topicDate\">Last post by <a href=\"./?action=viewProfile&amp;user={$lastPost['userID']}\">{$postUserName}</a> on {$lastPostTime}</div></div>\n");
	}

	print('<div class="boardFooter">');
	displayPageNavigationButtons($page, $totalTopics, null, true);
	print('</div></div>');
}
else
	print("There are no threads to display!");

setPageDescription($description);
?>