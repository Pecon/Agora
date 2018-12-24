<?php
	global $_page, $items_per_page;
	$start = $_page * $items_per_page;
	$num = $items_per_page;

	$sql = "SELECT * FROM posts ORDER BY postID DESC LIMIT {$start},{$num}";
	$result = querySQL($sql);

	if($result -> num_rows > 0)
	{
		$numPosts = querySQL("SELECT COUNT(*) FROM posts;") -> fetch_assoc()["COUNT(*)"];

		print('<div class="topicContainer">');
		print("<div class=\"topicHeader\"><span>&nbsp;&rarr;&nbsp;</span><h3>Viewing all recent posts</h3>\n");
		displayPageNavigationButtons($_page, $numPosts, "action=recentposts", true);
		print("</div>");

		$posts = Array();
		while($post = $result -> fetch_assoc())
			array_push($posts, $post);

		$backgroundSwitch = false;
		foreach($posts as $index => $post)
		{
			$topic = findTopicbyID($post['threadID']);
			$user = findUserByID($post['userID']);
			$username = $user['username'];
			$date = date("F d, Y H:i:s", $post['postDate']);
			$topicPage = floor($post['threadIndex'] / $items_per_page);

			if($index === 0)
				print('<div class="post originalPost' . ($backgroundSwitch ? " postBackgroundA" : " postBackgroundB") . '">');
			else
				print('<div class="post' . ($backgroundSwitch ? " postBackgroundA" : " postBackgroundB") . '">');

			// Display username of poster
			print("\n<div class=\"postUser\"><a class=\"userLink\" name=\"${post['postID']}\"></a><a class=\"userLink\" href=\"./?action=viewProfile&amp;user=${post['userID']}\">${username}</a>");


			// Display the user's tagline
			if($user['banned'])
				print("<div class=\"userTagline taglineBanned finetext\">${user['tagline']}</div>");
			else if($user['administrator'])
				print("<div class=\"userTagline taglineAdmin finetext\">${user['tagline']}</div>");
			else
				print("<div class=\"userTagline tagline finetext\">${user['tagline']}</div>");

			// Display the user's avatar and the post date
			$date = date("F d, Y H:i:s", $post['postDate']);
			print("<img class=\"avatar\" src=\"./avatar.php?user=${post['userID']}&amp;cb=${user['avatarUpdated']}\" /><div class=\"postDate finetext\">${date}</div><div class=\"userPostSeperator\"></div></div>");


			// Display the post body
			print("\n<div class=\"postBody\"><div class=\"postText\">\n<a href=\"./?topic=${post['threadID']}&amp;page=${topicPage}#${post['postID']}\">\n${topic['topicName']}\n</a>\n<hr />\n${post['postPreparsed']}\n</div>");

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
			print("<a class=\"inPostButtons\" href=\"./?topic=${post['threadID']}&amp;page=${topicPage}#${post['postID']}\">Permalink</a></div></div></div>\n");
		}
		
		print("\n<div class=\"topicFooter\">");
		displayPageNavigationButtons($_page, $numPosts, "action=recentposts", true);
		print("</div>\n</div>\n");
	}
	else
	{
		print("There are no posts to display!");
	}
?>