<?php
	global $_script_start, $_startDirectory;
	$_script_start = microtime(true);
	$_startDirectory = __DIR__;

	require_once 'functions.php';
	require_once 'ratelimit.php';
	require_once 'database.php';
	require_once 'page.php';

	setPageTitle($site_name);
	reauthuser();

	if(isSet($_GET['action']))
	{
		switch(strToLower($_GET['action']))
		{
			case "gotopost":
				if(!isSet($_GET['post']))
				{
					error("No postID specified.");
					break;
				}

				$link = getPostLink($_GET['post']);

				if($link === false)
				{
					header('Status: 404 Not Found');
					error("The post specified could not be found. The post may have been deleted or the link you followed may be malformed.");
					break;
				}

				header("Location: $link");
				info('Your browser has been informed of the location of this post and should have redirected you to it, but if you are reading this then that probably didn\'t happen. To view your requested post, please <a href="' . $link . '">click here</a> to complete your redirect manually.', "Redirect");

				break;
			case "login":
				loadThemePart("login");
				break;

			case "logout":
				loadThemePart("logout");
				break;

			case "register":
				loadThemePart("register");
				break;

			case "post":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}

				if(!isSet($_POST['postcontent']))
				{
					error("Form error.");
				}

				else if($_SESSION['banned'] == true)
				{
					error("You are banned.");
				}

				else if(isSet($_POST['preview']))
				{
					$postStuff = $_POST['postcontent'];
					$preview = bb_parse($postStuff);

					global $_title, $_preview, $_postContentPrefill, $_user;
					$_title = "Post Preview";
					$_preview = $preview;
					$_postContentPrefill = htmlentities($postStuff);
					$_user = findUserByID($_SESSION['userid']);
					$_page = intval($_GET['page']);
					$_topicID = intval($_GET['topic']);

					loadThemePart("preview");
					loadThemePart("form-post");
				}
				else if(!isSet($_GET['topic']))
				{
					error("You need to be in a topic to post.");
				}
				else if(strLen(trim($_POST['postcontent'])) < 3)
				{
					error("Please make your post longer.");

					$postStuff = $_POST['postcontent'];

					global $_postContentPrefill, $_page, $_topicID;
					$_postContentPrefill = htmlentities($postStuff);
					$_page = intval($_GET['page']);
					$_topicID = intval($_GET['topic']);

					loadThemePart("form-post");
				}
				else if(strLen(trim($_POST['postcontent'])) > 10000)
				{
					error("Your post is over the 10000 character limit.");

					$postStuff = $_POST['postcontent'];

					global $_postContentPrefill, $_page, $_topicID;
					$_postContentPrefill = htmlentities($postStuff);
					$_page = intval($_GET['page']);
					$_topicID = intval($_GET['topic']);

					loadThemePart("form-post");
				}
				else if($_SESSION['lastpostdata'] == $_POST['postcontent'])
				{
					error("Oops! Looks like you already tried to post that message.");
				}
				else if(!checkRateLimitAction("post", 20, 1))
				{
					warn("The last post created from your IP was less than 20 seconds ago, please wait a bit before posting.");
					$postStuff = $_POST['postcontent'];

					global $_postContentPrefill, $_page, $_topicID;
					$_postContentPrefill = htmlentities($postStuff);
					$_page = intval($_GET['page']);
					$_topicID = intval($_GET['topic']);

					loadThemePart("form-post");
				}
				else if(isSet($_POST['postcontent']))
				{
					$postID = createPost($_SESSION['userid'], intval($_GET['topic']), $_POST['postcontent']);
					info("Post successful!", "Post topic");
					header("Location: ./?topic=${_GET['topic']}&page=${_GET['page']}#${postID}");
					$_SESSION['lastpostdata'] = $_POST['postcontent'];
				}

				break;

			case "edit":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}
				if(!isSet($_GET['post']))
				{
					error("No post specified.");
					break;
				}
				$post = fetchSinglePost(intval($_GET['post']));
				if($post['userID'] !== $_SESSION['userid'] && !$_SESSION['admin'] == true)
				{
					error("You do not have permission to edit this post!");
					break;
				}
				else if(!isSet($_POST['editpost']))
				{

				}
				else if(isSet($_POST['preview']))
				{
					$postStuff = $_POST['editpost'];
					$preview = bb_parse($postStuff);

					global $_title, $_preview, $_user;
					$_title = "Edit Preview";
					$_preview = $preview;
					$_user = findUserByID($_SESSION['userid']);

					loadThemePart("preview");
				}
				else if(strLen(trim($_POST['editpost'])) < 3)
				{
					error("Please make your post longer.");
				}
				else if(strLen(trim($_POST['editpost'])) > 10000 && !$_SESSION['admin'])
				{
					error("Your post is over the 10000 character limit.");
				}
				else if(strLen(trim($_POST['editpost'])) > 30000)
				{
					error("Your post is over the 30000 character hard limit.");
				}
				else if(!checkRateLimitAction("edit", 20, 2))
				{
					error("You need to wait a moment before editing again.");
				}
				else if(isSet($_POST['editpost']) && !isSet($_POST['preview']))
				{
					editPost($post['userID'], $post['postID'], $_POST['editpost']);

					if(isSet($_POST['edittopicsubject']))
						editTopicTitle($post['topicID'], $_POST['edittopicsubject']);

					header("Location: ./?topic=${_GET['topic']}&page=${_GET['page']}#${post['postID']}");
					break;
				}

				global $_postContentPrefill, $_subjectPrefill, $_postID, $_topicID, $_page;

				$_postID = $_GET['post'];
				$_topicID = $_GET['topic'];
				$_page = $_GET['page'];

				if(isSet($_POST['edittopicsubject']))
					$_subjectPrefill = htmlentities($_POST['edittopicsubject']);
				else if($post['threadIndex'] == 0)
					$_subjectPrefill = findTopicByID($post['topicID'])['topicName'];

				if(isSet($_POST['editpost']))
					$_postContentPrefill = htmlentities($_POST['editpost']);
				else
					$_postContentPrefill = $post['postData'];

				loadThemePart("form-edit");
				break;

			case "recentposts":
				if(!isSet($_GET['page']))
					$_page = 0;
				else
					$_page = intval($_GET['page']);

				if(isSet($_GET['user']))
					$_user = intval($_GET['user']);

				loadThemePart("recentposts");
				break;

			case "newtopic":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}

				else if($_SESSION['banned'] == true)
				{
					error("You are banned.");
					break;
				}

				else if(isSet($_POST['newtopicsubject']) && isSet($_POST['newtopicpost']))
				{
					if(strLen(trim($_POST['newtopicsubject'])) < 3)
					{
						error("Please make your topic title longer.");

						global $_postContentPrefill, $_subjectPrefill;

						$_postContentPrefill = htmlentities($_POST['newtopicpost']);
						$_subjectPrefill = htmlentities($_POST['newtopicsubject']);

						loadThemePart("form-newtopic");
						break;
					}
					else if(strLen(trim($_POST['newtopicsubject'])) > 130)
					{
						error("Topic title is longer than the 130 character maximum.");

						global $_postContentPrefill, $_subjectPrefill;

						$_postContentPrefill = htmlentities($_POST['newtopicpost']);
						$_subjectPrefill = htmlentities($_POST['newtopicsubject']);

						loadThemePart("form-newtopic");
						break;
					}
					else if(strLen(trim($_POST['newtopicpost'])) < 3)
					{
						error("Please make your post longer.");

						global $_postContentPrefill, $_subjectPrefill;

						$_postContentPrefill = htmlentities($_POST['newtopicpost']);
						$_subjectPrefill = htmlentities($_POST['newtopicsubject']);

						loadThemePart("form-newtopic");
						break;
					}
					else if(isSet($_POST['preview']))
					{
						$preview = bb_parse($_POST['newtopicpost']);

						global $_title, $_preview, $_user;
						$_title = htmlentities($_POST['newtopicsubject']) . ' (Preview)';
						$_preview = $preview;
						$_user = findUserByID($_SESSION['userid']);

						loadThemePart("preview");


						global $_postContentPrefill, $_subjectPrefill;

						$_postContentPrefill = htmlentities($_POST['newtopicpost']);
						$_subjectPrefill = htmlentities($_POST['newtopicsubject']);

						loadThemePart("form-newtopic");
						break;
					}
					else if(strLen(trim($_POST['newtopicpost'])) > 30000)
					{
						if(!$_SESSION['admin'])
						{
							error("Your post is over the 30000 character limit. Size: " . strLen(trim($_POST['newtopicpost'])));

							global $_postContentPrefill, $_subjectPrefill;

							$_postContentPrefill = htmlentities($_POST['newtopicpost']);
							$_subjectPrefill = htmlentities($_POST['newtopicsubject']);

							loadThemePart("form-newtopic");
							break;
						}
						else if(strLen(trim($_POST['newtopicpost'])) > 100000)
						{
							error("Your post is over the 100000 character hard limit. Size: " . strLen(trim($_POST['newtopicpost'])));

							global $_postContentPrefill, $_subjectPrefill;

							$_postContentPrefill = htmlentities($_POST['newtopicpost']);
							$_subjectPrefill = htmlentities($_POST['newtopicsubject']);

							loadThemePart("form-newtopic");
							break;
						}
						else
						{
							$topicID = createThread($_SESSION['userid'], $_POST['newtopicsubject'], $_POST['newtopicpost']);
							header("Location: ./?topic=${topicID}");
							$_SESSION['lastpostdata'] = $_POST['newtopicsubject'];
						}
					}
					else if($_SESSION['lastpostdata'] == $_POST['newtopicsubject'])
					{
						error("Oops! Looks like you already tried to post that topic.");
						break;
					}
					else if(!checkRateLimitAction("post", 20, 1))
					{
						warn("The last post created from your IP was less than 20 seconds ago, please wait a bit before posting.");

						global $_postContentPrefill, $_subjectPrefill;

						$_postContentPrefill = htmlentities($_POST['newtopicpost']);
						$_subjectPrefill = htmlentities($_POST['newtopicsubject']);

						loadThemePart("form-newtopic");
						break;
					}
					else
					{
						$topicID = createThread($_SESSION['userid'], $_POST['newtopicsubject'], $_POST['newtopicpost']);
						header("Location: ./?topic=${topicID}");
						$_SESSION['lastpostdata'] = $_POST['newtopicsubject'];
					}
				}

				else
				{
					loadThemePart("form-newtopic");
				}
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
					global $_post;
					$_post = intval($_GET['post']);

					loadThemePart("edits");

					// displayPostEdits(intval($_GET['post']));
				}
				break;

			case "messaging":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}

				if(isSet($_GET['id']))
				{
					global $_id;
					$_id = intval($_GET['id']);

					loadThemePart("message");
					// displayMessage($_GET['id']);
				}
				else if(isSet($_GET['page']))
				{
					global $_page, $_sent;
					$_page = intval($_GET['page']);
					$_sent = false;

					loadThemePart("messages");
					// displayRecentMessages($_GET['page'], false);
				}
				else
				{
					global $_page, $_sent;
					$_page = 0;
					$_sent = false;

					loadThemePart("messages");
					// displayRecentMessages(0, false);
				}

				break;

			case "outbox":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}

				if(isSet($_GET['id']))
					displayMessage($_GET['id']);
				else if(isSet($_GET['page']))
				{
					global $_page, $_sent;
					$_page = intval($_GET['page']);
					$_sent = true;

					loadThemePart("messages");
					// displayRecentMessages($_GET['page'], true);
				}
				else
				{
					global $_page, $_sent;
					$_page = 0;
					$_sent = true;

					loadThemePart("messages");
					// displayRecentMessages(0, true);
				}

				break;

			case "composemessage":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}

				if(isSet($_POST['recipient']) && isSet($_POST['subject']) && isSet($_POST['postcontent']))
				{
					if(isSet($_POST['preview']))
					{
						// Create preview
						$postStuff = $_POST['postcontent'];
						$preview = bb_parse($postStuff);

						global $_title, $_preview, $_user;
						$_title = htmlentities($_POST['subject']) . ' (Preview)';
						$_preview = $preview;
						$_user = findUserByID($_SESSION['userid']);

						loadThemePart("preview");
					}
					else if(strLen($_POST['postcontent']) > 10000 && !$_SESSION['admin'])
					{
						error("Your message is over the 10000 character limit.");
					}
					else if(!checkRateLimitAction("sendMessage", 20, 1))
					{
						error("Please wait a bit before sending another message.");
					}
					else if(isSet($_POST['send']))
					{
						$success = sendMessage($_POST['postcontent'], $_POST['subject'], $_POST['recipient'], (isSet($_POST['replyID']) ? $_POST['replyID'] : -1));

						if($success)
						{
							info("Message sent successfully!", "Send message");
							header('location: ./?action=outbox');
							break;
						}
						else
						{
							info("Failed to send message.", "Send message");
						}
					}

					global $_postContentPrefill, $_recipientPrefill, $_subjectPrefill;

					$_postContentPrefill = htmlentities($_POST['postcontent']);
					$_recipientPrefill = htmlentities($_POST['recipient']);
					$_subjectPrefill = htmlentities($_POST['subject']);
				}

				loadThemePart("form-composemessage");

				break;

			case "deletemessage":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}

				if(isSet($_POST['id']))
				{
					$result = deleteMessage($_POST['id']);

					if($result)
					{
						header('location: ./?action=messaging');
						info("Successfully deleted message.", "Delete message");
					}
					else
						error("Could not delete message.");
				}
				else
					error("Invalid action.");

				break;

			case "viewprofile":
				if(!isSet($_GET['user']))
				{
					error("No profile was specified.");
					break;
				}

				displayUserProfile(intval($_GET['user']));
				break;

			case "updateprofile":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}
				else if(!isSet($_POST['updateProfileText']))
				{
					error("Did you forget to put something here?");
					break;
				}

				updateUserProfileText($_SESSION['userid'], $_POST['updateProfileText'], $_POST['tagline'], $_POST['website']);
				displayUserProfile($_SESSION['userid']);
				// header("Location: ./?action=viewprofile&user=${_SESSION['userid']}");
				break;

			case "avatarchange":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}

				if(!checkRateLimitAction("avatarChange", 5, 60))
				{
					error("Too many avatar changes, please wait a minute before trying to upload another avatar.");
					break;
				}

				if(isSet($_FILES['avatar']))
				{
					if($_FILES['avatar']['error'] !== UPLOAD_ERR_OK)
					{
						$error = $_FILES['avatar']['error'];

						if($error == UPLOAD_ERR_INI_SIZE)
							error("The uploaded file exceeds the maximum filesize this server is configured to support.<br /><a href=\"./?action=avatarchange\">Continue</a>");
						else if($error == UPLOAD_ERR_FORM_SIZE)
							error("The uploaded file exceeds the maximum filesize.<br /><a href=\"./?action=avatarchange\">Continue</a>");
						else if($error == UPLOAD_ERR_PARTIAL)
							error("The uploaded file did not finish uploading completely. Please try again.<br /><a href=\"./?action=avatarchange\">Continue</a>");
						else if($error == UPLOAD_ERR_NO_FILE)
							error("No file was uploaded.<br /><a href=\"./?action=avatarchange\">Continue</a>");
						else if($error == UPLOAD_ERR_NO_TMP_DIR)
						{
							error("Your avatar could not be processed because the server is missing a temporary directory to handle the upload.<br /><a href=\"./?action=avatarchange\">Continue</a>");
							addLogMessage("User's uploaded avatar could not be processed due to the lack of a PHP upload temp directory. This is a configuration problem.", 'error');
						}
						else if($error == UPLOAD_ERR_CANT_WRITE)
						{
							error("Your avatar could not be processed due to an issue writing data on the server. Please try again later.");
							addLogMessage("User's uploaded avatar could not be processed because of a disk write error. Is the system out of disk space? Permission issue?");
						}
						else
							error("An error occurred while uploading your avatar. Please try again.<br /><a href=\"./?action=avatarchange\">Continue</a>");

						addToHead("<meta http-equiv=\"refresh\" content=\"3;URL='./?action=avatarchange'\" />");
					}
					else if($_FILES['avatar']['size'] > 2024000)
					{
						error("Your avatar file is too large. Try to keep it under 2MB.<br /><a href=\"./?action=avatarchange\">Continue</a>");
						addToHead("<meta http-equiv=\"refresh\" content=\"3;URL='./?action=avatarchange'\" />");
					}
					else
					{
						$location = "./data/avatartemp_${_SESSION['userid']}.dat";

						$success = updateAvatarByID($_SESSION['userid'], $location);

						if($success)
						{
							addToHead("<meta http-equiv=\"refresh\" content=\"5;URL='./?action=viewprofile&user=${_SESSION['userid']}'\" />");
							//header("Location: ./?action=viewprofile&user=${_SESSION['userid']}");
							info("Avatar updated successfully.", "Avatar change");
							addLogMessage("User changed their avatar.", 'info');
						}
						else
						{
							addToHead("<meta http-equiv=\"refresh\" content=\"5;URL='./?action=avatarchange'\" />");
							error("Couldn't update avatar.");
						}
					}
				}
				else
				{
					$form = <<<EOT
					<form enctype="multipart/form-data" method="POST">
						Avatar upload: <input type="file" accept=".jpg,.jpeg,.png,.gif,.bmp,.webp" name="avatar" />
						<input class="postButtons" type="submit" value="Upload" />
					</form><br />
					png, jpg, bmp, webp, and gif files are supported<br />
					Non-png/gif images will be converted to png.<br />
					For best results, make your avatar a png of 100x100px or smaller.
EOT;
					addToBody($form);
				}
				break;

			case "passwordchange":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}

				if(isSet($_POST['oldpassword']) && isSet($_POST['newpassword']) && isSet($_POST['confirmnewpassword']))
				{
					if(password_verify($_POST['oldpassword'], getPasswordHashByID($_SESSION['userid'])))
					{
						if($_POST['newpassword'] == $_POST['confirmnewpassword'])
						{
							updatePasswordByID($_SESSION['userid'], password_hash($_POST['newpassword'], PASSWORD_BCRYPT));
							info("Your password has been updated.<br /><a href=\"./?action=viewprofile&user=${_SESSION['userid']}\">Continue</a>", "Change password");
							addToHead("<meta http-equiv=\"refresh\" content=\"3;URL='./?action=viewprofile&user=${_SESSION['userid']}'\" />");
						}
						else
							error("The new passwords you entered didn't match.<br /><a href=\"./?action=passwordchange\">Try again</a>");
							addToHead("<meta http-equiv=\"refresh\" content=\"3;URL='./?action=passwordchange'\" />");
					}
					else
						error("Incorrect password.<br /><a href=\"./?action=passwordchange\">Try again</a>");
						addToHead("<meta http-equiv=\"refresh\" content=\"3;URL='./?action=passwordchange'\" />");
				}
				else
				{
					$form = <<<EOT
					<form action="./?action=passwordchange" method="POST">
						Old password: <input type="password" name="oldpassword" /><br />
						New password: <input type="password" name="newpassword" /><br />
						Confirm new password: <input type="password" name="confirmnewpassword" /><br />
						<input class="postButtons" type="submit" value="Update password" />
					</form>
EOT;
					addToBody($form);
				}
				break;

			case "emailchange":
				if(isSet($_GET['code']) && isSet($_GET['id']))
				{
					if(!checkRateLimitAction("emailSecretAttempt", 5, 600))
					{
						error("Maximum attempts exceeded. Wait 10 minutes before trying again.");
						break;
					}
					if(verifyEmailChange($_GET['id'], $_GET['code']))
					{
						info("Your new email address was successfully verified!", "Change email");
						addToHead("<meta http-equiv=\"refresh\" content=\"5;URL='./'\" />");
						break;
					}
					else
					{
						error("Email verification failed.");
						break;
					}
				}
				else if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}

				if(isSet($_POST['newemail']))
				{
					if(!checkRateLimitAction("sendEmail", 600, 2))
					{
						error("Maximum number of email actions reached. Please wait 10 minutes before trying again.");
						break;
					}
					if(updateEmailByID($_SESSION['userid'], $_POST['newemail']))
					{
						if($require_email_verification)
							info("A confirmation email has been sent to the new email address. Please click the link in the email to confirm this change.", "Change email");
						else
						{
							info("Your email has been updated.<br /><a href=\"./?action=viewprofile&user=${_SESSION['userid']}\">Continue</a>", "Change email");
							addToHead("<meta http-equiv=\"refresh\" content=\"5;URL='./?action=viewprofile&user=${_SESSION['userid']}'\" />");
						}
					}
					else
					{
						error("That is not a valid email address.<br /><a href=\"./?action=emailchange\">Try again</a>");
						addToHead("<meta http-equiv=\"refresh\" content=\"5;URL='./?action=emailchange'\" />");
					}
				}
				else
				{
					$form = <<<EOT
					<form action="./?action=emailchange" method="POST">
						Enter new email address: <input class="validate" type="email" name="newemail" />
						<input class="postButtons" type="submit" value="Update email" />
					</form>
EOT;
					addToBody($form);
				}
				break;

			case "verify":
				if(!checkRateLimitAction("emailSecretAttempt", 5, 600))
				{
					error("Maximum attempts exceeded. Wait 10 minutes before trying again.");
					break;
				}

				$error = verifyAccount($_GET['code']);

				if($error === false)
				{
					error("Unable to verify account.");
					break;
				}

				info('Your account has been verified!<br /><a href="./?action=login">Log in</a>', "Account verification");
				addToHead("<meta http-equiv=\"refresh\" content=\"5;URL='./'\" />");
				break;

			case "resetpassword":
				if(isSet($_GET['code']) && isSet($_GET['id']))
				{
					if(!checkRateLimitAction("emailSecretAttempt", 5, 600))
					{
						error("Maximum attempts exceeded. Wait 10 minutes before trying again.");
						break;
					}
					if(getVerificationByID($_GET['id']) !== $_GET['code'])
					{
						error("This verifcation code is invalid.");
						break;
					}
					if(!isSet($_POST['newpassword']))
					{
						$form = <<<'EOD'
						<h1>Complete Password Reset</h1>
						<table border=1 style="align: center; padding: 3px;">
							<form method="POST">
								New password: <input type="password" class="" minLength="${min_password_length}" maxLength="72" name="newpassword" tabIndex="1" autocomplete="new-password" required pattern="(^[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\x27\x22,./<>?~][A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\x27\x22,./<>?~ ]{0,70}[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\x27\x22,./<>?~]$)" /><br />
								Confirm password: <input type="password" name="confirmpassword" tabIndex="2" /><br />
								<input class="postButtons" type="submit" value="Change password" tabIndex="3" />
							</form>
						</table>
EOD;
						addToBody($form);
						break;
					}
					else
					{
						// Verify password requirements
						// Matches a string between 2-72 characters with only alphanumeric characters, spaces, or most ascii special characters. Spaces are not allowed at the beginning or end of the string (typo protection since it's unlikely a user would want that intentionally).
						// This same expression is used in the form html to let the client self-validate.
						if(!preg_match('(^[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~][A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~ ]{0,70}[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~]$)', $_POST['newpassword']))
						{
							error('Password is not valid. Passwords can contain alphanumeric characters, spaces, and common special characters. Unicode characters are not allowed and the password cannot begin or end with a space.');
							break;
						}
						else if($_POST['newpassword'] !== $_POST['confirmpassword'])
						{
							error("The passwords you entered did not match.");
							break;
						}
						else if(strlen($_POST['newpassword']) < $min_password_length)
						{
							error("Error: Password is too short. Use at least ${min_password_length} characters. This is the only requirement aside from your password not being 'password'.");
							break;
						}
						else if(stripos($_POST['newpassword'], "password") !== false && strlen($_POST['password']) < 16)
						{
							error("You've got to be kidding me.");
							break;
						}

						$newPassword = password_hash($_POST['newpassword'], PASSWORD_BCRYPT);
						updatePasswordByID($_GET['id'], $newPassword);
						clearVerificationByID($_GET['id']);

						info('Password reset successful!<br /><a href="./?action=login">Log in</a>', "Reset password");
						break;
					}
				}
				if(!isSet($_POST['email']))
				{
					$form = <<<EOT
					<h1>Reset Password</h1>
					<table border=1 style="align: center; padding: 3px;">
						<form method="POST">
							Email address: <input type="text" name="email" class="validate" /><br />
							<input class="postButtons" type="submit" value="Send reset email">
						</form>
					</table>
EOT;
					addToBody($form);
					break;
				}
				if(!checkRateLimitAction("sendEmail", 600, 2))
				{
					error("Maximum number of email actions reached. Please wait 10 minutes before trying again.");
					break;
				}

				$error = sendResetEmail($_POST['email']);

				if($error === false)
				{
					error("Couldn't send reset email. Contact the system administrator.");
					break;
				}

				if($error == 1)
					info("Reset email sent! Please follow the link in the email to reset your password.", "Reset password");
				break;

			case "locktopic":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}

				if(!isSet($_GET['topic']))
				{
					error("No topic specified.");
					break;
				}

				$result = lockTopic($_GET['topic']);
				if($result === -1)
					break;

				info(($result ? "Locked" : "Unlocked") . " topic!", "Topic controls");
				// addToHead("<meta http-equiv=\"refresh\" content=\"1;URL='./?topic=${_GET['topic']}'\" />");
				break;

			case "admin":
				if(!$_SESSION['admin'])
				{
					error("You do not have permission to view this page.");
					break;
				}

				loadThemePart("admin");
				break;

			case "stickytopic":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}

				if(!isSet($_GET['topic']))
				{
					error("No topic specified.");
					break;
				}

				$result = stickyTopic($_GET['topic']);
				if($result === -1)
					break;

				info(($result ? "Sticky'd" : "Unsticky'd") . " topic!", "Topic controls");
				// addToHead("<meta http-equiv=\"refresh\" content=\"1;URL='./?topic=${_GET['topic']}'\" />");
				break;

			case "deletepost":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in.");
					break;
				}

				if(!$_SESSION['admin'])
				{
					error("You do not have permission to do this action.");
					break;
				}

				if(!isSet($_GET['post']))
				{
					error("No post specified.");
					break;
				}

				$result = deletePost($_GET['post']);

				if(!$result)
				{
					error("Failed to delete post.");
					break;
				}

				warn("Post deleted successfully.");
				break;

			case "ban":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in.");
					break;
				}
				if(!$_SESSION['admin'])
				{
					error("You do not have permission to do this action.");
					break;
				}

				if(!isSet($_GET['id']))
				{
					error("No user id specified.");
					break;
				}

				$result = toggleBanUserByID($_GET['id']);
				warn(($result ? "Banned" : "Unbanned") . " user!");
				addToHead("<meta http-equiv=\"refresh\" content=\"1;URL='./?action=viewProfile&user=${_GET['id']}'\" />");
				break;

			case "promote":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in.");
					break;
				}
				if(!$_SESSION['admin'])
				{
					error("You do not have permission to do this action.");
					break;
				}

				if(!isSet($_GET['id']))
				{
					error("No user id specified.");
					break;
				}

				$result = togglePromoteUserByID($_GET['id']);
				warn(($result ? "Promoted" : "Demoted") . " user!");
				addToHead("<meta http-equiv=\"refresh\" content=\"1;URL='./?action=viewProfile&user=${_GET['id']}'\" />");
				break;

			case "search":
				addToBody("");
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
			$_page = 0;
		}
		else
			$_page = intval($_GET['page']);

		$_topicID = intval($_GET['topic']);

		loadThemePart("topic");
	}
	else if(!isSet($_GET['action']))
	{
		if(!isSet($_GET['page']))
			$_page = 0;
		else
			$_page = intval($_GET['page']);

		loadThemePart("board");

		if(isSet($_SESSION['loggedin']))
			addToBody("<br /><br /><a href=\"./?action=newtopic\">Post a new topic</a>\n");

		addToBody("<br /><br /><a href=\"./?action=recentPosts\">Show all recent posts</a>\n");

		if(isSet($_SESSION['admin']))
			if($_SESSION['admin'])
				addToBody("<br /><a href=\"./?action=admin\">Admin</a>");
	}

	// End of possible actions, close mysql connection.
	disconnectSQL();
	finishPage();
?>