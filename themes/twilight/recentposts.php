<?php
	global $_page, $_user, $items_per_page;
	$start = $_page * $items_per_page;
	$num = $items_per_page;

	if(!isSet($_user))
		$_user = null;
	else
		$_user = findUserByID($_user);

	$title = "All recent posts";

	if($_user == null)
		$sql = "SELECT * FROM posts ORDER BY postID DESC LIMIT {$start},{$num}";
	else
	{
		$title = "Recent posts of ${_user['username']}";
		$sql =  "SELECT * FROM posts WHERE userID=${_user['id']} ORDER BY postID DESC LIMIT {$start},{$num}";
	}

	setPageTitle($title);

	$result = querySQL($sql);

	if($result -> num_rows > 0)
	{
		if($_user == null)
			$numPosts = querySQL("SELECT COUNT(*) FROM posts;") -> fetch_assoc()["COUNT(*)"];
		else
			$numPosts = querySQL("SELECT COUNT(*) FROM posts WHERE userID=${_user['id']};") -> fetch_assoc()["COUNT(*)"];

		print('<div class="topicContainer">');
		print("<div class=\"topicHeader\"><span>&nbsp;&rarr;&nbsp;</span><h3>$title</h3>\n");

		if($_user == null)
			displayPageNavigationButtons($_page, $numPosts, "action=recentposts", true);
		else
			displayPageNavigationButtons($_page, $numPosts, "action=recentposts&user=${_user['id']}", true);

		print("</div>");

		$posts = Array();
		while($post = $result -> fetch_assoc())
			array_push($posts, $post);

		$backgroundSwitch = false;
		foreach($posts as $index => $post)
		{
			$topic = findTopicbyID($post['topicID']);
			$user = findUserByID($post['userID']);
			$username = $user['username'];
			$date = date("F d, Y H:i:s", $post['postDate']);
			$topicPage = floor($post['threadIndex'] / $items_per_page);

			$backgroundSwitch = !$backgroundSwitch;

			if($index === 0)
				print('<div class="post topPost' . ($backgroundSwitch ? " postBackgroundA" : " postBackgroundB") . '">');
			else
				print('<div class="post' . ($backgroundSwitch ? " postBackgroundA" : " postBackgroundB") . '">');

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
			print("<img class=\"avatar\" src=\"./avatar.php?user=${post['userID']}&amp;cb=${user['avatarUpdated']}\" /><div class=\"postDate finetext\">${date}</div><div class=\"userPostSeperator\"></div></div>");


			// Display the post body
			print("\n<div class=\"postBody\"><div class=\"postText\">\n<a href=\"./?action=gotopost&amp;post=${post['postID']}\">\n${topic['topicName']}\n</a>\n<hr />\n${post['postPreparsed']}\n</div>");

			// Moving on to the post controls
			print("\n<div class=\"postFooter\">");


			// If admin, show the delete button
			if(isSet($_SESSION['loggedin']))
			{
				if($_SESSION['admin'])
					print("<a class=\"inPostButtons\" href=\"./?action=deletepost&amp;post=${post['postID']}\">Delete</a> ");
			}

			// If logged in and there are edits, display the view edits button
			if($post['changeID'] > 0 && isSet($_SESSION['userid']))
				print("<a class=\"inPostButtons\" href=\"./?action=viewedits&amp;post=${post['postID']}\">View&nbsp;edits</a> ");

			// Display the permalink button and wrap up.
			print("<a class=\"inPostButtons\" href=\"./?action=gotopost&amp;post=${post['postID']}\">Permalink</a></div></div></div>\n");
		}
		
		print("\n<div class=\"topicFooter\">");

		if($_user == null)
			displayPageNavigationButtons($_page, $numPosts, "action=recentposts", true);
		else
			displayPageNavigationButtons($_page, $numPosts, "action=recentposts&user=${_user['id']}", true);

		print("</div>\n</div>\n");
	}
	else
	{
		print("There are no posts to display!");
	}
?>