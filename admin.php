<?php
	session_start();
	$pageTitle = "REforum - Admin";
	$metaTags = "<meta HTTP-EQUIV=\"Pragma\" content=\"no-cache\">
				<meta HTTP-EQUIV=\"Expires\" content=\"-1\">";
	require_once './header.php';
	print("<center>");
	require_once './navmenu.php';
	?>
		<h1>Admin</h1>
<?php
	require_once './functions.php';
	
	function adminLog($stuff)
	{
		$file = fopen("./admin.log", "a");
		$file.fwrite($file, time() . " " . $_SESSION['name'] . " " . $_SESSION['BL_ID'] . " " . $stuff . "\r\n");
		
		fclose($file);
	}

	if(!isSet($_SESSION['loggedin']))
	{
		error("You are not logged in.");
		return;
	}
	else if(!$_SESSION['admin'])
	{
		error("You do not have permission to use this page.");
		return;
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
					return;
				}

				$newtext = "[delete]" . $postdata['postPreparsed'] . "[/delete]

				[color=red]User was banned for this post. Reason: " . $_POST['fortext'] . "[/color]";

				editPost($_SESSION['userid'], $_POST['forpost'], $newtext);
				error("Ban message added.");
				adminLog("Banned " . $_POST['ban'] . " for " . $_POST['forpost'] . " " . $_POST['fortext']);
				return;
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
				error("<br>Could not delete post.");
				return;
			}

			error("Post deleted. <br>");
			adminLog("Deleted " . $_POST['ban'] . "Post author: " . $postAuthor . " Content: " . $postContent);
		}
	}
?>

<form method="POST">
	Ban this person: <input type="text" name="ban" value="userid"><input type="text" name="forpost" value="postid"><input type="text" name="fortext" value="Reason"><input type="submit" value="BAN">
</form>
<br><br>
<form method="POST">
	Delete this post: <input type="text" name="deletepost" value="postid"><input type="submit" value="DELETE">
</form>
</center>
</body>
</html>

