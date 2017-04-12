<?php
	require_once './data.php';
	require_once './database.php';

	date_default_timezone_set("America/Los_Angeles"); // Should add this to the configuration at some point.

	function reauthuser()
	{
		if(!isSet($_SESSION['userid']))
		{
			return;
		}

		$userData = findUserByID($_SESSION['userid']);
		$_SESSION['loggedin'] = true;
		$_SESSION['name'] = $userData['username'];
		$_SESSION['admin'] = $userData['administrator'];
		$_SESSION['banned'] = $userData['banned'];

		if($_SESSION['banned'] == true)
		{
			error("Oh no. You're banned.");
			session_destroy();
			die();
		}
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
		$sql = "SELECT id FROM users WHERE email='${email}'";

		$result = querySQL($sql);

		$result = $result -> fetch_assoc();
		if(!isSet($result['id']))
		{
			return true; // Don't let the user know that the email wasn't on file.
		}

		$verification = bin2hex(openssl_random_pseudo_bytes(32));
		$domain = $_SERVER['SERVER_NAME']; // Just hope their webserver is configured correctly...

		$uri = $_SERVER['REQUST_URI'];
		$uri = substr($uri, 0, strrchr($uri, '/') + 1);
		if(strlen($uri) == 0)
			$uri = "/";

		$url = "http://" . $domain . $uri . "index.php?action=resetpassword&code=" . $verification . "&id=" . $result['id'];

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

		$error = mail($realEmail, "REforum password reset", $message, "MIME-Version: 1.0\r\nContent-type: text/html; charset=iso-utf-8\r\nFrom: donotreply@${domain}\r\nX-Mailer: PHP/" . phpversion());
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

		$ID = intVal($ID);
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
		$sql = "UPDATE users SET administrator=0, tagline='' WHERE id='${id}'";
		$result = querySQL($sql);

		$user = findUserByID($id);
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

		$ID = intVal($ID);
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
		$id = intVal($id);
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
				exit(error("Avatar is in an unsupported image format. Please make your avatar a png, jpeg, or gif type image.", true));
			}

			// Delete the raw uploaded image so it isn't left there if we exit from an error.
			if(!$keepOriginal)
				unlink($imagePath);

			if($image === false)
				exit(error("Failed to load image.", true));

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
					exit(error("Unable to scale image.", true));

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
					exit(error("Unable to save converted image.", true));

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
			error("Uploaded file could not be validated.");
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


			$url = "http://" . $domain . $uri . "index.php?action=emailchange&code=" . $verification . "&id=" . $ID;
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

			$error = mail($newEmail, "REforum email change", $message, "MIME-Version: 1.0\r\nContent-type: text/html; charset=iso-utf-8\r\nFrom: donotreply@${domain}\r\nX-Mailer: PHP/" . phpversion());
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
		$sql = "SELECT * FROM users WHERE lower(username) = '{$username}' AND lower(email) = '${email}';";
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


		if(!isSet($_SESSION['loggedin']))
			$adminControl = "";
		else
		{
			if($_SESSION['admin'])
				$adminControl = "<a href=\"./?action=ban&id=${id}\">" . ($userData['banned'] ? "Unban" : "Ban") . " this user</a> &nbsp; <a href=\"./?action=promote&id=${id}\">" . ($userData['administrator'] ? "Demote" : "Promote") . " this user</a><br >\n";
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

		print("\n${adminControl}<table class=forumTable border=1>\n<tr>\n<td class=padding style=\"background-color: #414141;\">\n{$username}\n</td>\n</tr>\n<tr>\n<td class=padding style=\"background-color: #414141;\">\n" .
				(strLen($tagLine) > 0 ? "<span style=\"color:${taglineColor}\">${tagLine}</span><br />\n" : "<br />") .
				"<img class=avatar src=\"./avatar.php?user=${id}\" /><br />
				Posts: {$postCount}<br />
				Date registered: {$reg_date}<br />
				Last activity: {$lastActive}<br />" .
				(strLen($website) > 0 ? "Website: <a target=\"_blank\" href=\"${website}\">${websitePretty}</a><br />\n" : "Website: None") .
				"</td>
				</tr>
				<tr>
				<td class=padding>
				<br />
				{$profileDisplayText}
				<br \><br \>
				</td>
				</tr>
				</table><br />\n");

		if(strlen($website) == 0)
			$website = "http://";

		if(isSet($_SESSION['userid']))
			if($_SESSION['userid'] == $id)
			{
				$updateProfileText = str_replace("<br>", "\n", $profileText);
				?>

				<table class="forumTable">
					<tr>
						<td class="padding" style="min-width: 30px; max-width: 50px; vertical-align: top;">
							User&nbsp;settings<br />
							<hr />
							<a href="./?action=avatarchange">Change&nbsp;avatar</a><br />
							<a href="./?action=emailchange">Change&nbsp;email</a><br />
							<a href="./?action=passwordchange">Change&nbsp;password</a><br />
						</td>
						<td class="padding">
							Profile info<br />
							<hr />
							<?php
									print("<form action=\"./?action=updateprofile&amp;finishForm=1&amp;newAction=viewProfile%26user=${id}\" method=POST accept-charset=\"ISO-8859-1\">
								Tagline: <input type=text name=tagline maxLength=40 value=\"${tagLine}\"/><br />
								Website: <input type=text name=website maxLength=200 value=\"${website}\"/><br />
								<br />
								Update profile text:<br />
								<textarea class=postbox maxLength=300 name=updateProfileText>{$updateProfileText}</textarea><br />
								<input type=submit value=\"Update profile\">
							</form>");
							?>
						</td>
					</tr>
				</table>

				<?php
			}
	}

	function updateUserProfileText($id, $text, $tagLine, $website)
	{
		if(strlen($text) > 300)
		{
			error("Your profile info text cannot exceed 300 characters.");
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
		$rawText = htmlentities(mb_convert_encoding($text, 'UTF-8', 'ASCII'), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8");
		$text = sanitizeSQL(trim(bb_parse(str_replace("\n", "<br>", $rawText))));
		$rawText = sanitizeSQL($rawText);
		$website = sanitizeSQL(trim($website));
		$tagLine = sanitizeSQL(htmlentities(mb_convert_encoding(trim($tagLine), 'UTF-8', 'ASCII'), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8"));

		$sql = "UPDATE users SET profiletext='${rawText}', profiletextPreparsed='${text}', tagline='${tagLine}', website='${website}' WHERE id=${id}";
		$result = querySQL($sql);

		return true;
	}

	function showRecentThreads($start, $num)
	{
		$sql = "SELECT * FROM topics ORDER BY sticky DESC, lastposttime DESC LIMIT {$start},{$num}";
		$result = querySQL($sql);

		if($result -> num_rows > 0)
		{
			print("<table class=forumTable border=1>\n");
			print("<tr><td>Topic name</td><td class=startedby>Author</td><td>Last post by</td></tr>\n");
			while($row = $result -> fetch_assoc())
			{
				$topicID = $row['topicID'];
				$topicName = $row['topicName'];
				$numPosts = $row['numposts'];
				$creator = findUserByID($row['creatorUserID']);
				$creatorName = $creator['username'];

				if(!boolval($row['locked']) && !boolval($row['sticky']))
					$threadStatus = "";
				else
					$threadStatus = (boolval($row['sticky']) ? "&#128204; " : "") . (boolval($row['locked']) ? "&#128274; " : "");


				$lastPost = fetchSinglePost($row['lastpostid']);
				$lastPostTime = date("F d, Y H:i:s", $lastPost['postDate']);
				$postUserName = findUserByID($lastPost['userID']);
				$postUserNameIngame = $postUserName['username'];

				$quickPages = "&laquo; <a href=./?topic={$topicID}&page=0>0</a>";
				if($numPosts > 10)
				{
					$quickPages = $quickPages . " <a href=./?topic={$topicID}&page=1>1</a>";

					if($numPosts > 20)
					{
						$pagenum = ceil($numPosts / 10) - 2;
						$quickPages = $quickPages . " ... <a href=./?topic={$topicID}&page={$pagenum}>{$pagenum}</a>";

						$pagenum++;
						$quickPages = $quickPages . "  <a href=./?topic={$topicID}&page={$pagenum}>{$pagenum}</a>";
					}
				}

				$quickPages = $quickPages . " &raquo;";

				print("<tr><td>${threadStatus}<a href=\"./?topic=${topicID}\">${topicName}</a> <span class=finetext>${quickPages}</span></td><td class=startedbyrow><a href=\"./?action=viewProfile&user={$row['creatorUserID']}\">{$creatorName}</a></td><td class=lastpostrow><a href=\"./?action=viewProfile&user={$lastPost['userID']}\">{$postUserNameIngame}</a> on {$lastPostTime}</td></tr>\n");
			}
			print("</table>");
		}
		else
		{
			print("There are no threads to display!");
		}
	}

	function getRecentPosts($start, $num)
	{
		$sql = "SELECT * FROM posts ORDER BY postID DESC LIMIT {$start},{$num}";
		$result = $querySQL($sql);

		if($result -> num_rows > 0)
		{
			print("<table class=forumTable border=1>\n");
			while($row = $result -> fetch_assoc())
			{
				$user = findUserByID($row['userID']);
				$username = $user['username'];
				$date = date("F d, Y H:i:s", $row['postDate']);

				print("<tr><td class=usernamerow><a href=\"./?action=viewProfile&user={$row['userID']}\">{$username}</a><br><div class=finetext>${user['tagline']}<br /><img class=avatar src=\"./avatar.php?user=${row['userID']}\" /><br />${date}</div></td><td class=postdatarow>{$row['postPreparsed']}</td></tr>\n");
			}
			print("</table>\n");
		}
		else
		{
			print("There are no posts to display!");
		}
	}

	function fetchSinglePost($postID)
	{
		static $post = array();

		if(isSet($post[$postID]))
			return $post[$postID];

		$postID = intVal($postID);
		$sql = "SELECT * FROM posts WHERE postID={$postID}";

		$result = querySQL($sql);

		$row = $result -> fetch_assoc();
		$post[$postID] = $row;
		return $row;
	}

	function displayPostEdits($postID)
	{
		$post = fetchSinglePost(intVal($postID));

		if($post['changeID'] == false)
		{
			error("There are no changes to view on this post.");
			return;
		}

		$postID = intval($postID);
		$username = getUserNameByID($post['userID']);

		print("Viewing post edits<br>\n<table border=1 class=forumTable><tr><td class=usernamerow><a href=\"./?action=viewProfile&user={$post['userID']}\">{$username}</a><br>Current</td><td class=postdatarow>{$post['postPreparsed']}</td></tr>\n");

		$changeID = $post['changeID'];

		while($changeID > 0)
		{
			$sql = "SELECT * FROM changes WHERE id={$changeID}";
			$result = querySQL($sql);

			if($result == false)
			{
				error("Could not get edit.");
				break;
			}

			$change = $result -> fetch_assoc();
			$changeID = $change['lastChange'];
			$date = date("F d, Y H:i:s", $change['changeTime']);

			print("<tr><td class=usernamerow><a href=\"./?action=viewProfile&user={$post['userID']}\">{$username}</a><br><span class=finetext>${date}</span></td><td class=postdatarow>{$change['postData']}</td></tr>\n");
		}

		print("</table>\n");
	}

	function displayThread($threadID, $page)
	{
		$start = $page * 10;
		$end = $start + 10;
		$threadID = intVal($threadID);

		$row = findTopicbyID($threadID);
		if($row === false)
		{
			error("Failed to load thread.");
			return;
		}
		$threadControls = "";

		$sql = "SELECT * FROM posts WHERE threadID='${threadID}' ORDER BY threadIndex ASC LIMIT ${start}, ${end}";
		$posts = querySQL($sql);

		if(isSet($_SESSION['userid']))
		{
			$quotesEnabled = true;
			print("<script type=\"text/javascript\">function insertQuote(postText, authorName){ var textbox = document.getElementById(\"replytext\"); textbox.value += (textbox.value == \"\" ? \"\" : \"\\r\\n\") + \"[quote \" + authorName + \"]\" + postText + \"[/quote]\"; }</script>\n");

			if($row['creatorUserID'] == $_SESSION['userid'] || $_SESSION['admin'])
				$threadControls = "<a href=\"./?action=lockthread&thread=${threadID}\">" . (boolval($row['locked']) ? "Unlock" : "Lock") . " thread</a> &nbsp;&nbsp;";

			if($_SESSION['admin'])
			{
				$sql = "SELECT postID FROM posts WHERE threadID='${threadID}' AND threadIndex='0';";
				$result = querySQL($sql);
				$result = $result -> fetch_assoc();

				$threadControls = $threadControls . "<a href=\"./?action=stickythread&thread=${threadID}\">" . (boolval($row['sticky']) ? "Unsticky" : "Sticky") . " thread</a> &nbsp;&nbsp; <a href=\"./?action=deletepost&post=${result['postID']}\">Delete thread</a> &nbsp;&nbsp; ";
			}
		}
		else
			$quotesEnabled = false;

		if(!boolval($row['locked']) && !boolval($row['sticky']))
			$threadStatus = "&rarr;";
		else
			$threadStatus = (boolval($row['sticky']) ? "&#128204;" : "") . (boolval($row['locked']) ? "&#128274;" : "");

		print("<div class=threadHeader> ${threadStatus} Displaying thread: {$row['topicName']} &nbsp;&nbsp;${threadControls}</div>\n<table class=forumTable border=1>\n");
		while($post = $posts -> fetch_assoc())
		{
			$user = findUserByID($post['userID']);

			$username = $user['username'];

			if($post['changeID'] > 0 && isSet($_SESSION['userid']))
				$viewChanges = " <a class=inPostButtons href=\"./?action=viewedits&post={$post['postID']}\">View edits</a>   ";
			else
				$viewChanges = "";

			$makeEdit = "";

			if(isSet($_SESSION['userid']) && !boolval($row['locked']))
				if($post['userID'] == $_SESSION['userid'])
					$makeEdit = " <a class=inPostButtons href=\"./?action=edit&post={$post['postID']}&topic=${threadID}" . (isSet($_GET['page']) ? "&page={$_GET['page']}" : "&page=0") . "\">Edit post</a>   ";

			if($quotesEnabled)
				$quoteData = "<a class=inPostButtons onclick=\"insertQuote('" . javascriptEscapeString(htmlentities(mb_convert_encoding($post['postData'], 'UTF-8', 'ASCII'), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8")) . "', '{$username}');\" href=\"#replytext\">Quote/Reply</a>   ";
			else
				$quoteData = "";

			$taglineColor = "#FFFFFF";
			if($user['administrator'])
				$taglineColor = "#FFFF00; text-shadow: 0px 0px 1px #FFFFAA";
			if($user['banned'])
				$taglineColor = "#FF0000";

			$deletePost = "";
			if(isSet($_SESSION['loggedin']))
			{
				if($_SESSION['admin'])
					$deletePost = "<a class=inPostButtons href=\"./?action=deletepost&post=${post['postID']}\">Delete</a>";
			}

			$date = date("F d, Y H:i:s", $post['postDate']);
			print("<tr><td class=usernamerow><a name={$post['postID']}></a><a href=\"./?action=viewProfile&user={$post['userID']}\">{$username}</a><br><div class=finetext style=\"color:${taglineColor}\">${user['tagline']}</div><br /><img class=avatar src=\"./avatar.php?user=${post['userID']}\" /><br /><div class=finetext>${date}</div></td>\n<td class=postdatarow><div class=threadText>{$post['postPreparsed']}</div><div class=bottomstuff>{$deletePost} {$quoteData} {$makeEdit} {$viewChanges} <a class=inPostButtons href=\"./?topic={$threadID}&page={$page}#{$post['postID']}\">Permalink</a></div></td></tr>\n");
		}
		print("</table>\n");

		if($page - 2 >= 0)
		{
			$jumpPage = $page - 2;
			print("<a href=\"./?topic={$threadID}&page={$jumpPage}\">{$jumpPage}</a> ");
		}

		if($page - 1 >= 0)
		{
			$jumpPage = $page - 1;
			print("<a href=\"./?topic={$threadID}&page={$jumpPage}\">{$jumpPage}</a> ");
		}

		print("[${page}] ");

		$highestPage = floor(($row['numposts'] - 1) / 10);

		if($page + 1 <= $highestPage)
		{
			$jumpPage = $page + 1;
			print("<a href=\"./?topic={$threadID}&page={$jumpPage}\">{$jumpPage}</a> ");
		}

		if($page + 2 <= $highestPage)
		{
			$jumpPage = $page + 2;
			print("<a href=\"./?topic={$threadID}&page={$jumpPage}\">{$jumpPage}</a> ");
		}

		print("<br><br>\n");

		if(isSet($_SESSION['loggedin']) && !boolval($row['locked']))
			print("<form action=\"./?action=post&topic={$threadID}&page={$page}\" method=POST>
			<input type=hidden name=action value=newpost>
			<textarea id=\"replytext\" class=postbox name=postcontent></textarea>
			<br>
			<input type=submit name=post value=Post>
			<input type=submit name=preview value=Preview>
		</form>");
	}

	function createThread($userID, $topic, $postData)
	{
		$userID = intval($userID);
		$topic = sanitizeSQL(htmlentities(mb_convert_encoding($topic, 'UTF-8', 'ASCII'), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8"));

		$sql = "INSERT INTO topics (creatorUserID, topicName) VALUES ({$userID}, '{$topic}');";

		$mysqli = getSQLConnection();
		$result = querySQL($sql);
		$topicID = $mysqli -> insert_id;

		createPost($userID, $topicID, $postData);
		return $topicID;
   }

   function lockThread($threadID)
   {
		$threadID = intval($threadID);
		$topic = findTopicbyID($threadID);

		if($topic === false)
		{
			error("That thread does not exist.");
			return -1;
		}

		if($_SESSION['userid'] != $topic['creatorUserID'] && !$_SESSION['admin'])
		{
			error("You do not have permission to do this action.");
			return -1;
		}

		$newValue = !$topic['locked'];

		$sql = "UPDATE topics SET locked='${newValue}' WHERE topicID='{$threadID}';";

		querySQL($sql);

		adminLog("set thread (${threadID}) locked status to " . ($newValue ? "Locked" : "Not Locked") . ".");
		return $newValue;
   }

	function stickyThread($threadID)
	{
		$threadID = intval($threadID);
		$topic = findTopicbyID($threadID);

		if($topic === false)
		{
			error("That thread does not exist.");
			return -1;
		}

		if(!$_SESSION['admin'])
		{
			error("You do not have permission to do this action.");
			return -1;
		}

		$newValue = !$topic['sticky'];

		$sql = "UPDATE topics SET sticky='${newValue}' WHERE topicID='{$threadID}';";

		querySQL($sql);

		adminLog("set thread (${threadID}) sticky status to " . ($newValue ? "Sticky" : "Not Sticky") . ".");
		return $newValue;
	}

	function createPost($userID, $threadID, $postData)
	{
		$userID = intval($userID);
		$threadID = intval($threadID);

		$row = findTopicbyID($threadID);
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
		$postData = htmlentities(mb_convert_encoding($postData, 'UTF-8', 'ASCII'), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8");
		$parsedPost = sanitizeSQL(bb_parse(str_replace("\n", "\n<br>", $postData)));
		$postData = sanitizeSQL($postData);
		$date = time();

		// Make entry in posts table
		$mysqli = getSQLConnection();
		$sql = "INSERT INTO posts (userID, threadID, postDate, postData, postPreparsed, threadIndex) VALUES (${userID}, ${threadID}, '${date}', '${postData}', '${parsedPost}', '${row['numposts']}');";
		querySQL($sql);

		$postID = $mysqli -> insert_id;

		// Make new data for thread entry
		$numPosts = $row['numposts'] + 1;

		// Update thread entry
		$sql = "UPDATE topics SET lastposttime='${date}', lastpostid='${postID}', numposts='${numPosts}' WHERE topicID=${threadID}";
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

		$mysqli = getSQLConnection();
		$sql = "INSERT INTO changes (lastChange, postData, changeTime, postID, threadID) VALUES ('${post['changeID']}', '${oldPostData}', '${changeTime}', '${post['postID']}', '${post['threadID']}');";
		querySQL($sql);

		$changeID = $mysqli -> insert_id;
		$newPostData = htmlentities(mb_convert_encoding($newPostData, 'UTF-8', 'ASCII'), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8");;
		$newPostParsed = sanitizeSQL(bb_parse(str_replace("\n", "<br>", $newPostData)));
		$newPostData = sanitizeSQL($newPostData);

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

	function normalize_special_characters($str)
	{
		$str = preg_replace( chr(ord("`")), "'", $str );
		$str = preg_replace( chr(ord("´")), "'", $str );
		$str = preg_replace( chr(ord("„")), ",", $str );
		$str = preg_replace( chr(ord("`")), "'", $str );
		$str = preg_replace( chr(ord("´")), "'", $str );
		$str = preg_replace( chr(ord("“")), "\"", $str );
		$str = preg_replace( chr(ord("”")), "\"", $str );
		$str = preg_replace( chr(ord("´")), "'", $str );
			$unwanted_array = array('Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
															'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
															'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
															'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
															'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y');
			$str = strtr( $str, $unwanted_array );
			$str = preg_replace( chr(149), "&#8226;", $str );
			$str = preg_replace( chr(150), "&ndash;", $str );
			$str = preg_replace( chr(151), "&mdash;", $str );
			$str = preg_replace( chr(153), "&#8482;", $str );
			$str = preg_replace( chr(169), "&copy;", $str );
			$str = preg_replace( chr(174), "&reg;", $str );

		return $str;
	}

	// Shitty bbcode parsing hackjob because my web host won't let me install the bbcode php plugin. Alternative implementation is commented.
	function bb_parse($string)
	{
		$tags = 'b|it|un|size|color|center|delete|quote|url|img|video';
		while (preg_match_all('`\[('.$tags.')=?(.*?)\](.+?)\[/\1\]`', $string, $matches)) foreach ($matches[0] as $key => $match)
		{
			list($tag, $param, $innertext) = array($matches[1][$key], $matches[2][$key], $matches[3][$key]);
			switch ($tag) {
				case 'b': $replacement = "<strong>$innertext</strong>"; break;
				case 'it': $replacement = "<em>$innertext</em>"; break;
				case 'un': $replacement = "<u>$innertext</u>"; break;
				case 'size': $replacement = "<span style=\"font-size: " . (strstr($param, ";") !== false ? substr($param, 0, strpos($param, ";")) : $param) . ";\">$innertext</span>"; break;
				case 'color': $replacement = "<span style=\"color: " . (strstr($param, ";") !== false ? substr($param, 0, strpos($param, ";")) : $param) . ";\">$innertext</span>"; break;
				case 'center': $replacement = "<div style=\"text-align: center;\">$innertext</div>"; break;
				case 'delete': $replacement = "<span style=\"text-decoration: line-through;\">$innertext</span>"; break;
				case 'quote': $replacement = ($param ? "<br><span class=finetext>Quote from: {$param}</span>" : "<span class=finetext>Quote:</span>") . "<blockquote>{$innertext}</blockquote>"; break;
				case 'url': $replacement = '<a href="' . ($param ? $param : $innertext) . "\" target=\"_blank\">$innertext</a>"; break;
				case 'img':
					list($width, $height) = preg_split('`[Xx]`', $param);
					$replacement = "<img src=\"$innertext\" " . (is_numeric($width)? "width=\"$width\" " : '') . (is_numeric($height)? "height=\"$height\" " : '') . '/>';
				break;
				case 'video':
					$videourl = parse_url($innertext);
					parse_str($videourl['query'], $videoquery);

					if (strpos($videourl['host'], 'youtube.com') !== FALSE) $replacement = '<iframe width="500" height="281" src="https://www.youtube.com/embed/' . $videoquery['v'] . '" frameborder="0" allowfullscreen></iframe>';
					if (strpos($videourl['host'], 'google.com') !== FALSE) $replacement = '<embed src="http://video.google.com/googleplayer.swf?docid=' . $videoquery['docid'] . '" width="400" height="326" type="application/x-shockwave-flash"></embed>';
					if (strpos($videourl['host'], 'vimeo.com') !== FALSE) $replacement = '<iframe src="https://player.vimeo.com/video' . $videourl['path'] . '" width="500" height="281" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
				break;
			}
			$string = str_replace($match, $replacement, $string);
		}

		return $string;
		/*
		// New code attempt using bbcode extension.
		$bbcode = bbcode_create();

		// Enable bbcode autocorrections
		bbcode_set_flags($bbcode, BBCODE_CORRECT_REOPEN_TAGS|BBCODE_AUTO_CORRECT, BBCODE_SET_FLAGS_SET);

		// General text formatting
		bbcode_add_element($bbcode, 'i', array('type' => BBCODE_TYPE_NOARG, 'open_tag' => "<em>", 'close_tag' => "</em>", 'childs' => "b,u,s,size,color,url"));
		bbcode_add_element($bbcode, 'b', array('type' => BBCODE_TYPE_NOARG, 'open_tag' => "<b>", 'close_tag' => "</b>", 'childs' => "i,u,s,size,color,url"));
		bbcode_add_element($bbcode, 'u', array('type' => BBCODE_TYPE_NOARG, 'open_tag' => "<span style=\"text-decoration: underline;\">", 'close_tag' => "</span>", 'childs' => "b,i,u,s,size,color,url"));
		bbcode_add_element($bbcode, 's', array('type' => BBCODE_TYPE_NOARG, 'open_tag' => "<span style=\"text-decoration: line-through;\">", 'close_tag' => "</span>", 'childs' => "b,i,u,size,color,url"));
		bbcode_add_element($bbcode, 'size', array('type' => BBCODE_TYPE_ARG, 'open_tag' => "<span style=\"font-size: {PARAM}pt;\">", 'close_tag' => "</span>", 'childs' => "b,i,u,s,color,url"));
		bbcode_add_element($bbcode, 'color', array('type' => BBCODE_TYPE_ARG, 'open_tag' => "<span style=\"text-color: {PARAM};\">", 'close_tag' => "</span>", 'childs' => "b,i,u,s,size,color,url"));
		bbcode_add_element($bbcode, 'url', array('type' => BBCODE_TYPE_OPTARG, 'open_tag' => "<a href=\"{PARAM}\">", 'close_tag' => "</a>", 'default_arg' => "{CONTENT}", 'childs' => "b,i,u,s,size,color"));

		// Text alignment
		bbcode_add_element($bbcode, 'center', array('type' => BBCODE_TYME_NOARG, 'open_tag' => "<div style=\"text-align: center;\">", 'close_tag' => "</div>", 'childs' => "b,i,u,s,size,color,url,img,youtube,vimeo"));
		bbcode_add_element($bbcode, 'right', array('type' => BBCODE_TYME_NOARG, 'open_tag' => "<div style=\"text-align: right;\">", 'close_tag' => "</div>", 'childs' => "b,i,u,s,size,color,url,img,youtube,vimeo"));
		bbcode_add_element($bbcode, 'left', array('type' => BBCODE_TYME_NOARG, 'open_tag' => "<div style=\"text-align: left;\">", 'close_tag' => "</div>", 'childs' => "b,i,u,s,size,color,url,img,youtube,vimeo"));
		bbcode_add_element($bbcode, 'justify', array('type' => BBCODE_TYME_NOARG, 'open_tag' => "<div style=\"text-align: justify;\">", 'close_tag' => "</div>", 'childs' => "b,i,u,s,size,color,url,img,youtube,vimeo"));


		// Special content
		bbcode_add_element($bbcode, 'quote', array('type' => BBCODE_TYPE_OPTARG, 'open_tag' => "<br><span class=finetext>Quote from:</span> {PARAM}<blockquote>", 'close_tag' => "</blockquote>", 'default_arg' => "Unknown", 'childs' => "b,i,u,s,url,img,quote,center,right,left,justify"));
		bbcode_add_element($bbcode, 'img', array('type' => BBCODE_TYPE_NOARG, 'open_tag' => "<img src=\"", 'close_tag' => " />", 'childs' => ""));
		bbcode_add_element($bbcode, 'vimeo', array('type' => BBCODE_TYPE_ARG|BBCODE_TYPE_SINGLE, 'open_tag' => "<iframe src=\"https://player.vimeo.com/video/{PARAM}\" width=500 height=281 frameborder=0 webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>", 'childs' => ""));
		bbcode_add_element($bbcode, 'youtube', array('type' => BBCODE_TYPE_ARG|BBCODE_TYPE_SINGLE, 'open_tag' => "<iframe width=500 height=281 src=\"https://www.youtube.com/embed/{PARAM}\"  frameborder=0 allowfullscreen></iframe>", 'childs' => ""));


		$result = bbcode_parse($bbcode, $string);

		if($result === false)
		{
			error("bbcode parsing failed.");
			return $string;
		}

		return $result;
		*/
	}


	function error()
	{
		$numArgs = func_num_args();

		if($numArgs < 1)
			return;

		$text = func_get_arg(0);

		if($numArgs > 1)
			if(func_get_arg(1))
				return "<div class=errorText>" . $text . "</div>";

		print("<div class=errorText>" . $text . "</div>\r\n");
	}

	function warn()
	{
		$numArgs = func_num_args();

		if($numArgs < 1)
			return;

		$text = func_get_arg(0);

		if($numArgs > 1)
			if(func_get_arg(1))
				return "<div class=warningText>" . $text . "</div>";

		print("<div class=warningText>" . $text . "</div>\r\n");
	}

	function fatalError($error)
	{
		print('<div class="fatalErrorBox">\n<b>FATAL ERROR</b><br><br>' . $error);
		exit();
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

	function javascriptEscapeString($string)
	{
		$string = str_replace("\\", "\\\\", $string);
		$string = str_replace("\"", "\\\"", $string);
		$string = str_replace("\r", "\\r", $string);
		$string = str_replace("\n", "\\n", $string);
		$string = str_replace("'", "\\'", $string);

		return $string;
	}
?>
