<?php
	require_once 'page.php';
	require_once 'functions.php';

	setPageTitle("$site_name - Admin");
	addToBody("<h1>Admin</h1>");

	if(!isSet($_SESSION['loggedin']))
	{
		error("You are not logged in.");
		finishPage();
	}
	else if(!$_SESSION['admin'])
	{
		error("You do not have permission to use this page.");
		finishPage();
	}
	else
	{
		if(isSet($_POST['ban']))
		{
			banUserByID(intval($_POST['ban']));

			if(isSet($_POST['forpost']));
				$forpost = intval($_POST['forpost']);

			if(isSet($_POST['fortext']));
				$forreason = htmlentities($_POST['fortext']);

			if($forpost > 0)
			{
				$postdata = fetchSinglePost($_POST['forpost']);

				if(!$postdata)
				{
					error("This post does not exist.");
					finishPage();
				}

				$newtext = "[delete]" . $postdata['postPreparsed'] . "[/delete]

				[color=red]User was banned for this post. Reason: " . $_POST['fortext'] . "[/color]";

				editPost($_SESSION['userid'], $_POST['forpost'], $newtext);
				error("Ban message added.");
				adminLog("Banned " . $_POST['ban'] . " for " . $_POST['forpost'] . " " . $_POST['fortext']);
				finishPage();
			}
			else
			{
				error("User was banned for... something.");
				adminLog("Banned " . $_POST['ban'] . " (no reason)");
			}
		}

		if(isSet($_POST['deletepost']))
		{
			$post = fetchSinglePost($_POST['deletepost']);
			$postContent = str_replace("\r", "", str_replace("\n", "  ", $post['postData']));
			$postAuthor = $post['userID'];

			$success = deletePost(intval($_POST['deletepost']));

			if($success === false)
			{
				error("Could not delete post.");
				finishPage();
			}

			error("Post deleted.");
			adminLog("Deleted post# " . $_POST['deletepost'] . ". Post author: " . $postAuthor . ". Content: " . $postContent);
		}
	}

	$form = <<<EOT
<form method="POST">
	Ban this person: <input type="text" name="ban" value="userid"><input type="text" name="forpost" value="postid"><input type="text" name="fortext" value="Reason"><input type="submit" value="BAN">
</form>
<br /><br />
<form method="POST">
	Delete this post: <input type="text" name="deletepost" value="postid"><input type="submit" value="DELETE">
</form>
EOT;
	addToBody($form);

	finishPage();
?>