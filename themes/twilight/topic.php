<?php
	global $items_per_page, $_topicID, $_page, $_script_nonce;

	$start = $_page * $items_per_page;
	$end = $items_per_page;

	$row = findTopicbyID($_topicID, true);
	$creator = findUserbyID($row['creatorUserID']);
	if($row === false)
	{
		error("Failed to load topic.");
		return;
	}
	setPageTitle($row['topicName']);
	setPageDescription("Topic ${row['topicName']} by ${creator['username']}.");
	$topicControls = "";

	if(isSet($_SESSION['userid']))
	{
		$quotesEnabled = true;
		$quoteString = Array();
		print("<script src=\"./js/quote.js\" type=\"text/javascript\" nonce=\"${_script_nonce}\" async></script>\n");

		if($row['creatorUserID'] == $_SESSION['userid'] || $_SESSION['admin'])
			$topicControls = "<a href=\"./?action=locktopic&amp;topic=${_topicID}\">" . (boolval($row['locked']) ? "Unlock" : "Lock") . " topic</a> &nbsp;&nbsp;";

		if($_SESSION['admin'])
		{
			$sql = "SELECT postID FROM posts WHERE topicID='${_topicID}' AND threadIndex='0';";
			$result = querySQL($sql);
			$result = $result -> fetch_assoc();

			$topicControls = $topicControls . "<a href=\"./?action=stickytopic&amp;topic=${_topicID}\">" . (boolval($row['sticky']) ? "Unsticky" : "Sticky") . " topic</a> &nbsp;&nbsp; <a href=\"./?action=deletepost&amp;post=${result['postID']}\">Delete topic</a> &nbsp;&nbsp; ";
		}
	}
	else
		$quotesEnabled = false;

	if(!boolval($row['locked']) && !boolval($row['sticky']))
		$topicStatus = "&nbsp;&rarr;&nbsp;";
	else
		$topicStatus = (boolval($row['sticky']) ? '<span class="icon stickyTopic"></span>' : "") . (boolval($row['locked']) ? '<span class="icon lockedTopic"></span>' : "");

	print('<article class="topicContainer">');
	print("<header class=\"topicHeader\"><span>${topicStatus}</span><h3><a href=\"./?topic=${row['topicID']}\">${row['topicName']}</a></h3>\n<br />${topicControls}<br />\n");

	$numPosts = querySQL("SELECT COUNT(*) FROM posts WHERE topicID=${_topicID};") -> fetch_assoc()["COUNT(*)"];
	displayPageNavigationButtons($_page, $numPosts, "topic=${_topicID}", true);
	print("</header>");

	// Get all the posts into one array
	$sql = "SELECT * FROM posts WHERE topicID='${_topicID}' ORDER BY threadIndex ASC LIMIT ${start}, ${end}";
	$posts = querySQL($sql);

	$allPosts = Array();
	while($post = $posts -> fetch_assoc())
		array_push($allPosts, $post);

	// Write one query to get all the user data we need
	$sql = "SELECT id, username, usergroup, banned, tagline, avatarUpdated FROM users WHERE ";
	foreach($allPosts as $post)
		$sql = $sql . "id = '${post['userID']}' OR ";

	$sql = $sql . " id = '-1';";
	$users = querySQL($sql);

	if($users === false)
	{
		error("No users for this topic exist!");
		finishPage();
		return;
	}

	$allUsers = Array();
	while($user = $users -> fetch_assoc())
		array_push($allUsers, $user);

	$allPostsUsers = Array();
	foreach($allUsers as $user)
		$allPostsUsers[$user['id']] = $user;

	unset($users);
	unset($user);
	unset($allUsers);
	unset($posts);
	unset($post);

	$backgroundSwitch = false;

	// Loop through and write each post
	$count = count($allPosts);
	for($i = 0; $i < $count; $i++)
	{
		$post = $allPosts[$i];
		$user = $allPostsUsers[$post['userID']];

		$username = $user['username'];

		// Highlight the post if applicable, swap background colors
		$backgroundSwitch = !$backgroundSwitch;
		$postClasses = "post";

		if(!$post['threadIndex']) // OP. Apply special style to distinguish it from the rest of the thread.
			$postClasses .= " originalPost";

		if($i == 0) // Top post of the page. Apply top post style so that it is not missing a top border.
			$postClasses .= " topPost";

		$postClasses .= $backgroundSwitch ? " postBackgroundA" : " postBackgroundB";

		print('<section class="' . $postClasses . '">');

		// Display username of poster
		print("\n<div class=\"postUser\"><a class=\"userLink\" name=\"${post['postID']}\"></a><a class=\"userLink\" href=\"./?action=viewProfile&amp;user=${post['userID']}\">${username}</a>");


		// Display the user's tagline
		if($user['banned'])
			print("<div class=\"userTagline taglineBanned finetext\">${user['tagline']}</div>");
		else if($user['usergroup'] == 'admin')
			print("<div class=\"userTagline taglineAdmin finetext\">${user['tagline']}</div>");
		else
			print("<div class=\"userTagline tagline finetext\">${user['tagline']}</div>");

		// Display the user's avatar and the post date
		$date = date("F d, Y H:i:s", $post['postDate']);
		$timeTag = date('c', $post['postDate']);
		print("<img class=\"avatar\" src=\"./avatar.php?user=${post['userID']}&amp;cb=${user['avatarUpdated']}\" /><time datetime=\"${timeTag}\" class=\"postDate finetext\">${date}</time><div class=\"userPostSeperator\"></div></div>");


		// Display the post body
		print("\n<div class=\"postBody\"><article class=\"postText\">${post['postPreparsed']}</article>");


		// Moving on to the post controls
		print("\n<div class=\"postFooter\">");


		// If admin, show the delete button
		if(isSet($_SESSION['loggedin']))
		{
			if($_SESSION['admin'])
				print("<a class=\"inPostButtons\" href=\"./?action=deletepost&amp;post=${post['postID']}\">Delete</a> ");
		}


		// If logged in, show the quote button
		if($quotesEnabled)
		{
			print("<noscript style=\"display: inline;\"><a class=\"inPostButtons\" href=\"./?topic=${_topicID}" . (isSet($_GET['page']) ? "&amp;page=${_GET['page']}" : "") . "&amp;quote=${post['postID']}#editor\">Quote/Reply</a></noscript><a class=\"inPostButtons javascriptButton quoteButtonClass\" quotepostid=\"${post['postID']}\" href=\"#editor\">Quote/Reply</a> ");

			if(isSet($_GET['quote']))
			{
				if(intval($_GET['quote']) == $post['postID'])
				{
					$quoteString['data'] = $post['postData'];
					$quoteString['author'] = $user['username'];
				}
			}


			// If the post owner, show the edit button
			if($post['userID'] == $_SESSION['userid'] && !$row['locked'])
				print("<a class=\"inPostButtons\" href=\"./?action=edit&amp;post={$post['postID']}&amp;topic=${_topicID}" . (isSet($_GET['page']) ? "&amp;page=${_GET['page']}" : "&amp;page=0") . "\">Edit&nbsp;post</a> ");
		}


		// If logged in and there are edits, display the view edits button
		if($post['changeID'] > 0 && isSet($_SESSION['userid']))
			print("<a class=\"inPostButtons\" href=\"./?action=viewedits&amp;post=${post['postID']}\">View&nbsp;edits</a> ");

		// Display the permalink button and wrap up.
		print("<a class=\"inPostButtons\" href=\"./?action=gotopost&amp;post=${post['postID']}\">Permalink</a></div></div></section>\n");
	}
	print("\n<div class=\"topicFooter\">");

	displayPageNavigationButtons($_page, $numPosts, "topic=${_topicID}", true);

	print("</div>\n</article>\n");

	if(isSet($_SESSION['loggedin']) && !boolval($row['locked']))
	{
		if(isSet($quoteString['data']))
		{
			global $_postContentPrefill;
			$_postContentPrefill = "[quote " . $quoteString['author'] . "]\n" . $quoteString['data'] . "[/quote]";
		}

		directLoadThemePart("form-post");
	}
?>