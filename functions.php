<?php
	require_once './data.php';
	require_once './database.php';
	require_once './logging.php';

	date_default_timezone_set($site_timezone);

	function reauthuser()
	{
		if(!isSet($_SESSION['userid']))
		{
			// Attempt to find persistent session cookie

			if(isSet($_COOKIE['agoraSession']))
			{
				$findSession = json_decode($_COOKIE['agoraSession']);

				if($findSession === false)
					return;

				$idCheck = intval($findSession -> id);
				$tokenCheck = sanitizeSQL($findSession -> token);

				$sql = "SELECT * FROM sessions WHERE userID=$idCheck AND token='$tokenCheck';";
				$result = querySQL($sql);

				if($result -> num_rows > 0)
				{
					$session = $result -> fetch_assoc();

					if($findSession -> token == $session['token'])
					{
						if($session['lastSeenTime'] + 60*60*24*30*12 < time())
						{
							setcookie("agoraSession", "");
							warn("Your session has expired.");
							return;
						}

						$userData = findUserbyID($idCheck);

						$_SESSION['loggedin'] = true;
						$_SESSION['name'] = $userData['username'];
						$_SESSION['banned'] = $userData['banned'];
						$_SESSION['userid'] = $userData['id'];
						$_SESSION['lastpostdata'] = "";
						$_SESSION['actionSecret'] = mt_rand(10000, 99999);
						$_SESSION['token'] = $session['token'];

						// Refresh client's cookie
						$newSession = new StdClass();
						$newSession -> token = $session['token'];
						$newSession -> id = $userData['id'];

						setcookie("agoraSession", json_encode($newSession), time()+60*60*24*30*12);

						// Update the last seen time and IP in session table
						$time = time();
						$sql = "UPDATE sessions SET lastSeenIP='${_SERVER['REMOTE_ADDR']}', lastSeenTime=${time} WHERE id=${session['id']};";
						querySQL($sql);

						// info("Your session has been restored.", "Session restored");
					}

					if(!isSet($_SESSION['loggedin']))
						return;
					else if($_SESSION['loggedin'] == false)
						return;

					// User is now logged in if those checks passed. The rest of the function will handle group stuff and check if they're banned, etc.
				}
				else
					return;
			}
			else
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
			error("You have been banned.");
			setcookie("agoraSession", "");
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

		addLogMessage("User verified their email.", 'info', $ID);

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

		if(!isSet($_SERVER['REQUEST_URI']))
			$uri = "/";
		else
		{
			$uri = $_SERVER['REQUEST_URI'];

			$uri = substr($uri, 0, strrpos($uri, '/') + 1);
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
			addLogMessage("Failed to send password reset email.", 'error', $result['id']);
			return false;
		}

		addLogMessage("Password reset email sent for user.", 'security', $result['id']);

		return true;
	}

	function findTopicbyID()
	{
		$numArgs = func_num_args();

		if($numArgs < 1 || $numArgs > 2)
			return;

		$ID = func_get_arg(0);
		$nocache = false;

		if($numArgs > 1)
			$nocache = boolval(func_get_arg(1));

		static $topic = Array();

		if(isSet($topic[$ID]) && !$nocache)
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
		adminLog("Banned user \$USERID:${id}");
		return true;
	}

	function unbanUserByID($id)
	{

		$id = intval($id);
		$sql = "UPDATE users SET banned=0, tagline='' WHERE id='${id}'";
		$result = querySQL($sql);

		$user = findUserByID($id);
		adminLog("Unbanned user \$USERID:${id}");
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
		$sql = "UPDATE users SET usergroup='admin', tagline='Administrator' WHERE id={$id}";
		$result = querySQL($sql);

		$user = findUserByID($id);
		adminLog("Promoted user \$USERID:${id} to admin.");
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
		else if($user['usergroup'] != 'admin')
		{
			error("That user isn't an administrator.");
			return false;
		}

		$sql = "UPDATE users SET usergroup='member', tagline='' WHERE id='${id}'";
		$result = querySQL($sql);

		
		adminLog("Demoted user \$USERID:${id} from admin.");
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

		if($user['usergroup'] == 'admin')
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

	function findUserbyID_nocache($ID)
	{
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
				error("Avatar is in an unsupported image format. Please make your avatar a png, jpeg, bmp, webp, or gif type image.");
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
					return false;
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
			$time = time();
			$sql = "UPDATE users SET avatar='${inputData}', avatarUpdated='${time}' WHERE id=${id};";

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
		global $_userData, $_id;
		$_userData = findUserByID_nocache($id);
		$_id = $id;

		if($_userData == false)
		{
			error("No user by this user id exists.");
			return;
		}

		loadThemePart("profile");
	}

	function updateUserProfileText($id, $text, $tagLine, $website)
	{
		if(strlen($text) > 1001)
		{
			error("Your profile info text cannot exceed 1000 characters. (" . strlen($text) . ")");
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

		if(strlen($tagLine) > 30)
		{
			error("Your tagline is too long.");

			$tagLine = findUserByID($id)['tagline'];
		}

		$id = intval($id);
		$rawText = sanitizeSQL(htmlentities(html_entity_decode($text), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8"));
		$text = sanitizeSQL(bb_parse($text));
		$website = sanitizeSQL(trim($website));
		$tagLine = sanitizeSQL(htmlentities(html_entity_decode(trim($tagLine)), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8"));

		if(strlen($text) > 2000)
		{
			error("Your bbcode formatting exceeded storage parameters. Use fewer tags that expand into lots of html.");
			return false;
		}

		$sql = "UPDATE users SET profiletext='${rawText}', profiletextPreparsed='${text}', tagline='${tagLine}', website='${website}' WHERE id=${id}";
		$result = querySQL($sql);

		return true;
	}

	function displayRecentTopics($page)
	{
		
	}

	function displayRecentPosts($start, $num)
	{
		
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

	function getPostLink($postID)
	{
		global $items_per_page;
		$postID = intval($postID);

		$post = fetchSinglePost($postID);

		if($post === false)
			return false;

		$topicPage = floor($post['threadIndex'] / $items_per_page);

		$link = "./?topic=${post['topicID']}&page=${topicPage}#${post['postID']}";
		return $link;
	}

	function displayPostEdits($postID)
	{
		
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
		addLogMessage('User started a new topic $TOPICID:' . $topicID);
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

		adminLog(($newValue ? "Locked" : "Unlocked") . " topic \$TOPICID:${topicID}");
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

		adminLog("Topic \$TOPICID:${topicID} is " . ($newValue ? "now sticky" : "no longer sticky") . ".");
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
		$sql = "INSERT INTO posts (userID, topicID, postDate, postData, postPreparsed, threadIndex) VALUES (${userID}, ${topicID}, '${date}', '${postData}', '${parsedPost}', '${row['numposts']}');";
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

		addLogMessage('User created a post $POSTID:' . $postID . ' in $TOPICID:' . $topicID, 'info', $userID);
		
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

		if(boolval(findTopicbyID($post['topicID'])['locked']))
		{
			error("You can't edit posts in a locked thread.");
			return;
		}

		$changeTime = time();
		$postID = intval($postID);
		$oldPostData = sanitizeSQL($post['postPreparsed']);

		$sql = "INSERT INTO changes (lastChange, postData, changeTime, postID, topicID) VALUES ('${post['changeID']}', '${oldPostData}', '${changeTime}', '${post['postID']}', '${post['topicID']}');";
		querySQL($sql);

		$changeID = getLastInsertID();
		$newPostParsed = sanitizeSQL(bb_parse($newPostData));
		$newPostData = sanitizeSQL(htmlentities(html_entity_decode($newPostData), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8"));

		addLogMessage('User edited their post $POSTID:' . $postID, 'info', $userID);

		$sql = "UPDATE posts SET postData='{$newPostData}', postPreparsed='{$newPostParsed}', changeID={$changeID} WHERE postID={$postID};";
		querySQL($sql);
	}

	function editTopicTitle($topicID, $newTitle)
	{
		$topicID = intval($topicID);
		$topic = findTopicbyID($topicID);

		if(!$topic)
		{
			error("This topic does not exist.");
			return;
		}

		if($_SESSION['userid'] !== $topic['creatorUserID'] && !$_SESSION['admin'])
		{
			error("You do not have permission to edit this topic's title.");
			return;
		}

		if($topic['locked'])
		{
			error("The subject of a locked topic cannot be edited.");
			return;
		}

		$newTitle = sanitizeSQL(htmlentities($newTitle, ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8"));

		if(strlen($newTitle) > 130)
		{
			error("Your subject is over the 130 character limit!");
			return;
		}

		addLogMessage('User changed their topic\'s title from ' . $topic['topicName'] . ' to $TOPICID:' . $topicID);

		$sql = "UPDATE topics SET topicName='${newTitle}' WHERE topicID=${topicID};";
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
			$thread = findTopicByID($post['topicID']);
			$threadCreator = findUserByID($thread['creatorUserID']);

			$sql = "DELETE FROM topics WHERE topicID='${post['topicID']}';";
			querySQL($sql);

			$sql = "DELETE FROM posts WHERE topicID='${post['topicID']}';";
			querySQL($sql);

			$sql = "DELETE FROM changes WHERE topicID='${post['topicID']}';";
			querySQL($sql);

			adminLog("Deleted topic by \$USERID:${threadCreator['id']} ((${post['topicID']}) . ${thread['topicName']})");
		}
		else
		{
			// Check if we need to update the latest post data
			$topic = findTopicByID($post['topicID']);

			if($topic['lastpostid'] == $id)
			{
				// Find the last existing post in the thread.
				$sql = "SELECT postID, threadIndex, postDate FROM posts WHERE topicID='${post['topicID']}' ORDER BY threadIndex DESC LIMIT 0,2";
				$result = querySQL($sql);

				// Skip the first result since it's going to be the post we're about to delete. We want the one after it.
				$result -> fetch_assoc();
				$newLastPost = $result -> fetch_assoc();
				$newPostCount = $newLastPost['threadIndex'] + 1;

				// Update the thread with the new values
				$sql = "UPDATE topics SET lastpostid='${newLastPost['postID']}', lastposttime='${newLastPost['postDate']}', numposts='${newPostCount}' WHERE topicID='${post['topicID']}';";
				querySQL($sql);
			}

			// Delete just this post out of the thread
			$post = fetchSinglePost($id);
			$postStuff = str_replace(array("\r", "\n"), " ", $post['postData']);
			$user = findUserByID($post['userID'])['username'];

			$sql = "DELETE FROM posts WHERE postID='${id}';";
			querySQL($sql);

			// Fix thread indexes
			$sql = "UPDATE posts SET threadIndex=threadIndex-1 WHERE topicID='${post['topicID']}' AND threadIndex>'${post['threadIndex']}';";
			querySQL($sql);

			// De-increment user post count
			$sql = "UPDATE users SET postCount=postCount-1 WHERE id='${post['userID']}';";
			querySQL($sql);

			$sql = "DELETE FROM changes WHERE postID='${id}';";
			querySQL($sql);

			adminLog("Deleted post by \$USERID:${post['userID']} ((${id}) ${postStuff}");
		}

		return true;
	}



	// Private messaging functions

	function displayRecentMessages($page, $sent)
	{
		
	}

	function displayMessage($ID)
	{
		
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
			warn("Could not find a user by that name.");
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

	function displayPageNavigationButtons($currentPage, $totalItems, $pageAction, $print)
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
			$quickPages = '<a href="' . $pageAction . ($currentPage - 1) . '">' . $currentPage . '</a>&nbsp;' . $quickPages;

			if($currentPage - 2 >= 0)
			{
				$quickPages = '<a href="' . $pageAction . ($currentPage - 2) . '">' . ($currentPage - 1) . '</a>&nbsp;' . $quickPages;

				if($currentPage - 3 >= 0)
					$quickPages = '<a href="' . $pageAction . '0' . '">' . '1' . '</a>&nbsp;...&nbsp;' . $quickPages;
			}
		}

		if($currentPage + 1 < $totalPages)
		{
			$quickPages = $quickPages . '&nbsp;<a href="' . $pageAction . ($currentPage + 1) . '">' . ($currentPage + 2) . '</a>';

			if($currentPage + 2 < $totalPages)
			{
				$quickPages = $quickPages . '&nbsp;<a href="' . $pageAction . ($currentPage + 2) . '">' . ($currentPage + 3) . '</a>';

				if($currentPage + 3 < $totalPages)
				{
					$quickPages = $quickPages . '&nbsp;...&nbsp;<a href="' . $pageAction . ($totalPages - 1) . '">' . $totalPages . '</a>';
				}
			}
		}

		$quickPages = '&laquo;&nbsp;' . $quickPages . '&nbsp;&raquo;';

		//addToBody('<br />');
		// addToBody('<div class="finetext">Navigation</div>');
		if($print)
			print($quickPages);
		else
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
?>
