<?php
	ob_start();
	session_start();
	$pageTitle = "REforum";
	$metaTags = "<meta HTTP-EQUIV=\"Pragma\" content=\"no-cache\">
				<meta HTTP-EQUIV=\"Expires\" content=\"-1\">";
	include_once './header.php';
?>
<center>
<?php
	include_once 'functions.php';
	include_once 'navmenu.php';
	if(isSet($_GET['action']))
	{
		switch(strToLower($_GET['action']))
		{
			case "post":
				reauthuser();
				
				if(!isSet($_POST['postcontent']))
				{
					error("Form error.");
					return;
				}
				
				else if(!isSet($_SESSION['loggedin']))
				{
					error("You don't have permission to do this action.");
				}
				
				else if($_SESSION['banned'] == true)
				{
					error("You are banned.");
				}
				
				else if(isSet($_POST['preview']))
				{
					print("Here is a preview of your post.<br>\n<table border=1 class=forumtable><tr><td class=postcontent>\n");
					$postStuff = htmlentities($_POST['postcontent']);
					$preview = bb_parse(str_replace("\n", "<br>", $postStuff));
					
					print($preview);
					print("\n</td></tr></table><br>\n<form action=\"./?action=post&topic={$_GET['topic']}&page={$_GET['page']}\" method=POST accept-charset=\"ISO-8859-1\">
						<textarea name=postcontent class=postbox>{$postStuff}</textarea>
						<br>
						<input type=submit name=post value=Post>
						<input type=submit name=preview value=Preview>
						</form><br>
						");
				}
				
				else if($_SESSION['lastpostingtime'] > time() - 20)
				{
					error("Please wait a minute before posting.");
				}
				
				else if(!isSet($_GET['topic']))
				{
					error("You need to be in a topic to post.");
				}
				else if(strLen(trim($_POST['postcontent'])) < 3)
				{
					error("Please make your post longer.");
				}
				else if(strLen(trim($_POST['postcontent'])) > 10000)
				{
					error("Your post is over the 10000 character limit.");
				}
				else if($_SESSION['lastpostdata'] == $_POST['postcontent'])
				{
					error("Oops! Looks like you already tried to post that message.");
				}
				else if(isSet($_POST['postcontent']))
				{
					// print_r($_POST);
					$postID = createPost($_SESSION['userid'], intVal($_GET['topic']), $_POST['postcontent']);
					print("Post successful!<script> window.location = \"./?topic={$_GET['topic']}&page={$_GET['page']}#{$postID}\"; </script>");
					$_SESSION['lastpostdata'] = $_POST['postcontent'];
					$_SESSION['lastpostingtime'] = time();
				}
				
				break;
				
			case "edit":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You don't have permission to do this action.");
					break;
				}
				if(!isSet($_GET['post']))
				{
					error("No post specified.");
					break;
				}
				$post = fetchSinglePost(intVal($_GET['post']));
				if($post['userID'] !== $_SESSION['userid'] && !$_SESSION['admin'] == true)
				{
					error("You do not have permission to edit this post!");
					break;
				}
				else if(!isSet($_POST['editpost']))
				{
					print("Editing post<br>\n<form method=post action=\"./?action=edit&post={$_GET['post']}&topic={$_GET['topic']}&page={$_GET['page']}\" accept-charset=\"ISO-8859-1\"><textarea name=editpost class=postbox>{$post['postData']}</textarea><br>\n<input type=submit value=Edit></form>\n");
				}
				else if(strLen(trim($_POST['editpost'])) > 10000)
				{
					error("Your post is over the 10000 character limit.");
				}
				else
				{
					editPost($post['userID'], $post['postID'], $_POST['editpost']);
					print("Post edited.<script> window.location = \"./?topic={$_GET['topic']}&page={$_GET['page']}#{$post['postID']}\"; </script>");
				}
				
				break;
				
			case "recentposts":
				getRecentPosts(0, 40);
				break;
				
			case "newtopic":
				reauthuser();
				
				if(!isSet($_SESSION['loggedin']))
				{
					error("You don't have permission to do this action.");
				}
				
				else if($_SESSION['banned'] == true)
				{
					error("You are banned.");
					break;
				}
				
				else if($_SESSION['lastpostingtime'] > time() - 20)
				{
					error("Please wait a minute before posting.");
					break;
				}
				
				else if(isSet($_POST['newtopicsubject']) && isSet($_POST['newtopicpost']))
				{
					if(strLen(trim($_POST['newtopicsubject'])) < 3)
					{
						error("Please make your topic title longer.");
						break;
					}
					else if(strLen(trim($_POST['newtopicsubject'])) > 130)
					{
						error("Topic title is longer than the 130 character maximum.");
						break;
					}
					else if(strLen(trim($_POST['newtopicpost'])) < 3)
					{
						error("Please make your post longer.");
						break;
					}
					else if(strLen(trim($_POST['newtopicpost'])) > 10000)
					{
						if(!$_SESSION['admin'])
						{
							error("Your post is over the 10000 character limit. Size: " . strLen(trim($_POST['newtopicpost'])));
							break;
						}
						else if(strLen(trim($_POST['newtopicpost'])) > 100000)
						{
							error("Your post is over the 100000 character hard limit. Size: " . strLen(trim($_POST['newtopicpost'])));
							break;
						}
						else
						{
							$threadID = createThread($_SESSION['userid'], $_POST['newtopicsubject'], $_POST['newtopicpost']);
							print("<script> window.location = \"./?topic={$threadID}\"; </script>");
							$_SESSION['lastpostdata'] = $_POST['newtopicsubject'];
						}
					}
					else if($_SESSION['lastpostdata'] == $_POST['newtopicsubject'])
					{
						error("Oops! Looks like you already tried to post that message.");
						break;
					}
					else
					{
						$threadID = createThread($_SESSION['userid'], $_POST['newtopicsubject'], $_POST['newtopicpost']);
						print("<script> window.location = \"./?topic={$threadID}\"; </script>");
						$_SESSION['lastpostdata'] = $_POST['newtopicsubject'];
					}	
				}
				
				else
				{
					print("<form action=\"./?action=newtopic\" method=POST accept-charset=\"ISO-8859-1\">
							Subject: <input type=text name=newtopicsubject><br>
							Original post:<br>
							<textarea class=postbox name=newtopicpost accept-charset=\"ISO-8859-1\"></textarea><br>
							<input type=submit value=\"Create thread\">
						</form>");
				}
				break;
				
			case "viewprofile":
				if(!isSet($_GET['user']))
				{
					error("No profile was specified.");
					break;
				}
				
				displayUserProfile(intVal($_GET['user']));
				break;
				
			case "viewedits":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You don't have permission to do this action.");
					break;
				}
				else if(!isSet($_GET['post']))
				{
					error("No post specified.");
					break;
				}
				else
				{
					displayPostEdits(intVal($_GET['post']));
				}
				break;
				
			case "updateprofile":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You do not have permission to do this action.");
					break;
				}
				else if(!isSet($_POST['updateProfileText']))
				{
					error("Did you forget to put something here?");
					break;
				}
				
				updateUserProfileText($_SESSION['userid'], $_POST['updateProfileText'], $_POST['tagline'], $_POST['website']);
				displayUserProfile(intVal($_SESSION['userid']));
				break;
				
			case "search":
				print("");
				break;
				
			default:
				error("Unknown action.");
				break;
		}
	}
	if(isSet($_GET['topic']))
	{
		if(!isSet($_GET['page']))
		{
			$page = 0;
		}
		else
		$page = intVal($_GET['page']);

		displayThread(intVal($_GET['topic']), $page);
	}
	else if(!isSet($_GET['action']))
	{
		showRecentThreads(0, 20);
		if(isSet($_SESSION['loggedin']))
			print("<br><br><a href=\"./?action=newtopic\">Post a new topic</a>\n");
		
		print("<br><br><a href=\"./?action=recentPosts\">Show all recent posts</a>\n");
		
		if(isSet($_SESSION['admin']))
			if($_SESSION['admin'])
				print("<br><a href=\"./admin.php\">Admin</a>");
	}
?>
<br><br><div class=finetext>REforum is &#169; 2016 pecon.us <a href="./about.html">About</a></div>
</center>
</body>
</html>