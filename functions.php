<?php
require_once 'autoloader.php';
require_once 'data.php';
require_once 'logging.php';

date_default_timezone_set($site_timezone);

function reauthuser(): void
{
	if(!isSet($_SESSION['userid']))
	{
		// Attempt to find persistent session cookie

		if(isSet($_COOKIE['agoraSession']))
		{
			$findSession = json_decode($_COOKIE['agoraSession']);

			if($findSession === false)
				return;

			$sql = 'SELECT * FROM `sessions` WHERE `userID` = ? AND `token` = ?';
			$result = DBConnection::execute($sql, [$findSession -> id, $findSession -> token]);

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

					$userData = findUserByID($findSession -> id);

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
					$sql = 'UPDATE `sessions` SET `lastSeenIP` = ?, `lastSeenTime` = ? WHERE `id` = ?;';
					DBConnection::execute($sql, [$_SERVER['REMOTE_ADDR'], $time, $session['id']]);
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

	$sql = 'SELECT COUNT(*) AS `count` FROM `privateMessages` WHERE `recipientID` = ? AND `read` = 0';
	$result = DBConnection::execute($sql, [$_SESSION['userid']]);
	$result = $result -> fetch_assoc();
	$_SESSION['unreadMessages'] = $result['count'];

	if($_SESSION['banned'] == true)
	{
		error("You have been banned.");
		setcookie("agoraSession", "");
		session_destroy();
		finishPage();
	}
}

function sendMail(string $toAddress, string $subject, string $contents): void
{
	global $site_name;
	$fromName = "$site_name Agora <donotreply@" . $_SERVER['SERVER_NAME'] . ">";
	$headers = "From: " . $fromName . "\r\n" .
					"Reply-To:" . $fromName . "\r\n" .
					'X-Mailer: PHP/' . phpversion();
	
	$message = wordwrap($contents, 70, "\r\n");
	$success = mail($toAddress, $subject, $contents, $headers);
}

function getVerificationByID(int $ID): Array
{
	$sql = 'SELECT `verification` FROM `users` WHERE `id` = ?';
	$result = DBConnection::execute($sql, [$ID]);

	$result = $result -> fetch_assoc();
	return $result['verification'];
}

function clearVerificationByID(int $ID): bool
{
	$sql = 'UPDATE `users` SET `verification` = "0" WHERE `id` = ?';
	$result = DBConnection::execute($sql, [$ID]);

	return true;
}

function verifyAccount(string $code): bool
{
	if(strlen($code) != 64)
	{
		error("Invalid verification code.");
		return false;
	}

	$sql = 'SELECT `id` FROM `users` WHERE `verification` = ?';
	$result = DBConnection::execute($sql, [$code]);

	$result = $result -> fetch_assoc();
	if(!isSet($result['id']))
		return false;

	$sql = 'UPDATE `users` SET `verified` = 1, `verification` = 0 WHERE `id` = ?';
	$result = DBConnection::execute($sql, [$ID]);

	addLogMessage("User verified their email.", 'info', $ID);

	return true;
}

function sendResetEmail(string $email): ?bool
{
	if(!filter_var($email, FILTER_VALIDATE_EMAIL))
	{
		error("That's not a valid email address.");
		return false;
	}

	$sql = 'SELECT `id`, `verified` FROM `users` WHERE `email` = ?';

	$result = DBConnection::execute($sql, [$email]);

	$result = $result -> fetch_assoc();
	if(!isSet($result['id']))
	{
		return true; // Don't let the user know that the email wasn't on file.
	}

	if($result['verified'] == 0)
	{
		return null;
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
<a href="{$url}">{$url}</a><br />
<br />
If you did not initiate this reset, you may safely disregard this email.<br />
EOF;
	$sql = 'UPDATE `users` SET `verification` = ? WHERE `id` = ?';
	$result = DBConnection::execute($sql, [$verification, $result['id']]);

	global $site_name;

	$error = mail($email, "$site_name password reset", $message, "MIME-Version: 1.0\r\nContent-type: text/html; charset=iso-utf-8\r\nFrom: donotreply@{$domain}\r\nX-Mailer: PHP/" . phpversion());
	if($error === false)
	{
		error("Failed to send verification email. Please try again later.");
		addLogMessage("Failed to send password reset email.", 'error', $result['id']);
		return false;
	}

	addLogMessage("Password reset email sent for user.", 'security', $result['id']);

	return true;
}

function findTopicByID(int $ID, bool $noCache = false): ?Array
{
	static $topic = Array();

	if(isSet($topic[$ID]) && !$noCache)
	{
		return $topic[$ID];
	}

	$sql = 'SELECT * FROM `topics` WHERE `topicID` = ? LIMIT 1';
	$result = DBConnection::execute($sql, [$ID]);

	if($result -> num_rows == 0)
	{
		return null;
	}

	return $result -> fetch_assoc();
}

function banUserByID(int $ID): bool
{
	$sql = 'UPDATE `users` SET `banned` = 1, `tagline` = "Banned user" WHERE `id` = ?';
	$result = DBConnection::execute($sql, [$ID]);

	$user = findUserByID($id);
	adminLog("Banned user \$USERID:{$ID}");
	return true;
}

function unbanUserByID(int $ID): bool
{
	$sql = 'UPDATE `users` SET `banned` = 0, `tagline` = "" WHERE `id` = ?';
	$result = DBConnection::execute($sql, [$ID]);

	$user = findUserByID($id);
	adminLog("Unbanned user \$USERID:{$id}");
	return true;
}

function toggleBanUserByID(int $ID): ?bool
{
	$user = findUserByID($ID);

	if($user === false)
	{
		error("No user exists by that id.");
		return null;
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

function promoteUserByID(int $ID): bool
{
	$sql = 'UPDATE `users` SET `usergroup` = "admin", `tagline` = "Administrator" WHERE `id` = ?';
	$result = DBConnection::execute($sql, [$ID]);

	$user = findUserByID($ID);
	adminLog("Promoted user \$USERID:{$ID} to admin.");
	return true;
}

function demoteUserByID(int $ID): bool
{
	$user = findUserByID($ID);

	if($ID == 1)
	{
		error("You cannot demote the superuser.");
		return false;
	}
	else if($user['usergroup'] != 'admin')
	{
		error("That user isn't an administrator.");
		return false;
	}

	$sql = 'UPDATE `users` SET `usergroup` = "member", `tagline` = "" WHERE `id` = ?';
	$result = DBConnection::execute($sql, [$ID]);

	
	adminLog("Demoted user \$USERID:{$ID} from admin.");
	return true;
}

function togglePromoteUserByID(int $ID): ?bool
{
	$user = findUserByID($ID);

	if($user === false)
	{
		error("No user exists by that id.");
		return null;
	}

	if($user['usergroup'] == 'admin')
	{
		demoteUserByID($ID);
		return false;
	}
	else
	{
		promoteUserByID($ID);
		return true;
	}
}

function findUserByName(string $name): Array|false
{
	// Speed up many requests by avoiding duplicate mysql queries
	static $user = array();

	if(isSet($user[$name]))
		return $user[$name];

	$sql = 'SELECT * FROM `users` WHERE lower(`username`) = ? LIMIT 1';
	$result = DBConnection::execute($sql, [$name]);

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

function findUserByID(int $ID, bool $skipCache = false): Array|false
{
	static $user = array();

	if(isSet($user[$ID]) && !$skipCache)
	{
		return $user[$ID];
	}

	$sql = 'SELECT * FROM `users` WHERE `id` = ?';
	$result = DBConnection::execute($sql, [$ID]);

	if($result -> num_rows == 0)
	{
		return false;
	}

	return $result -> fetch_assoc();
}

function updateAvatarByID(int $ID, string $imagePath): bool
{
	if(!move_uploaded_file($_FILES['avatar']['tmp_name'], $imagePath))
	{
		error("Uploaded file could not be validated.");
		return false;
	}

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
		$image = imagecreatefrombmp($imagePath);
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
	{
		unlink($imagePath);
	}

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
	$imageData = fread(fopen($imagePath, "rb"), filesize($imagePath));
	unlink($imagePath);
	$time = time();
	$sql = "UPDATE `users` SET `avatar` = ?, `avatarUpdated` = ? WHERE `id` = ?;";

	DBConnection::execute($sql, [$imageData, $time, $ID]);

	return true;
}

function getPasswordHashByID(int $ID): ?string
{
	$sql = 'SELECT `passkey` FROM `users` WHERE `id` = ?;';

	$result = DBConnection::execute($sql, [$ID]);

	if(!$result)
	{
		return null;
	}

	$hash = $result -> fetch_assoc()['passkey'] ?? null;
	return $hash;
}

function updatePasswordByID(int $ID, string $newHash): void
{
	$sql = 'UPDATE `users` SET `passkey` = ? WHERE `id` = ?';

	DBConnection::execute($sql, [$newHash, $ID]);
}

function updateEmailByID(int $ID, string $newEmail): bool
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
		$user = findUserByID($ID);
		$username = htmlentities($user['username']);

		$message = <<<EOF
This email was sent to you because an email change was initiated on your account, {$username}. If you intended to do this, please click the link below to confirm the new email:<br />
<br />
<a href="{$url}">{$url}</a><br />
<br />
If you are not the owner of the account, pleae disregard this email.<br />
EOF;

		$sql = 'UPDATE `users` SET `emailVerification`= ?, `newEmail` = ? WHERE `id` = ?';
		DBConnection::execute($sql, [$verification, $newEmail, $ID]);

		global $site_name;

		$error = mail($newEmail, "$site_name email change", $message, "MIME-Version: 1.0\r\nContent-type: text/html; charset=iso-utf-8\r\nFrom: donotreply@{$domain}\r\nX-Mailer: PHP/" . phpversion());
		if($error === false)
		{
			error("Failed to send verification email. Please try again later.");
			return false;
		}

		return true;
	}
	else
	{
		$newEmail = trim($newEmail);

		$sql = 'UPDATE `users` SET `email` = ? WHERE `id` = ?';

		DBConnection::execute($sql, [$newEmail, $ID]);
		return true;
	}
}

function verifyEmailChange(int $ID, string $verification): bool
{
	$user = findUserByID($ID);

	if($verification !== $user['emailVerification'])
	{
		error("Invalid verification code.");
		return false;
	}

	$sql = 'UPDATE `users` SET `email` = ?, `newEmail` = "", `emailVerification` = "0" WHERE `id` = ?';
	DBConnection::execute($sql, [$user['newEmail']]);

	return true;
}

function checkUserExists(string $username, string $email): bool
{
	$sql = 'SELECT * FROM `users` WHERE lower(`username`) = lower(?) OR lower(`email`) = lower(?)';
	$result = DBConnection::execute($sql, [$username, $email]);

	return $result -> num_rows > 0;
}

function displayUserProfile(int $ID): void
{
	global $_userData, $_id;
	$_userData = findUserByID($ID, true);
	$_id = $ID;

	if(!$_userData)
	{
		error("No user by this user id exists.");
		return;
	}

	loadThemePart("profile");
}

function updateUserProfileText(int $ID, string $text, string $tagLine, string $website): bool
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
		{
			$website = findUserByID($ID)['website'];
			error("Your website url is invalid or too long.");
		}
		else if(strToLower($website) == "http://")
			$website = findUserByID($ID)['website'];
		else
			$website = "";
	}

	if(strlen($tagLine) > 30)
	{
		error("Your tagline is too long.");

		$tagLine = findUserByID($ID)['tagline'];
	}

	$rawText = htmlentities(html_entity_decode($text), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8");
	$text = bb_parse($text);
	$website = trim($website);
	$tagLine = htmlentities(html_entity_decode(trim($tagLine)), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8");

	if(strlen($text) > 2000)
	{
		error("Your bbcode formatting exceeded storage parameters. Use fewer tags that expand into lots of html.");
		return false;
	}

	$sql = 'UPDATE `users` SET `profiletext` = ?, `profiletextPreparsed` = ?, `tagline` = ?, `website` = ? WHERE `id` = ?';
	$result = DBConnection::execute($sql, [$rawText, $text, $tagLine, $website]);

	return true;
}

function fetchSinglePost(int $postID): ?Array
{
	static $post = array();

	if(isSet($post[$postID]))
		return $post[$postID];

	$sql = 'SELECT * FROM `posts` WHERE `postID` = ?';

	$result = DBConnection::execute($sql, [$postID]);

	if($result === false)
	{
		return null;
	}
	else if($result -> num_rows == 0)
	{
		return null;
	}

	$row = $result -> fetch_assoc();
	$post[$postID] = $row;

	return $row;
}

function getPostLink(int $postID): string|false
{
	global $items_per_page;

	$post = fetchSinglePost($postID);

	if(!$post)
		return false;

	$topicPage = floor($post['threadIndex'] / $items_per_page);

	$link = "./?topic={$post['topicID']}&page={$topicPage}#{$post['postID']}";
	return $link;
}

function createTopic(int $userID, string $topicName, string $postText): int
{
	$topicName = htmlentities(html_entity_decode($topicName), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8");

	$sql = 'INSERT INTO `topics` (`creatorUserID`, `topicName`) VALUES (?, ?)';

	DBConnection::beginTransaction('createTopic');
	$result = DBConnection::execute($sql, [$userID, $topicName]);
	$topicID = DBConnection::getInsertID();

	createPost($userID, $topicID, $postText);

	addLogMessage('User started a new topic $TOPICID:' . $topicID);
	DBConnection::commitTransaction('createTopic');

	return $topicID;
}

function lockTopic(int $topicID): ?bool
{
	$topic = findTopicByID($topicID);

	if(!$topic)
	{
		error("That topic does not exist.");
		return null;
	}

	if($_SESSION['userid'] != $topic['creatorUserID'] && !$_SESSION['admin'])
	{
		error("You do not have permission to do this action.");
		return null;
	}

	$newValue = (int) (!$topic['locked']);

	$sql = 'UPDATE `topics` SET `locked` = ? WHERE `topicID` = ?';

	DBConnection::execute($sql, [$newValue, $topicID]);

	adminLog(($newValue ? "Locked" : "Unlocked") . " topic \$TOPICID:{$topicID}");
	return (bool) $newValue;
}

function stickyTopic(int $topicID): ?bool
{
	$topic = findTopicByID($topicID);

	if($topic === false)
	{
		error("That topic does not exist.");
		return null;
	}

	if(!$_SESSION['admin'])
	{
		error("You do not have permission to do this action.");
		return null;
	}

	$newValue = (int) (!$topic['sticky']);

	$sql = 'UPDATE `topics` SET `sticky` = ? WHERE `topicID` = ?';

	DBConnection::execute($sql, [$newValue, $topicID]);

	adminLog("Topic \$TOPICID:{$topicID} is " . ($newValue ? "now sticky" : "no longer sticky") . ".");
	return (bool) $newValue;
}

function createPost(int $userID, int $topicID, string $postText): ?int
{
	$topic = findTopicByID($topicID);
	if(!$topic)
	{
		error("Could not find thread.");
		return null;
	}

	if($topic['locked'] == true)
	{
		error("This thread is locked. No further posts are permitted.");
		return null;
	}

	// Cleanse post data
	$parsedPost = bb_parse($postText);
	$postText = htmlentities(html_entity_decode($postText), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8");
	$date = time();

	// Make entry in posts table
	DBConnection::beginTransaction('createPost');
	$sql = 'INSERT INTO `posts` (`userID`, `topicID`, `postDate`, `postData`, `postPreparsed`, `threadIndex`) VALUES (?, ?, ?, ?, ?, ?)';
	DBConnection::execute($sql, [$userID, $topicID, $date, $postText, $parsedPost, $topic['numposts']]);

	$postID = DBConnection::getInsertID();

	// Make new data for thread entry
	$numPosts = $topic['numposts'] + 1;

	// Update thread entry
	$sql = 'UPDATE `topics` SET `lastposttime` = ?, `lastpostid` = ?, `numposts` = ? WHERE `topicID` = ?';
	DBConnection::execute($sql, [$date, $postID, $numPosts, $topicID]);

	// Update user post count
	$sql = 'UPDATE `users` SET `postCount` = (SELECT COUNT(*) FROM `posts` WHERE `userID` = ?) WHERE `id` = ?';
	DBConnection::execute($sql, [$userID, $userID]);

	addLogMessage('User created a post $POSTID:' . $postID . ' in $TOPICID:' . $topicID, 'info', $userID);
	DBConnection::commitTransaction('createPost');
	
	return $postID;
}

function editPost(int $userID, int $postID, string $newPostText): bool
{
	if($_SESSION['userid'] !== $userID && !$_SESSION['admin'])
	{
		error("You do not have permission to edit this post.");
		return false;
	}

	$post = fetchSinglePost($postID);

	if(!$post)
	{
		error("This post does not exist.");
		return false;
	}

	$topic = findTopicByID($post['topicID']);

	if($topic['locked'])
	{
		error("You can't edit posts in a locked thread.");
		return false;
	}

	$changeTime = time();
	$changeID = (int) $post['changeID'];
	$oldPostText = $post['postPreparsed'];

	DBConnection::beginTransaction('editPost');
	$sql = 'INSERT INTO `changes` (`lastChange`, `postData`, `changeTime`, `postID`, `topicID`) VALUES (?, ?, ?, ?, ?)';
	DBConnection::execute($sql, [$changeID, $oldPostText, $changeTime, $post['postID'], $post['topicID']]);

	$changeID = DBConnection::getInsertID();
	$newPostParsed = bb_parse($newPostText);
	$newPostText = htmlentities(html_entity_decode($newPostText), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8");

	addLogMessage('User edited their post $POSTID:' . $postID, 'info', $userID);

	$sql = 'UPDATE `posts` SET `postData` = ?, `postPreparsed` = ?, `changeID` = ? WHERE `postID` = ?';
	DBConnection::execute($sql, [$newPostText, $newPostParsed, $changeID, $postID]);
	DBConnection::commitTransaction('editPost');

	return true;
}

function editTopicTitle(int $topicID, string $newTitle): bool
{
	$topic = findTopicByID($topicID);

	if(!$topic)
	{
		error("This topic does not exist.");
		return false;
	}

	if($_SESSION['userid'] !== $topic['creatorUserID'] && !$_SESSION['admin'])
	{
		error("You do not have permission to edit this topic's title.");
		return false;
	}

	if($topic['locked'])
	{
		error("The subject of a locked topic cannot be edited.");
		return false;
	}

	$newTitle = htmlentities($newTitle, ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8");

	if(strlen($newTitle) > 130)
	{
		error("Your subject is over the 130 character limit!");
		return false;
	}

	addLogMessage('User changed their topic\'s title from ' . $topic['topicName'] . ' to $TOPICID:' . $topicID);

	$sql = 'UPDATE `topics` SET `topicName` = ? WHERE `topicID` = ?';
	DBConnection::execute($sql, [$newTitle, $topicID]);

	return true;
}

function deletePost(int $id): bool
{
	try
	{
		$admin = new Admin();
		return $admin -> deletePost($id);
	}
	catch(Exception $error)
	{
		error($error -> getMessage());
		return false;
	}
}

// Private messaging functions

function fetchSingleMessage(int $ID): Array|false
{
	$sql = 'SELECT * FROM `privateMessages` WHERE `messageID` = ?';
	$result = DBConnection::execute($sql, [$ID]);

	if($result !== false)
		return $result -> fetch_assoc();
	else
		return false;
}

function deleteMessage(int $ID): bool
{
	$message = fetchSingleMessage($ID);

	if($message['recipientID'] != $_SESSION['userid'])
	{
		error("You cannot delete messages other than your own.");
		return false;
	}

	$sql = 'UPDATE `privateMessages` SET `deleted` = 1 WHERE `messageID` = ?';
	$result = DBConnection::execute($sql, [$ID]);

	if($result === false)
		return false;
	else
		return true;
}

function sendMessage(string $text, string $subject, int $to, ?int $replyID): bool
{
	$recipient = findUserByName($to);
	$subject = htmlentities(html_entity_decode($subject), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8");
	$parsedText = bb_parse($text);
	$text = htmlentities(html_entity_decode($text), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8");

	if($recipient === false)
	{
		warn("Could not find a user by that name.");
		return false;
	}

	$sql = 'INSERT INTO `privateMessages` (`senderID`, `recipientID`, `messageDate`, `messageData`, `messagePreparsed`, `subject`) VALUES (?, ?, ?, ?, ?, ?)';
	$time = time();
	$result = DBConnection::execute($sql, [$_SESSION['userid'], $recipient['id'], $time, $text, $parsedText, $subject]);

	if(!$result)
	{
		error("Message could not be sent for an unknown reason. This is probably a bug.");
		addLogMessage("User was unable to send a private message to $USERID:{$recipient['id']}", 'error');
		return false;
	}

	if($replyID !== null)
	{
		$reply = fetchSingleMessage($replyID);

		if($reply['recipientID'] == $_SESSION['userid'] && $reply['senderID'] == $recipient)
		{
			$sql = 'UPDATE `privateMessages` SET `read` = 2 WHERE `messageID` = ?';
			DBConnection::execute($sql, [$replyID]);
		}
	}

	addLogMessage('User sent a private message to $USERID:' . $reply['recipientID'] . '.');
	return true;

}

function displayPageNavigationButtons(int $currentPage, int $totalItems, ?string $pageAction, bool $print): void
{
	// This value can be modified from your .settings.json file (under the /data directory)
	global $items_per_page;

	if($pageAction === null)
		$pageAction = "./?page=";
	else
	{
		$pageAction = htmlentities($pageAction);
		$pageAction = "./?{$pageAction}&amp;page=";
	}
	
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

function normalize_special_characters(string $str): string
{
	// Eventually.
	return $str;
}

function charAt(string $string, int $index): string
{
	if($index >= strlen($string) || $index < 0)
		return false;

	return substr($string, $index, 1);
}

function filter_url(string $string): string
{
	// Search for "anyProtocol://anyURI"
	// If not in that format, slap an http:// onto it.
	if(!preg_match("/^(?:[A-Za-z])+:\/\/(?:[\w\W]+)$/", $string))
		$string = "http://" . $string;

	return str_replace(Array('"', '\\'), Array('%22', '%5C'), $string);
}

function filter_uri(string $string): string
{
	return str_replace(Array('"', '\\'), Array('%22', '%5C'), $string);
}

function bb_parse(string $text): string|false
{
	try
	{
		$parser = new BBCodeParser($text);

		return $parser -> getParsed();
	}
	catch(Exception $error)
	{
		error("Error in BBCode parsing: " . $error -> getMessage());
		return false;
	}
}