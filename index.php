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
					$preview = bb_parse(str_replace("\n", "<br>", htmlentities(html_entity_decode($postStuff))));
					
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
				
			case "avatarchange":
				if(isSet($_FILES['avatar']))
				{
					if($_FILES['avatar']['error'] !== UPLOAD_ERR_OK)
						error("An error occurred while uploading your avatar. Please try again.<br /><a href=\"./?action=avatarchange\">Continue</a><script> window.setTimeout(function(){window.location.href = \"./?action=avatarchange\";}, 3000);</script>");
					else if($_FILES['avatar']['size'] > 2024000)
						error("Your avatar file is too large. Try to keep it under 2MB.<br /><a href=\"./?action=avatarchange\">Continue</a><script> window.setTimeout(function(){window.location.href = \"./?action=avatarchange\";}, 3000);</script>");
					else
					{
						$location = "./data/avatartemp_${_SESSION['userid']}.dat";
						
						updateAvatarByID($_SESSION['userid'], $location);
						
						print("Avatar uploaded successfully.<br /><a href=\"./?action=viewprofile&user=${_SESSION['userid']}\">Continue</a><script> window.setTimeout(function(){window.location.href = \"./?action=viewprofile&user=${_SESSION['userid']}\";}, 3000);</script>");
					}
				}
				else
				{
					?>
					<form enctype="multipart/form-data" method="POST">
						Avatar upload: <input type="file" accept=".jpg,.png,.gif,.bmp" name="avatar" />
						<input type="submit" value="Upload" />
					</form><br />
					png, jpg, bmp, and gif files supported<br />
					Non-PNG images will be converted to PNG.<br />
					For best results, make your avatar a PNG of 100x100px or smaller.
					<?php
				}
				break;
				
			case "passwordchange":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You have to be logged in to do this action.");
					break;
				}
				
				if(isSet($_POST['oldpassword']) && isSet($_POST['newpassword']) && isSet($_POST['confirmnewpassword']))
				{
					if(password_verify($_POST['oldpassword'], getPasswordHashByID($_SESSION['userid'])))
					{
						if($_POST['newpassword'] == $_POST['confirmnewpassword'])
						{
							updatePasswordByID($_SESSION['userid'], password_hash($_POST['newpassword'], PASSWORD_BCRYPT));
							print("Your password has been updated.<br /><a href=\"./?action=viewprofile&user=${_SESSION['userid']}\">Continue</a><script> window.setTimeout(function(){window.location.href = \"./?action=viewprofile&user=${_SESSION['userid']}\";}, 3000);</script>");
						}
						else
							error("The new passwords you entered didn't match.<br /><a href=\"./?action=passwordchange\">Try again</a> <script> window.setTimeout(function(){window.location.href = \"./?action=passwordchange\";}, 3000);</script>");
					}
					else
						error("Incorrect password.<br /><a href=\"./?action=passwordchange\">Try again</a> <script> window.setTimeout(function(){window.location.href = \"./?action=passwordchange\";}, 3000);</script>");
				}
				else
				{
					?>
					<form action="./?action=passwordchange" method="POST">
						Old password: <input type="password" name="oldpassword" /><br />
						New password: <input type="password" name="newpassword" /><br />
						Confirm new password: <input type="password" name="confirmnewpassword" /><br />
						<input type="submit" value="Update password" />
					</form>
					<?php
				}
				break;
			
			case "emailchange":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You have to be logged in to do this action.");
					break;
				}
				
				if(isSet($_POST['newemail']))
				{
					if(updateEmailByID($_SESSION['userid'], $_POST['newemail']))
						print("Your email has been updated.<br /><a href=\"./?action=viewprofile&user=${_SESSION['userid']}\">Continue</a> <script> window.setTimeout(function(){window.location.href = \"./?action=viewprofile&user=${_SESSION['userid']}\";}, 3000);</script>");
					else
						error("That is not a valid email address.<br /><a href=\"./?action=emailchange\">Try again</a> <script> window.setTimeout(function(){window.location.href = \"./?action=emailchange\";}, 3000);</script>");
						
				}
				else
				{
					?>
					<form action="./?action=emailchange" method="POST">
						Enter new email address: <input class="validate" type="email" name="newemail" />
						<input type="submit" value="Update email" />
					</form>
					<?php
				}
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