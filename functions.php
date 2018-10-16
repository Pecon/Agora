<?php
	require_once './data.php';
	require_once './database.php';

	date_default_timezone_set($site_timezone);

	function reauthuser()
	{
		if(!isSet($_SESSION['userid']))
		{
			return;
		}

		$userData = findUserByID($_SESSION['userid']);
		$_SESSION['loggedin'] = true;
		$_SESSION['name'] = $userData['username'];

		switch($userData['usergroup'])
		{
			case "superuser":
				$_SESSION['superuser'] = true;
				$_SESSION['admin'] = true;
				$_SESSION['moderator'] = true;
				$_SESSION['member'] = true;
				break;
			case "admin":
				$_SESSION['superuser'] = false;
				$_SESSION['admin'] = true;
				$_SESSION['moderator'] = true;
				$_SESSION['member'] = true;
				break;
			case "moderator":
				$_SESSION['superuser'] = false;
				$_SESSION['admin'] = false;
				$_SESSION['moderator'] = true;
				$_SESSION['member'] = true;
				break;
			case "member":
				$_SESSION['superuser'] = false;
				$_SESSION['admin'] = false;
				$_SESSION['moderator'] = false;
				$_SESSION['member'] = true;
				break;
			case "unverified":
				$_SESSION['superuser'] = false;
				$_SESSION['admin'] = false;
				$_SESSION['moderator'] = false;
				$_SESSION['member'] = false;
				break;

		}
		$_SESSION['banned'] = $userData['banned'];

		$sql = "SELECT COUNT(*) FROM privateMessages WHERE `recipientID` = ${_SESSION['userid']} AND `read` = 0;";
		$result = querySQL($sql);
		$result = $result -> fetch_assoc();
		$_SESSION['unreadMessages'] = $result['COUNT(*)'];

		if($_SESSION['banned'] == true)
		{
			error("Oh no. You're banned.");
			session_destroy();
			finishPage();
		}
	}

	function sendMail($toAddress, $subject, $contents)
	{
		global $site_name;
		$fromName = "$site_name Agora <donotreply@" . $_SERVER['SERVER_NAME'] . ">";
		$headers = "From: " . $fromName . "\r\n" .
						"Reply-To:" . $fromName . "\r\n" .
						'X-Mailer: PHP/' . phpversion();
		
		$message = wordwrap($contents, 70, "\r\n");
		$success = mail($toAddress, $subject, $contents, $headers);
	}

	function getVerificationByID($ID)
	{
		$ID = intval($ID);

		$sql = "SELECT verification FROM users WHERE id='${ID}';";
		$result = querySQL($sql);

		$result = $result -> fetch_assoc();
		return $result['verification'];
	}

	function clearVerificationByID($ID)
	{
		$ID = intval($ID);

		$sql = "UPDATE users SET verification='0' WHERE id='${ID}';";
		$result = querySQL($sql);

		return true;
	}

	function verifyAccount($code)
	{
		if(strlen($code) != 64)
		{
			error("Invalid verification code.");
			return false;
		}

		$code = sanitizeSQL($code);
		$sql = "SELECT id FROM users WHERE verification='${code}'";
		$result = querySQL($sql);

		$result = $result -> fetch_assoc();
		if(!isSet($result['id']))
			return false;

		$ID = intval($result['id']);

		$sql = "UPDATE users SET verified=1, verification=0 WHERE id='${ID}'";
		$result = querySQL($sql);

		return true;
	}

	function sendResetEmail($email)
	{
		if(!filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			error("That's not a valid email address.");
			return false;
		}

		$realEmail = $email;
		$email = sanitizeSQL($email);
		$sql = "SELECT id, verified FROM users WHERE email='${email}'";

		$result = querySQL($sql);

		$result = $result -> fetch_assoc();
		if(!isSet($result['id']))
		{
			return true; // Don't let the user know that the email wasn't on file.
		}

		if($result['verified'] == 0)
		{
			error("This account is not yet verified. You cannot reset the password until the account is verified. If you have not seen your verification email, please check your spam folder and/or wait a few minutes for it to arrive. If the email does not arrive, try registering again or contact the administrator for manual verification.");
			return -1;
		}

		$verification = bin2hex(openssl_random_pseudo_bytes(32));
		$domain = $_SERVER['SERVER_NAME']; // Just hope their webserver is configured correctly...

		if(!isSet($_SERVER['REQUST_URI']))
			$uri = "/";
		else
		{
			$uri = $_SERVER['REQUST_URI'];

			$uri = substr($uri, 0, strrchr($uri, '/') + 1);
			if(strlen($uri) == 0)
				$uri = "/";
		}
		
		global $force_ssl;

		$url = ($force_ssl ? "https://" : "http://") . $domain . $uri . "index.php?action=resetpassword&amp;code=" . $verification . "&amp;id=" . $result['id'];

		$message = <<<EOF
This email was sent to you because a password reset was initiated on your account. If you intended to do this, please click the link below:<br />
<br />
<a href="${url}">${url}</a><br />
<br />
If you did not initiate this reset, you may safely disregard this email.<br />
EOF;
		$verificationCode = sanitizeSQL($verification);
		$sql = "UPDATE users SET verification='${verificationCode}' WHERE id='${result['id']}'";
		$result = querySQL($sql);

		global $site_name;

		$error = mail($realEmail, "$site_name password reset", $message, "MIME-Version: 1.0\r\nContent-type: text/html; charset=iso-utf-8\r\nFrom: donotreply@${domain}\r\nX-Mailer: PHP/" . phpversion());
		if($error === false)
		{
			error("Failed to send verification email. Please try again later.");
			return false;
		}

		return true;
	}

	function findTopicbyID($ID)
	{
		static $topic = array();

		if(isSet($topic[$ID]))
			return $topic[$ID];

		$ID = intval($ID);
		$sql = "SELECT * FROM topics WHERE topicID = ${ID}";
		$result = querySQL($sql);

		if($numResults = $result -> num_rows > 0)
		{
			while($row = $result -> fetch_assoc())
			{
				$topic[$ID] = $row;
				return $row;
			}
			return false;
		}
		else
			return false;
	}

	function banUserByID($id)
	{
		$id = intval($id);
		$sql = "UPDATE users SET banned=1, tagline='Banned user' WHERE id={$id}";
		$result = querySQL($sql);

		$user = findUserByID($id);
		adminLog("Banned user (${id}) ${user['username']}.");
		return true;
	}

	function unbanUserByID($id)
	{

		$id = intval($id);
		$sql = "UPDATE users SET banned=0, tagline='' WHERE id='${id}'";
		$result = querySQL($sql);

		$user = findUserByID($id);
		adminLog("Unbanned user (${id}) ${user['username']}.");
		return true;
	}

	function toggleBanUserByID($id)
	{
		$user = findUserByID($id);

		if($user === false)
		{
			error("No user exists by that id.");
			return;
		}

		if($user['banned'])
		{
			unbanUserByID($id);
			return false;
		}
		else
		{
			banUserByID($id);
			return true;
		}
	}

	function promoteUserByID($id)
	{
		$id = intval($id);
		$sql = "UPDATE users SET administrator=1, tagline='Administrator' WHERE id={$id}";
		$result = querySQL($sql);

		$user = findUserByID($id);
		adminLog("Promoted user (${id}) ${user['username']} to admin.");
		return true;
	}

	function demoteUserByID($id)
	{
		$id = intval($id);
		$user = findUserByID($id);

		if($id == 1)
		{
			error("You cannot demote the superuser.");
			return false;
		}
		else if($user['administrator'] == false)
		{
			error("That user isn't an administrator.");
			return false;
		}

		$sql = "UPDATE users SET administrator=0, tagline='' WHERE id='${id}'";
		$result = querySQL($sql);

		
		adminLog("Demoted user (${id}) ${user['username']} from admin.");
		return true;
	}

	function togglePromoteUserByID($id)
	{
		$user = findUserByID($id);

		if($user === false)
		{
			error("No user exists by that id.");
			return;
		}

		if($user['administrator'])
		{
			demoteUserByID($id);
			return false;
		}
		else
		{
			promoteUserByID($id);
			return true;
		}
	}


	function findUserbyName($name)
	{
		// Speed up many requests by avoiding duplicate mysql queries
		static $user = array();

		$name = htmlentities(html_entity_decode(trim($name)));

		if(isSet($user[$name]))
			return $user[$name];

		$name = sanitizeSQL(strToLower($name));
		$sql = "SELECT * FROM users WHERE lower(username) = '{$name}'";
		$result = querySQL($sql);

		if($numResults = $result -> num_rows > 0)
		{
			while($row = $result -> fetch_assoc())
			{
				$user[$name] = $row;
				return $row;
			}

			return false;
		}
		else
			return false;
	}

	function findUserbyID($ID)
	{
		static $user = array();

		if(isSet($user[$ID]))
			return $user[$ID];

		$ID = intval($ID);
		$sql = "SELECT * FROM users WHERE id = {$ID}";
		$result = querySQL($sql);

		if($numResults = $result -> num_rows > 0)
		{
			while($row = $result -> fetch_assoc())
			{
				$user[$ID] = $row;
				return $row;
			}
			return false;
		}
		else
			return false;
	}

	function getUserNameByID($id)
	{
		$id = intval($id);
		$sql = "SELECT username FROM users WHERE id = '{$id}'";
		$result = querySQL($sql);

		if($numResults = $result -> num_rows > 0)
		{
			while($row = $result -> fetch_assoc())
			{
				return $row['username'];
			}
			return false;
		}
		else
			return false;
	}

	function getUserPostcountByID($id)
	{
		$id = intval($id);
		$sql = "SELECT postCount FROM users WHERE id = '{$id}'";
		$result = querySQL($sql);

		if($numResults = $result -> num_rows > 0)
		{
			while($row = $result -> fetch_assoc())
				return $row['postCount'];
			return false;
		}
		else
			return false;
	}

	function getAvatarByID($id)
	{
		$id = intval($id);
		$sql = "SELECT avatar FROM users WHERE id=${id};";

		$result = querySQL($sql);
		$result = $result -> fetch_assoc();

		if(isSet($result['avatar']))
			return $result['avatar'];
		return false;
	}

	function updateAvatarByID($id, $imagePath)
	{
		if(move_uploaded_file($_FILES['avatar']['tmp_name'], $imagePath))
		{
			$imgInfo = getimagesize($imagePath);
			$width = $imgInfo[0];
			$height = $imgInfo[1];
			$imgType = $imgInfo['mime'];
			$keepOriginal = false;
			$scaled = false;

			if($imgType == "image/png")
			{
				$image = imagecreatefrompng($imagePath);

				if(filesize($imagePath) < 65000)
					$keepOriginal = true;
			}
			else if($imgType == "image/jpeg")
				$image = imagecreatefromjpeg($imagePath);
			else if($imgType == "image/gif")
			{
				$image = imagecreatefromgif($imagePath);

				if(filesize($imagePath) < 65000)
					$keepOriginal = true;
			}
			else if($imgType == "image/bmp")
				$image = imagecreatefromwbmp($imagePath);
			else if($imgType == "image/webp")
				$image = imagecreatefromwebp($imagePath);
			else
			{
				unlink($imagePath);
				error("Avatar is in an unsupported image format. Please make your avatar a png, jpeg, or gif type image.");
				return false;
			}

			// Delete the raw uploaded image so it isn't left there if we exit from an error.
			if(!$keepOriginal)
				unlink($imagePath);

			if($image === false)
			{
				error("Failed to load image.");
				return false;
			}

			if($height > 100 || $width > 100)
			{
				if($height > $width)
				{
					$newHeight = 100;
					$newWidth = round($width * ($newHeight / $height));
				}
				else
				{
					$newWidth = 100;
					$newHeight = round($height * ($newWidth / $width));
				}

				$newImage = imagecreatetruecolor($newWidth, $newHeight);

				// Make sure transparency is spared.
				imagesavealpha($image, true);
				imagesavealpha($newImage, true);
				imagesetinterpolation($newImage, IMG_BICUBIC);

				$error = imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

				if($error === false)
				{
					error("Unable to scale image.");
					return;
				}

				imagedestroy($image);
				$image = $newImage;
				$scaled = true;

				warn("Your image was scaled because it was too big. Some quality may have been lost.");
			}

			// Save the converted image.
			if(!$keepOriginal || $scaled)
			{
				$error = imagepng($image, $imagePath, 9, PNG_NO_FILTER);

				if($error === false)
				{
					error("Unable to save converted image.");
					return false;
				}

				if(!$scaled)
					warn("Your image was converted to PNG format.");
			}

			imagedestroy($image);

			//Upload the avatar to the MySQL database
			$id = intval($id);
			$inputData = sanitizeSQL(fread(fopen($imagePath, "rb"), filesize($imagePath)));
			unlink($imagePath);
			$sql = "UPDATE users SET avatar='${inputData}' WHERE id=${id};";

			querySQL($sql);
		}
		else
		{
			error("Uploaded file could not be validated.");
			return false;
		}

		return true;
	}

	function getPasswordHashByID($ID)
	{
		$ID = intval($ID);
		$sql = "SELECT passkey FROM users WHERE id=${ID};";

		$result = querySQL($sql);

		return $result -> fetch_assoc()['passkey'];
	}

	function updatePasswordByID($ID, $newHash)
	{
		$ID = intval($ID);
		$newHash = sanitizeSQL($newHash);

		$sql = "UPDATE users SET passkey='${newHash}' WHERE id=${ID};";

		querySQL($sql);
	}

	function getEmailByID($ID)
	{
		$ID = intval($ID);
		$sql = "SELECT email FROM users WHERE id=${ID};";

		$result = querySQL($sql);

		return $result -> fetch_assoc()['email'];
	}

	function updateEmailByID($ID, $newEmail)
	{
		if(filter_var($newEmail, FILTER_VALIDATE_EMAIL) === false)
			return false;

		global $require_email_verification;

		if($require_email_verification)
		{
			$verification = bin2hex(openssl_random_pseudo_bytes(32));
			$domain = $_SERVER['SERVER_NAME']; // Just hope their webserver is configured correctly...

			$uri =  $_SERVER['REQUEST_URI'];
			$uripos = strrchr($uri, '/');
			if($uripos === false)
				$uri = "/";
			else
				$uri = substr($uri, 0, $uripos + 1);

			global $force_ssl;

			$url = ($force_ssl ? "https://" : "http://") . $domain . $uri . "index.php?action=emailchange&amp;code=" . $verification . "&amp;id=" . $ID;
			$user = getUserNameByID($ID);

			$message = <<<EOF
	This email was sent to you because an email change was initiated on your account, ${user}. If you intended to do this, please click the link below to confirm the new email:<br />
	<br />
	<a href="${url}">${url}</a><br />
	<br />
	If you are not the owner of the account, pleae disregard this email.<br />
EOF;

			$verificationCode = sanitizeSQL($verification);
			$newEmail = sanitizeSQL($newEmail);
			$ID = intval($ID);
			$sql = "UPDATE users SET emailVerification='${verificationCode}', newEmail='${newEmail}' WHERE id='${ID}';";
			querySQL($sql);

			global $site_name;

			$error = mail($newEmail, "$site_name email change", $message, "MIME-Version: 1.0\r\nContent-type: text/html; charset=iso-utf-8\r\nFrom: donotreply@${domain}\r\nX-Mailer: PHP/" . phpversion());
			if($error === false)
			{
				error("Failed to send verification email. Please try again later.");
				return false;
			}

			return true;
		}
		else
		{
			$ID = intval($ID);
			$newEmail = sanitizeSQL(trim($newEmail));

			$sql = "UPDATE users SET email='${newEmail}' WHERE id=${ID};";

			querySQL($sql);
			return true;
		}
	}

	function verifyEmailChange($ID, $verification)
	{
		$ID = intval($ID);
		$user = findUserByID($ID);

		if($verification !== $user['emailVerification'])
		{
			error("Invalid verification code.");
			return false;
		}

		$sql = "UPDATE users SET email='${user['newEmail']}', newEmail='', emailVerification=0 WHERE id='${ID}';";
		$result = querySQL($sql);

		return true;
	}

	function checkUserExists($username, $email)
	{
		$username = sanitizeSQL(strToLower($username));
		$email = sanitizeSQL(strToLower($email));
		$sql = "SELECT * FROM users WHERE lower(username) = '${username}' OR lower(email) = '${email}';";
		$result = querySQL($sql);

		if($result -> num_rows > 0)
		{
			while($row = $result -> fetch_assoc())
				return true;

			return false;
		}

		else
			return false;
	}

	function displayUserProfile($id)
	{
		$userData = findUserByID($id);

		if($userData == false)
		{
			error("No user by this user id exists.");
			return;
		}

		global $site_name;
		setPageTitle("Profile of " . $userData['username']);
		setPageDescription("View the profile of ${userData['username']} on $site_name!");

		if(!isSet($_SESSION['loggedin']))
			$adminControl = "";
		else
		{
			if($_SESSION['admin'])
				$adminControl = "<a href=\"./?action=ban&amp;id=${id}\">" . ($userData['banned'] ? "Unban" : "Ban") . " this user</a> &nbsp; <a href=\"./?action=promote&amp;id=${id}\">" . ($userData['administrator'] ? "Demote" : "Promote") . " this user</a><br >\n";
			else
				$adminControl = "";
		}


		$username = $userData['username'];
		$lastActive = $userData['lastActive'];
		$reg_date = date('Y-m-d g:i:s', $userData['reg_date']);
		$postCount = $userData['postCount'];
		$tagLine = $userData['tagline'];
		$website = $userData['website'];
		$profileText = $userData['profiletext'];
		$profileDisplayText = $userData['profiletextPreparsed'];

		$taglineColor = "#FFFFFF";
		if($userData['administrator'])
			$taglineColor = "#FFFF00; text-shadow: 0px 0px 1px #FFFFAA"; // subtle xss ((just kidding))
		if($userData['banned'])
			$taglineColor = "#FF0000";

		$websiteComps = parse_url($website);
		if(isSet($websiteComps['host']))
			$websitePretty = $websiteComps['host'] . (isSet($websiteComps['path']) ? (strlen($websiteComps['path']) > 1 ? $websiteComps['path'] : "") : "");


		addToBody("\n${adminControl}<table class=\"forumTable\">\n<tr>\n<td class=\"padding\" style=\"background-color: #414141;\">\n${username}\n</td>\n</tr>\n<tr>\n<td class=padding style=\"background-color: #414141;\">\n" .
				(strLen($tagLine) > 0 ? "<span style=\"color:${taglineColor}\">${tagLine}</span><br />\n" : "<br />") .
				"<img class=avatar src=\"./avatar.php?user=${id}\" /><br />
				Posts: {$postCount}<br />
				Date registered: {$reg_date}<br />
				Last activity: {$lastActive}<br />" .
				(strLen($website) > 0 && isSet($websitePretty) ? "Website: <a target=\"_blank\" href=\"${website}\">${websitePretty}</a><br />\n" : "Website: None") .
				"</td>
				</tr>
				<tr>
				<td class=padding>
				<br />
				${profileDisplayText}
				<br \><br \>
				</td>
				</tr>
				</table><br />\n");

		if(strlen($website) == 0)
			$website = "http://";

		if(isSet($_SESSION['userid']))
			if($_SESSION['userid'] == $id)
			{
				$updateProfileText = $profileText;
				$table = <<<EOT
					<table class="forumTable">
					<tr>
						<td class="padding" style="width: 190px; vertical-align: top;">
							User&nbsp;settings<br />
							<hr />
							<a href="./?action=avatarchange">Change&nbsp;avatar</a><br />
							<a href="./?action=emailchange">Change&nbsp;email</a><br />
							<a href="./?action=passwordchange">Change&nbsp;password</a><br />
						</td>
						<td class="padding">
							Profile info<br />
							<hr />
							<form action="./?action=updateprofile" method=POST>
								Tagline: <input type="text" name="tagline" maxLength="40" value="${tagLine}"/><br />
								Website: <input type="text" name="website" maxLength="200" value="${website}"/><br />
								<br />
								Update profile text (you may use bbcode here):<br />
								<textarea class="postbox" maxLength="1000" name="updateProfileText">${updateProfileText}</textarea><br />
								<input class="postButtons" type="submit" value="Update profile">
							</form>
						</td>
					</tr>
				</table>
EOT;
				addToBody($table);
			}
	}

	function updateUserProfileText($id, $text, $tagLine, $website)
	{
		if(strlen($text) > 1000)
		{
			error("Your profile info text cannot exceed 1000 characters.");
			return false;
		}

		// verify website and tagline are OK and then sql escape them
		if(!filter_var($website, FILTER_VALIDATE_URL) || strlen($website) > 200)
		{
			if(strToLower($website) != "http://" && strlen($website) > 1)
				error("Your website url is invalid or too long.");
			else if(strToLower($website) == "http://")
				$website = findUserByID($id)['website'];
			else
				$website = "";
		}

		if(strlen($tagLine) > 40)
		{
			error("Your tagline is too long.");

			$tagLine = findUserByID($id)['tagline'];
		}

		$id = intval($id);
		$rawText = sanitizeSQL(htmlentities(html_entity_decode($rawText), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8"));
		$text = sanitizeSQL(bb_parse($text));
		$website = sanitizeSQL(trim($website));
		$tagLine = sanitizeSQL(htmlentities(html_entity_decode(trim($tagLine)), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8"));

		$sql = "UPDATE users SET profiletext='${rawText}', profiletextPreparsed='${text}', tagline='${tagLine}', website='${website}' WHERE id=${id}";
		$result = querySQL($sql);

		return true;
	}

	function displayRecentThreads($page)
	{
		global $items_per_page;
		$page = intval($page);
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
				array_push($posts, $post);

			$allPostID = Array();
			foreach($posts as $post)
				$allPostID[$post['postID']] = $post;

			unset($posts);
			unset($post);

			// Okay, start making the page now.
			$description = $description . "\nRecent topics:";
			addToBody("<table class=\"forumTable\" >");
			addToBody("<tr><td>Topic name</td><td class=\"startedby\">Author</td><td>Last post by</td></tr>");

			foreach($threads as $row)
			{
				$topicID = $row['topicID'];
				$topicName = $row['topicName'];

				$numPosts = querySQL("SELECT COUNT(*) FROM posts WHERE threadID=${topicID};") -> fetch_assoc()['COUNT(*)'];
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

				addToBody("<tr><td>${threadStatus} <a href=\"./?topic=${topicID}\">${topicName}</a> <span class=finetext>");

				global $items_per_page;
				if($numPosts > $items_per_page) // Don't show page nav buttons if there is only one page
					displayPageNavigationButtons(0, $numPosts, "topic=${topicID}");

				addToBody("</span></td><td class=startedbyrow><a href=\"./?action=viewProfile&amp;user={$row['creatorUserID']}\">{$creatorName}</a></td><td class=lastpostrow><a href=\"./?action=viewProfile&amp;user={$lastPost['userID']}\">{$postUserName}</a> on {$lastPostTime}</td></tr>\n");
			}

			addToBody("</table>");
		}
		else
			addToBody("There are no threads to display!");
		

		$totalTopics = querySQL("SELECT COUNT(*) FROM topics") -> fetch_assoc()['COUNT(*)'];
		displayPageNavigationButtons($page, $totalTopics, null);
		setPageDescription($description);
	}

	function displayRecentPosts($start, $num)
	{
		$sql = "SELECT * FROM posts ORDER BY postID DESC LIMIT {$start},{$num}";
		$result = querySQL($sql);

		global $items_per_page;

		if($result -> num_rows > 0)
		{
			addToBody("<table class=forumTable border=1>\n");
			while($row = $result -> fetch_assoc())
			{
				$topic = findTopicbyID($row['threadID']);
				$user = findUserByID($row['userID']);
				$username = $user['username'];
				$date = date("F d, Y H:i:s", $row['postDate']);
				$topicPage = floor($row['threadIndex'] / $items_per_page);

				addToBody("<tr><td colspan=2><a href=\"./?topic=${topic['topicID']}&amp;page=${topicPage}#${row['postID']}\">${topic['topicName']}</a></td></tr><tr><td class=usernamerow><a class=\"userLink\" href=\"./?action=viewProfile&amp;user={$row['userID']}\">{$username}</a><br><div class=finetext>${user['tagline']}<br /><img class=avatar src=\"./avatar.php?user=${row['userID']}\" /><br />${date}</div></td><td class=postdatarow>{$row['postPreparsed']}</td></tr>\n");
			}
			addToBody("</table>\n");
		}
		else
		{
			addToBody("There are no posts to display!");
		}
	}

	function fetchSinglePost($postID)
	{
		static $post = array();

		if(isSet($post[$postID]))
			return $post[$postID];

		$postID = intval($postID);
		$sql = "SELECT * FROM posts WHERE postID={$postID};";

		$result = querySQL($sql);

		if($result === false)
			return false;

		$row = $result -> fetch_assoc();
		$post[$postID] = $row;
		return $row;
	}

	function displayPostEdits($postID)
	{
		$post = fetchSinglePost(intval($postID));

		if($post['changeID'] == false)
		{
			error("There are no changes to view on this post.");
			return;
		}

		$postID = intval($postID);
		$username = getUserNameByID($post['userID']);

		$changeID = $post['changeID'];

		$sql = "SELECT * FROM changes WHERE id=${changeID}";
		$result = querySQL($sql);

		if($result === false)
		{
			error("There are no edits to display.");
			return;
		}

		$change = $result -> fetch_assoc();
		$changeID = $change['lastChange'];
		$date = date("F d, Y H:i:s", $change['changeTime']);

		addToBody("Viewing post edits<br>\n<table class=\"forumTable\"><tr><td class=\"usernamerow\"><a class=\"userLink\" href=\"./?action=viewProfile&amp;user=${post['userID']}\">${username}</a><br><span class=finetext>${date}<br>(Current version)</span></td><td class=\"postdatarow\">{$post['postPreparsed']}</td></tr>\n");

		while($changeID > 0)
		{
			addToBody("<tr><td class=\"usernamerow\"><a class=\"userLink\" href=\"./?action=viewProfile&amp;user=${post['userID']}\">${username}</a><br><span class=\"finetext\">${date}</span></td><td class=\"postdatarow\">${change['postData']}</td></tr>\n");

			$sql = "SELECT * FROM changes WHERE id=${changeID}";
			$result = querySQL($sql);

			if($result == false)
			{
				error("Could not get edit.");
				break;
			}

			$change = $result -> fetch_assoc();
			$changeID = $change['lastChange'];
			$date = date("F d, Y H:i:s", $change['changeTime']);
		}

		$date = date("F d, Y H:i:s", $post['postDate']);
		addToBody("<tr><td class=usernamerow><a class=\"userLink\" href=\"./?action=viewProfile&amp;user=${post['userID']}\">${username}</a><br><span class=finetext>${date}<br>(Original)</span></td><td class=postdatarow>${change['postData']}</td></tr>\n</table>");
	}

	function displayThread($topicID, $page)
	{
		global $items_per_page;
		
	}

	function createThread($userID, $topic, $postData)
	{
		$userID = intval($userID);
		$topic = sanitizeSQL(htmlentities(html_entity_decode($topic), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8"));

		$sql = "INSERT INTO topics (creatorUserID, topicName) VALUES ({$userID}, '{$topic}');";

		$result = querySQL($sql);
		$topicID = getLastInsertID();

		createPost($userID, $topicID, $postData);
		return $topicID;
   }

   function lockTopic($topicID)
   {
		$topicID = intval($topicID);
		$topic = findTopicbyID($topicID);

		if($topic === false)
		{
			error("That topic does not exist.");
			return -1;
		}

		if($_SESSION['userid'] != $topic['creatorUserID'] && !$_SESSION['admin'])
		{
			error("You do not have permission to do this action.");
			return -1;
		}

		$newValue = !$topic['locked'];

		$sql = "UPDATE topics SET locked='${newValue}' WHERE topicID='{$topicID}';";

		querySQL($sql);

		adminLog("set topic (${topicID}) locked status to " . ($newValue ? "Locked" : "Not Locked") . ".");
		return $newValue;
   }

	function stickyTopic($topicID)
	{
		$topicID = intval($topicID);
		$topic = findTopicbyID($topicID);

		if($topic === false)
		{
			error("That topic does not exist.");
			return -1;
		}

		if(!$_SESSION['admin'])
		{
			error("You do not have permission to do this action.");
			return -1;
		}

		$newValue = !$topic['sticky'];

		$sql = "UPDATE topics SET sticky='${newValue}' WHERE topicID='{$topicID}';";

		querySQL($sql);

		adminLog("set topic (${topicID}) sticky status to " . ($newValue ? "Sticky" : "Not Sticky") . ".");
		return $newValue;
	}

	function createPost($userID, $topicID, $postData)
	{
		$userID = intval($userID);
		$topicID = intval($topicID);

		$row = findTopicbyID($topicID);
		if($row === false)
		{
			error("Could not find thread data.");
			return;
		}

		if($row['locked'] == true)
		{
			error("This thread is locked. No further posts are permitted.");
			return false;
		}

		// Cleanse post data
		$parsedPost = sanitizeSQL(bb_parse($postData));
		$postData = sanitizeSQL(htmlentities(html_entity_decode($postData), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8"));
		$date = time();

		// Make entry in posts table
		$mysqli = getSQLConnection();
		$sql = "INSERT INTO posts (userID, threadID, postDate, postData, postPreparsed, threadIndex) VALUES (${userID}, ${topicID}, '${date}', '${postData}', '${parsedPost}', '${row['numposts']}');";
		querySQL($sql);

		$postID = getLastInsertID();

		// Make new data for thread entry
		$numPosts = $row['numposts'] + 1;

		// Update thread entry
		$sql = "UPDATE topics SET lastposttime='${date}', lastpostid='${postID}', numposts='${numPosts}' WHERE topicID=${topicID}";
		querySQL($sql);

		// Update user post count
		$postCount = getUserPostcountByID($userID) + 1;

		$sql = "UPDATE users SET postCount='${postCount}' WHERE id=${userID}";
		querySQL($sql);
		
		return $postID;
	}

	function editPost($userID, $postID, $newPostData)
	{
		if($_SESSION['userid'] !== $userID && !$_SESSION['admin'])
		{
			error("You do not have permission to edit this post.");
			return;
		}

		if(!$post = fetchSinglePost($postID))
		{
			error("This post does not exist.");
			return;
		}

		if(boolval(findTopicbyID($post['threadID'])['locked']))
		{
			error("You can't edit posts in a locked thread.");
			return;
		}

		$changeTime = time();
		$userID = intval($userID);
		$postID = intval($postID);
		$oldPostData = sanitizeSQL($post['postPreparsed']);

		$sql = "INSERT INTO changes (lastChange, postData, changeTime, postID, threadID) VALUES ('${post['changeID']}', '${oldPostData}', '${changeTime}', '${post['postID']}', '${post['threadID']}');";
		querySQL($sql);

		$changeID = getLastInsertID();
		$newPostParsed = sanitizeSQL(bb_parse($newPostData));
		$newPostData = sanitizeSQL(htmlentities(html_entity_decode($newPostData), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8"));

		$sql = "UPDATE posts SET postData='{$newPostData}', postPreparsed='{$newPostParsed}', changeID={$changeID} WHERE postID={$postID};";
		querySQL($sql);
	}

	function deletePost($id)
	{
		$id = intval($id);

		if(!$post = fetchSinglePost($id))
		{
			error("This post does not exist.");
			return false;
		}

		if($post['threadIndex'] == 0)
		{
			// Delete the thread entry as well
			$thread = findTopicByID($post['threadID']);
			$threadCreator = findUserByID($thread['creatorUserID']);

			$sql = "DELETE FROM topics WHERE topicID='${post['threadID']}';";
			querySQL($sql);

			$sql = "DELETE FROM posts WHERE threadID='${post['threadID']}';";
			querySQL($sql);

			$sql = "DELETE FROM changes WHERE threadID='${post['threadID']}';";
			querySQL($sql);

			adminLog("Deleted thread by ${threadCreator['id']} ${threadCreator['username']}: ${post['threadID']} . ${thread['topicName']}");
		}
		else
		{
			// Check if we need to update the latest post data
			$topic = findTopicByID($post['threadID']);

			if($topic['lastpostid'] == $id)
			{
				// Find the last existing post in the thread.
				$sql = "SELECT postID, threadIndex, postDate FROM posts WHERE threadID='${post['threadID']}' ORDER BY threadIndex DESC LIMIT 0,2";
				$result = querySQL($sql);

				// Skip the first result since it's going to be the post we're about to delete. We want the one after it.
				$result -> fetch_assoc();
				$newLastPost = $result -> fetch_assoc();
				$newPostCount = $newLastPost['threadIndex'] + 1;

				// Update the thread with the new values
				$sql = "UPDATE topics SET lastpostid='${newLastPost['postID']}', lastposttime='${newLastPost['postDate']}', numposts='${newPostCount}' WHERE topicID='${post['threadID']}';";
				querySQL($sql);
			}

			// Delete just this post out of the thread
			$post = fetchSinglePost($id);
			$postStuff = str_replace(array("\r", "\n"), " ", $post['postData']);
			$user = findUserByID($post['userID'])['username'];

			$sql = "DELETE FROM posts WHERE postID='${id}';";
			querySQL($sql);

			// De-increment user post count
			$postCount = findUserByID($post['userID'])['postCount'] - 1;
			$sql = "UPDATE users SET postCount='${postCount}' WHERE id='${post['userID']}';";
			querySQL($sql);

			$sql = "DELETE FROM changes WHERE postID='${id}';";
			querySQL($sql);

			adminLog("Deleted post by ${user} (${post['userID']}): (${id}) ${postStuff}");
		}

		return true;
	}



	// Private messaging functions

	function displayRecentMessages($page, $sent)
	{
		global $items_per_page;
		$page = intval($page);
		$start = ($page * $items_per_page);
		$num = $items_per_page;

		$sql = "SELECT * FROM privateMessages WHERE " . ($sent ? "senderID=" : "recipientID=") . $_SESSION['userid'] . ($sent ? "" : " AND deleted=0") . " ORDER BY messageDate DESC LIMIT {$start},{$num}";
		$result = querySQL($sql);

		global $site_name;
		$description = "Welcome to $site_name!";

		$threadStatus = "";

		if($result -> num_rows > 0)
		{
			$description = $description . ($sent ? "\nRecently sent messages:" : "\nRecent messages:");
			addToBody("<div class=\"threadHeader\">&rarr; Viewing " . ($sent ? "outbox" : "inbox") . "</div>");
			addToBody('<table class="forumTable" >');
			addToBody('<tr><td>Subject</td><td class="startedby">' . ($sent ? "To" : "From") . '</td><td>Date</td></tr>');
			while($row = $result -> fetch_assoc())
			{
				$messageID = $row['messageID'];
				$subject = $row['subject'];

				$creator = findUserByID($sent ? $row['recipientID'] : $row['senderID']);
				$creatorName = $creator['username'];
				$description = $description . "\n$subject, " . ($sent ? "to" : "from") . " $creatorName";

				switch(intval($row['read']))
				{
					case 0:
						$threadStatus = '<span class="icon messageUnread" title="Unread message"></span>';
						break;

					case 1:
						$threadStatus = '<span class="icon messageRead" title="Message has been read"></span>';
						break;

					case 2:
						$threadStatus = '<span class="icon messageReplied" title="Message has been replied to"></span>';
						break;

					default:
						$threadStatus = '<span class="icon messageRead"></span>';
						break;


				}

				$sentTime = date("F d, Y H:i:s", $row['messageDate']);

				addToBody("<tr><td>$threadStatus <a href=\"./?action=messaging&amp;id=$messageID \">$subject</a></td><td class=startedbyrow><a href=\"./?action=viewProfile&amp;user=${creator['id']}\">$creatorName</a></td><td class=lastpostrow>$sentTime</td>" . (!$sent ? '<td class="buttonRow"><form method="POST" action="./?action=deletemessage"><input type="hidden" name="id" value="' . $messageID . '" /><input type="submit" value="Delete" /></form></td>' : '') . "</tr>\n");
			}
			addToBody("</table>");
			$totalMessages = querySQL("SELECT COUNT(*) FROM privateMessages WHERE recipientID = $messageID ;") -> fetch_assoc()['COUNT(*)'];
			displayPageNavigationButtons($page, $totalMessages, "action=" . ($sent ? "outbox" : "messaging"));
		}
		else
			addToBody("No messages.<br /><br />");

		//addToBody("<a href=\"./?action=" . ($sent ? 'outbox' : 'messaging') . "\">Refresh messages</a><br /><br />");
		addToBody("<br /><br /><a href=\"./?action=" . ($sent ? 'messaging' : 'outbox') . "\">View " . ($sent ? 'inbox' : 'outbox') . "</a><br /><br />");
		addToBody("<a href=\"./?action=composemessage\">Compose message</a><br /><br />");

		setPageDescription($description);
	}

	function displayMessage($ID)
	{
		$message = fetchSingleMessage($ID);

		if($message === false)
		{
			error("Could not find that message.");
			return false;
		}

		$sender = findUserbyID($message['senderID']);
		$recipient = $message['recipientID'];

		if($_SESSION['userid'] != $sender['id'] && $_SESSION['userid'] != $recipient && !$_SESSION['admin'])
		{
			error("You do not have permission to view this message.");
			return false;
		}

		addToBody("<div class=\"threadHeader\">&rarr; Viewing private message: ${message['subject']}</div>");
		addToBody('<table class="forumTable">
			<tr><td class="usernamerow"><a class="userLink" name=" ' . $sender['id'] . '"></a><a class="userLink" href="./?action=viewProfile&amp;user=' . $sender['id'] . '">' . $sender['username'] . '</a><br>');

		if($sender['banned'])
				addToBody("<div class=\"taglineBanned finetext\">${sender['tagline']}</div>");
			else if($sender['administrator'])
				addToBody("<div class=\"taglineAdmin finetext\">${sender['tagline']}</div>");
			else
				addToBody("<div class=\"tagline finetext\">${sender['tagline']}</div>");


			// Display the user's avatar and the post date
			$date = date("F d, Y H:i:s", $message['messageDate']);
			addToBody("<br /><img class=\"avatar\" src=\"./avatar.php?user=${sender['id']}\" /><br /><div class=\"postDate finetext\">${date}</div></td>");

			addToBody('<td class="postdatarow">' . $message['messagePreparsed'] . '</td></tr></table>');

			if($_SESSION['userid'] == $recipient)
			{
				if(!$message['read'])
				{
					// Mark as read
					$sql = "UPDATE privateMessages SET `read` = '1' WHERE `messageID` = '${message['messageID']}';";
					$result = querySQL($sql);

					if($result === false)
						error("Failed to set message as read.");
					else
						$_SESSION['unreadMessages'] -= 1;
				}

				addToBody('<form method="POST" action="./?action=composemessage"><input type="hidden" name="toName" value="' . $sender['username'] . '" /><input type="hidden" name="subject" value="RE: ' . $message['subject'] . '" /><input type="hidden" name="replyID" value="' . $message['messageID'] . '" />');
				addToBody('<textarea class="postbox" name="postcontent" tabindex="1">[quote ' . $sender['username'] . ']' . $message['messageData'] . "[/quote]\n</textarea><br />");
				addToBody('<input class="postButtons" type="submit" name="send" value="Reply" tabindex="3">
				<input class="postButtons" type="submit" name="preview" value="Preview" tabindex="2"></form>');
			}
			
		return true;
	}

	function fetchSingleMessage($ID)
	{
		$ID = intval($ID);

		$sql = "SELECT * FROM privateMessages WHERE messageID='$ID';";
		$result = querySQL($sql);


		if($result !== false)
			return $result -> fetch_assoc();
		else
			return false;
	}

	function deleteMessage($ID)
	{
		$ID = intval($ID);
		$message = fetchSingleMessage($ID);

		if($message['recipientID'] != $_SESSION['userid'])
		{
			error("You cannot delete messages other than your own.");
			return false;
		}

		$sql = "UPDATE privateMessages SET `deleted` = '1' WHERE `messageID` = $ID;";
		$result = querySQL($sql);

		if($result === false)
			return false;
		else
			return true;
	}

	function sendMessage($text, $subject, $to, $replyID)
	{
		$recipient = findUserbyName($to);
		$subject = sanitizeSQL(htmlentities(html_entity_decode($subject), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8"));
		$parsedText = sanitizeSQL(bb_parse($text));
		$text = htmlentities(html_entity_decode($text), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8");

		if($recipient === false)
		{
			error("Could not find a user by that name.");
			return false;
		}

		$sql = "INSERT INTO privateMessages (senderID, recipientID, messageDate, messageData, messagePreparsed, subject) VALUES ('${_SESSION['userid']}', '${recipient['id']}', '" . time() . "', '$text', '$parsedText', '$subject');";
		$result = querySQL($sql);

		if($result !== false)
		{
			$replyID = intval($replyID);
			$reply = fetchSingleMessage($replyID);

			if($reply['recipientID'] == $_SESSION['userid'] && $reply['senderID'] == $recipient)
			{
				$sql = "UPDATE privateMessages SET `read` = 2 WHERE `messageID` = $replyID;";
				querySQL($sql);
			}

			return true;
		}
		else
		{
			error("Message could not be sent for an unknown reason. This is probably a bug.");
			return false;
		}
	}

	function displayPageNavigationButtons($currentPage, $totalItems, $pageAction)
	{
		// This value can be modified from your .settings.json file (under the /data directory)
		global $items_per_page;

		if($pageAction === null)
			$pageAction = "./?page=";
		else
		{
			$pageAction = htmlentities($pageAction);
			$pageAction = "./?${pageAction}&amp;page=";
		}
		
		$currentPage = intval($currentPage);
		$totalItems = intval($totalItems);
		$totalPages = ceil($totalItems / $items_per_page);

		// Pages Start At One
		$quickPages = " [" . ($currentPage + 1) . "] ";

		if($currentPage - 1 >= 0)
		{
			$quickPages = '<a href="' . $pageAction . ($currentPage - 1) . '">' . $currentPage . '</a> ' . $quickPages;

			if($currentPage - 2 >= 0)
			{
				$quickPages = '<a href="' . $pageAction . ($currentPage - 2) . '">' . ($currentPage - 1) . '</a> ' . $quickPages;

				if($currentPage - 3 >= 0)
					$quickPages = '<a href="' . $pageAction . '0' . '">' . '1' . '</a> ... ' . $quickPages;
			}
		}

		if($currentPage + 1 < $totalPages)
		{
			$quickPages = $quickPages . ' <a href="' . $pageAction . ($currentPage + 1) . '">' . ($currentPage + 2) . '</a>';

			if($currentPage + 2 < $totalPages)
			{
				$quickPages = $quickPages . ' <a href="' . $pageAction . ($currentPage + 2) . '">' . ($currentPage + 3) . '</a>';

				if($currentPage + 3 < $totalPages)
				{
					$quickPages = $quickPages . ' ... <a href="' . $pageAction . ($totalPages - 1) . '">' . $totalPages . '</a>';
				}
			}
		}

		$quickPages = '&laquo; ' . $quickPages . ' &raquo;';

		//addToBody('<br />');
		// addToBody('<div class="finetext">Navigation</div>');
		addToBody($quickPages);
	}

	// Other functions

	function normalize_special_characters($str)
	{
		// Eventually.
		return $str;
	}

	function bb_parse($text)
	{
		require_once 'bbcode.php';

		try
		{
			$text = parseTag($text, 0, "root");
		}
		catch(Exception $e)
		{
			error("Error: " . $e -> getText());
			$text = false;
		}

		return $text;
	}


	function adminLog($stuff)
	{
		$file = fopen("./admin.log", "a");
		$file.fwrite($file, time() . " " . $_SESSION['userid'] . " " . $stuff . "\r\n");

		fclose($file);
	}

	function adminLogParse($log)
	{
		$return = "";
		$lines = explode("\n", $log);

		foreach($lines as $line)
		{
			$words = explode(" ", $line);
			$wordCount = count($words);

			if($wordCount < 3)
				continue;

			$return = $return . date("r", intval($words[0]));
			$user = findUserByID(intval($words[1]));
			$return = $return . " ${user['username']} (${words[1]})";

			for($i = 2; $i < $wordCount; $i++)
			{
				$return = $return . " " . $words[$i];
			}
		}

		return $return;
	}
?>
