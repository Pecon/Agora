<?php 
	require_once './data.php';
	
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
		}
	}

	function banUserByID($id)
	{
		global $servername, $dbusername, $dbpassword, $dbname;

		// Create connection
		$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		// Check connection
		if ($mysqli->connect_error)
		{
			die("Connection failed: " . $mysqli->connect_error);
		}

		$sql = "UPDATE users SET banned=1 WHERE id={$id}";
		$result = $mysqli->query($sql);

		if($result == false)
		{
			error("Could not ban user.");
			return false;
		}
		else
		{
			error("User was banned successfully.");
		}
	}

	function findUserbyName($name)
	{
		global $servername, $dbusername, $dbpassword, $dbname;

		//Select query
		// Create connection
		$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		// Check connection
		if ($mysqli->connect_error)
		{
			die("Connection failed: " . $mysqli->connect_error);
		}

		$name = mysqli_real_escape_string($mysqli, strToLower($name));
		$sql = "SELECT * FROM users WHERE lower(username) = '{$name}'";
		$result = $mysqli->query($sql);

		if($numResults = $result->num_rows > 0)
		{
			while($row = $result->fetch_assoc())
			{
				return $row;
			}
			return false;
		}
		else
		{
			return false;
		}
		$mysqli->close();
	}

	function findUserbyID($ID)
	{
		global $servername, $dbusername, $dbpassword, $dbname;

		//Select query
		// Create connection
		$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		// Check connection
		if ($mysqli->connect_error)
		{
			die("Connection failed: " . $mysqli->connect_error);
		}

		$ID = intVal($ID);
		$sql = "SELECT * FROM users WHERE id = {$ID}";
		$result = $mysqli->query($sql);

		if($numResults = $result->num_rows > 0)
		{
			while($row = $result->fetch_assoc())
			{
				return $row;
			}
			return false;
		}
		else
		{
			return false;
		}
		$mysqli->close();
	}

	function getUserNameByID($id)
	{
		global $servername, $dbusername, $dbpassword, $dbname;

		//Select query
		// Create connection
		$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		// Check connection
		if ($mysqli->connect_error)
		{
			die("Connection failed: " . $mysqli->connect_error);
		}

		$name = mysqli_real_escape_string($mysqli, strToLower($name));
		$sql = "SELECT * FROM users WHERE id = '{$id}'";
		$result = $mysqli->query($sql);

		if($numResults = $result->num_rows > 0)
		{
			while($row = $result->fetch_assoc())
			{
				return $row['username'];
			}
			return false;
		}
		else
		{
			return false;
		}
		$mysqli->close();
	}

	function getUserBLIDByID($id)
	{
		global $servername, $dbusername, $dbpassword, $dbname;

		//Select query
		// Create connection
		$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		// Check connection
		if ($mysqli->connect_error)
		{
			die("Connection failed: " . $mysqli->connect_error);
		}

		$id = intVal($id);
		$sql = "SELECT * FROM users WHERE id = '{$id}'";
		$result = $mysqli->query($sql);

		if($numResults = $result->num_rows > 0)
		{
			while($row = $result->fetch_assoc())
			{
				return $row['BLID'];
			}
			return false;
		}
		else
		{
			return false;
		}
		$mysqli->close();
	}

	function getUserPostcountByID($id)
	{
		global $servername, $dbusername, $dbpassword, $dbname;

		//Select query
		// Create connection
		$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		// Check connection
		if ($mysqli->connect_error)
		{
			die("Connection failed: " . $mysqli->connect_error);
		}

		$id = intVal($id);
		$sql = "SELECT * FROM users WHERE id = '{$id}'";
		$result = $mysqli->query($sql);

		if($numResults = $result->num_rows > 0)
		{
			while($row = $result->fetch_assoc())
			{
				return $row['postCount'];
			}
			return false;
		}
		else
		{
			return false;
		}
		$mysqli->close();
	}

	function checkUserExists($username)
	{
		global $servername, $dbusername, $dbpassword, $dbname;

		$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($mysqli -> connect_error)
		{
			die("Connection failed: " . $mysqli->connect_error);
		}

		$username = mysqli_real_escape_string($mysqli, strToLower($username));
		$sql = "SELECT * FROM users WHERE lower(username) = '{$username}'";
		$result = $mysqli -> query($sql);

		if($result -> num_rows > 0)
		{
			while($row = $result -> fetch_assoc())
			{
				return true;
			}
			
			return false;
		}
		
		else
			return false;
		
		$mysqli -> close();
	}

	function displayUserProfile($id)
	{
		$userData = findUserByID($id);


		if($userData == false)
		{
			error("No user by this user id exists.");
			return;
		}

		$username = $userData['username'];
		$lastActive = $userData['reg_date'];
		$postCount = $userData['postCount'];
		$profileText = $userData['profiletext'];
		$profileDisplayText = $userData['profiletextPreparsed'];		

		print("<table class=forumTable border=1><tr><td>{$username}</td></tr>
				<tr><td>Posts: {$postCount}<br>
				Last activity: {$lastActive}<br>
				</td></tr>
				<tr><td>\n{$profileDisplayText}<br></td></tr></table><br>\n");
		
		if(isSet($_SESSION['userid']))
			if($_SESSION['userid'] == $id)
			{
				$updateProfileText = str_replace("<br>", "\n", $profileText);
				print("Update profile text:<br>
						<form action=\"./?action=updateprofile&amp;finishForm=1&amp;newAction=viewProfile%26user=${id}\" method=POST accept-charset=\"ISO-8859-1\">
							<textarea class=postbox name=updateProfileText>{$updateProfileText}</textarea><br>
							<input type=submit value=\"Update profile text\">
						</form>");
			}
	}

	function updateUserProfileText($id, $text)
	{
		if(strlen($text) > 300)
		{
			error("Your profile text cannot exceed 300 characters.");
			return false;
		}

		global $servername, $dbusername, $dbpassword, $dbname;

		// Create connection
		$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		// Check connection
		if ($mysqli->connect_error)
		{
			die("Connection failed: " . $mysqli->connect_error);
		}

		$rawText = htmlentities(strip_tags($text));
		$text = mysqli_real_escape_string($mysqli, trim(bb_parse(str_replace("\n", "<br>", strip_tags($rawText)))));
		$rawText = mysqli_real_escape_string($mysqli, $rawText);

		$sql = "UPDATE users SET profiletext='{$rawText}' WHERE id={$id}";
		$result = $mysqli->query($sql);

		if($result == false)
		{
			error("Could not update profile raw data.");
			return false;
		}


		$sql = "UPDATE users SET profiletextPreparsed='{$text}' WHERE id={$id}";
		$result = $mysqli->query($sql);

		if($result == false)
		{
			error("Could not update profile text.");
			return false;
		}

		return true;
	}

	function showRecentThreads($start, $num)
	{
		global $servername, $dbusername, $dbpassword, $dbname;

		//Select query
		// Create connection
		$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		// Check connection
		if ($mysqli->connect_error)
		{
			die("Connection failed: " . $mysqli->connect_error);
		}

		$sql = "SELECT * FROM topics ORDER BY sticky DESC, lastposttime DESC LIMIT {$start},{$num}";
		$result = $mysqli -> query($sql);
		
		if($result === false)
		{
			print("There are no threads to display!");
			return;
		}

		if($result -> num_rows > 0)
		{
			print("<table class=forumTable border=1>\n");
			print("<tr><td>Topic name</td><td class=startedby>Author</td><td>Last post by</td></tr>\n");
			while($row = $result->fetch_assoc())
			{
				$topicID = $row['topicID'];
				$topicName = $row['topicName'];
				$numPosts = $row['numposts'];
				$creator = findUserByID($row['creatorUserID']);
				$creatorName = $creator['username'];


				$lastPost = fetchSinglePost($row['lastpostid']);
				$lastPostTime = $lastPost['postDate'];
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

				print("<tr><td><a href=\"./?topic={$topicID}\">{$topicName}</a> <span class=finetext>{$quickPages}</span></td><td class=startedbyrow><a href=\"./?action=viewProfile&user={$row['creatorUserID']}\">{$creatorName}</a></td><td class=lastpostrow><a href=\"./?action=viewProfile&user={$lastPost['userID']}\">{$postUserNameIngame}</a> on {$lastPostTime}</td></tr>\n");
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
		global $servername, $dbusername, $dbpassword, $dbname;

		//Select query
		// Create connection
		$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		// Check connection
		if ($mysqli->connect_error)
		{
			die("Connection failed: " . $mysqli->connect_error);
		}

		$sql = "SELECT * FROM posts ORDER BY postID DESC LIMIT {$start},{$num}";
		$result = $mysqli->query($sql);
		if($result->num_rows > 0)
		{
			print("<table class=forumTable border=1>\n");
			while($row = $result->fetch_assoc())
			{
				$user = findUserByID($row['userID']);
				$username = $user['username'];
				
				print("<tr><td class=usernamerow><a href=\"./?action=viewProfile&user={$row['userID']}\">{$username}</a><br><div class=finetext></div></td><td class=postdatarow>{$row['postPreparsed']}</td></tr>\n");
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
		global $servername, $dbusername, $dbpassword, $dbname;

		//Select query
		// Create connection
		$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		// Check connection
		if ($mysqli->connect_error)
		{
			die("Connection failed: " . $mysqli->connect_error);
		}

		$postID = intVal($postID);
		$sql = "SELECT * FROM posts WHERE postID={$postID}";

		$result = $mysqli->query($sql);
		if($result == false)
		{
			error("Post fetch failed.");
			return false;
		}

		$row = $result->fetch_assoc();
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

		$username = getUserNameByID($post['userID']);

		print("Viewing post edits<br>\n<table border=1 class=forumTable><tr><td class=usernamerow><a href=\"./?action=viewProfile&user={$post['userID']}\">{$username}</a><br>Current</td><td class=postdatarow>{$post['postPreparsed']}</td></tr>\n");

		global $servername, $dbusername, $dbpassword, $dbname;

		$sqli = new mySQLi($servername, $dbusername, $dbpassword, $dbname);
		$changeID = $post['changeID'];

		while($changeID > 0)
		{
			$sql = "SELECT * FROM changes WHERE id={$changeID}";
			$result = $sqli->query($sql);

			if($result == false)
			{
				error("Could not get edit.");
				break;
			}

			$change = $result->fetch_assoc();
			$changeID = $change['lastChange'];

			print("<tr><td class=usernamerow><a href=\"./?action=viewProfile&user={$post['userID']}\">{$username}</a><br><span class=finetext>{$change['changeTime']}</span></td><td class=postdatarow>{$change['postData']}</td></tr>\n");
		}

		print("</table>\n");
	}

	function displayThread($threadID, $page)
	{
		$start = $page*10;
		$end = $start+10;

		global $servername, $dbusername, $dbpassword, $dbname;

		//Select query
		// Create connection
		$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		// Check connection
		if ($mysqli->connect_error)
		{
			die("Connection failed: " . $mysqli->connect_error);
		}

		$threadID = intVal($threadID);

		$sql = "SELECT * FROM topics WHERE topicID={$threadID}";

		$result = $mysqli->query($sql);
		if($result == false)
		{
			error("Failed to load thread.");
			return;
		}

		$row = $result->fetch_assoc();
		$posts = explode(" ", $row['posts']);
		$rowPostCount = count($posts);
		
		if(isSet($_SESSION['userid']))
		{
			$quotesEnabled = true;
			print("<script>function insertQuote(postText, authorName){ var textbox = document.getElementById(\"replytext\"); textbox.value += (textbox.value == \"\" ? \"\" : \"\\r\\n\") + \"[quote \" + authorName + \"]\" + postText + \"[/quote]\"; }</script>");
		}
		else
			$quotesEnabled = false;

		print("Showing thread: {$row['topicName']}<br>\n<table class=forumTable border=1>\n");
		for($i = $start; $i < $rowPostCount && $i < $end; $i++)
		{
			$post = fetchSinglePost($posts[$i]);
			$user = findUserByID($post['userID']);

			$username = $user['username'];

			if($post['changeID'] > 0 && isSet($_SESSION['userid']))
				$viewChanges = " <a class=inPostButtons href=\"./?action=viewedits&post={$post['postID']}\">View edits</a>   ";
			else
				$viewChanges = "";

			$makeEdit = "";
			
			if(isSet($_SESSION['userid']))
				if($post['userID'] == $_SESSION['userid'])
					$makeEdit = " <a class=inPostButtons href=\"./?action=edit&post={$post['postID']}\">Edit post</a>   ";
			
			if($quotesEnabled)
				$quoteData = "<a class=inPostButtons onclick=\"insertQuote('" . javascriptEscapeString(htmlentities($post['postData'])) . "', '{$username}');\" href=\"#replytext\">Quote/Reply</a>   ";
			else
				$quoteData = "";

			print("<tr><td class=usernamerow><a name={$post['postID']}></a><a href=\"./?action=viewProfile&user={$post['userID']}\">{$username}</a><br><div class=finetext>{$post['postDate']}</div></td>\n<td class=postdatarow>{$post['postPreparsed']}<div class=bottomstuff>{$quoteData} {$makeEdit} {$viewChanges} <a class=inPostButtons href=\"./?topic={$threadID}&page={$page}#{$post['postID']}\">Permalink</a></div></td></tr>\n");
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

		print("[{$page}] ");

		$highestPage = floor(($rowPostCount - 1) / 10);

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

		if(isSet($_SESSION['loggedin']))print("<form action=\"./?action=post&topic={$threadID}&page={$page}\" method=POST>
			<input type=hidden name=action value=newpost>
			<textarea id=\"replytext\" class=postbox name=postcontent></textarea>
			<br>
			<input type=submit name=post value=Post>
			<input type=submit name=preview value=Preview>
		</form>");
	}

	function createThread($userID, $topic, $postData)
	{
		global $servername, $dbusername, $dbpassword, $dbname;

		// Create connection
		$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		// Check connection
		if ($mysqli->connect_error)
		{
			die("Connection failed: " . $mysqli->connect_error);
		}

		$mysqli->set_charset("utf8");
		$topic = mysqli_real_escape_string($mysqli, htmlentities(strip_tags($topic)));

		$sql = "INSERT INTO topics (creatorUserID, topicName) VALUES ({$userID}, '{$topic}');";

		if($result = $mysqli->query($sql) === true)
		{
			$topicID = $mysqli->insert_id;
		}
		else
		{
			error("Error: " . $mysqli->error);
			return;
		}

		createPost($userID, $topicID, $postData);
		return $topicID;
    }

    function lockThread($threadID, $lockBool)
    {
		$threadID = intval($threadID);
		$lockBool = boolval($lockBool);
		
        global $servername, $dbusername, $dbpassword, $dbname;
		
		$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		
		if($mysqli -> connect_error)
			die("Connection failed: " . $mysqli -> connect_error);
		
		$sql = "SELECT * FROM threads WHERE threadID='{$threadID}'";
		
		$result = $mysqli -> query($sql);
		
		if($result -> num_rows < 1)
		{
			error("That thread does not exist.");
			return false;
		}
		
		$result = $result -> fetch_assoc();
		
		if($lockBool == $result['locked'])
			return true;
		
		$sql = "UPDATE threads SET locked='{$lockBool}' WHERE topicID='{$threadID}'";
		
		$result = $mysqli -> query($sql);
		
		if($result === false)
		{
			error("Could not update thread to locked status.");
			return false;
		}
		
		return true;
    }

	function createPost($userID, $threadID, $postData)
	{
		global $servername, $dbusername, $dbpassword, $dbname;

		// Create connection
		$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		// Check connection
		if ($mysqli->connect_error)
		{
			die("Connection failed: " . $mysqli->connect_error);
		}

		date_default_timezone_set("America/Los_Angeles");
		$date = mysqli_real_escape_string($mysqli, date("F j, Y G:i:s"));
		$mysqli->set_charset("utf8");
        
        // Get thread info
        $sql = "SELECT topicID,posts FROM topics WHERE topicID = {$threadID}";
		$result = $mysqli->query($sql);
		if($result->num_rows < 1)
		{
			error("Could not find thread data.");
			return;
		}

		$row = $result -> fetch_assoc();
        if($row['locked'] == true)
        {
            error("This thread is locked. No further posts are permitted.");
            return false;
        }

        // Cleanse post data
		$postData = htmlentities(strip_tags($postData));
		$parsedPost = mysqli_real_escape_string($mysqli, bb_parse(str_replace("\n", "<br>", $postData)));
		$postData = mysqli_real_escape_string($mysqli, $postData);

        // Make entry in posts table
		$sql = "INSERT INTO posts (userID, threadID, postDate, postData, postPreparsed) VALUES ({$userID}, {$threadID}, '{$date}', '{$postData}', '{$parsedPost}');";
		$result = $mysqli->query($sql);

		if($result == false)
		{
			error("Post failed. {$userID} {$threadID} {$postData}");
			return false;
		}
		$postID = $mysqli->insert_id;

        // Make new data for thread entry
		$topicPosts = $row['posts'];
		$topicPosts = trim($topicPosts . " " . $postID);
		$numPosts = count(explode(" ", $topicPosts));
		$topicPosts = mysqli_real_escape_string($mysqli, $topicPosts);
		$time = time();

        // Update thread entry
		$sql = "UPDATE topics SET posts='{$topicPosts}',lastposttime={$time},lastpostid={$postID},numposts={$numPosts} WHERE topicID={$threadID}";
		$result = $mysqli->query($sql);
		if($result == false)
		{
			error("Could not update thread.");
			return;
		}

        // Update user post count
		$postCount = getUserPostcountByID($userID) + 1;

		$sql = "UPDATE users SET postCount='{$postCount}' WHERE id={$userID}";
		$result = $mysqli->query($sql);
		if($result == false)
		{
			error("Could not update user postcount.");
			return;
		}

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

		global $servername, $dbusername, $dbpassword, $dbname;
		$sqli = new mySQLi($servername, $dbusername, $dbpassword, $dbname);

		$changeTime = mysqli_real_escape_string($sqli, date("F j, Y G:i:s"));
		$oldPostData = mysqli_real_escape_string($sqli, $post['postPreparsed']);

		$sql = "INSERT INTO changes (lastChange, postData, changeTime, postID) VALUES ('{$post['changeID']}', '{$oldPostData}', '{$changeTime}', {$post['postID']});";

		$result = $sqli->query($sql);

		if($result == false)
		{
			error("Could not create change data.");
			return;
		}

		$changeID = $sqli->insert_id;
		$newPostData = htmlentities(strip_tags($newPostData));
		$newPostParsed = mysqli_real_escape_string($sqli, bb_parse(str_replace("\n", "<br>", $newPostData)));
		$newPostData = mysqli_real_escape_string($sqli, $newPostData);

		$sql = "UPDATE posts SET postData='{$newPostData}', postPreparsed='{$newPostParsed}', changeID={$changeID} WHERE postID={$postID};";

		$result = $sqli->query($sql);

		if($result == false)
		{
			error("Could not update post.");
			return;
		}
	}

	function deletePost($id)
	{
		$id = intval($id);

		if(!$post = fetchSinglePost($id))
		{
			error("This post does not exist.");
			return false;
		}

		global $servername, $dbusername, $dbpassword, $dbname;
		$sqli = new mySQLi($servername, $dbusername, $dbpassword, $dbname);

		$sql = "SELECT * FROM topics WHERE topicID={$post['threadID']}";

		$result = $sqli->query($sql);
		if($result == false)
		{
			error("Failed to load thread.");
			return false;
		}

		$row = $result->fetch_assoc();

		if($row === false)
		{
			error("Failed to load thread listing.");
			return false;
		}
		$posts = explode(" ", $row['posts']);

		if($posts[0] == $id)
		{
			// Delete the whole thread
			error("Trying to delete whole thread.");

			$sql = "DELETE FROM topics WHERE topicID={$post['threadID']};";
			$result = $sqli->query($sql);

			if($result == false)
			{
				error("Failed to remove thread.");
				return false;
			}
			
			foreach($posts as $thispost)
			{
				$sql = "DELETE FROM posts WHERE postID={$thispost};";
				$result = $sqli->query($sql);

				if($result == false)
					error("Failed to delete post ". $thispost . " trying to continue...");
			}

			return true;
		}
		else
		{
			// Just delete this post
			error("Only deleting this post.");

			$newRow = str_replace(" " . $id, "", $row['posts']);
			$lastPost = explode(" ", $newRow);
			$lastPost = $lastPost[count($lastPost) - 1];

			$lastPostTime = strtotime(fetchSinglePost($lastPost)['postDate']);

			if($newRow != $row['posts'])
			{
				$sql = "UPDATE topics SET posts='{$newRow}', lastPostID='{$lastPost}', lastposttime='{$lastPostTime}' WHERE topicID={$post['threadID']};";
				$result = $sqli->query($sql);

				if($result == false)
				{
					error("Failed to update thread listing.");
					return false;
				}
			}
			else
			{
				error("Post is not in a thread, skipping thread listing update.");
			}


			$sql = "DELETE FROM posts WHERE postID={$id};";
			$result = $sqli->query($sql);

			if($result == false)
			{
				error("Could not delete post, but has already been removed from thread listing. Success?");
				return false;
			}
			return true;
		}


	}
    
	function normalize_special_characters($str)
	{
		// $str = preg_replace( chr(ord("`")), "'", $str );
		// $str = preg_replace( chr(ord("´")), "'", $str );
		// $str = preg_replace( chr(ord("„")), ",", $str );
		// $str = preg_replace( chr(ord("`")), "'", $str );
		// $str = preg_replace( chr(ord("´")), "'", $str );
		// $str = preg_replace( chr(ord("“")), "\"", $str );
		// $str = preg_replace( chr(ord("”")), "\"", $str );
		// $str = preg_replace( chr(ord("´")), "'", $str );
			// $unwanted_array = array('Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
															// 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
															// 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
															// 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
															// 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y');
			// $str = strtr( $str, $unwanted_array );
			// $str = preg_replace( chr(149), "&#8226;", $str );
			// $str = preg_replace( chr(150), "&ndash;", $str );
			// $str = preg_replace( chr(151), "&mdash;", $str );
			// $str = preg_replace( chr(153), "&#8482;", $str );
			// $str = preg_replace( chr(169), "&copy;", $str );
			// $str = preg_replace( chr(174), "&reg;", $str );
	   
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
                case 'size': $replacement = "<span style=\"font-size: $param;\">$innertext</span>"; break;
                case 'color': $replacement = "<span style=\"color: $param;\">$innertext</span>"; break;
                case 'center': $replacement = "<div style=\"text-align: center;\">$innertext</div>"; break;
				case 'delete': $replacement = "<span style=\"text-decoration: line-through;\">$innertext</span>"; break;
                case 'quote': $replacement = ($param ? "<br><span class=finetext>Quote from: {$param}</span>" : "<span class=finetext>Quote:</span>") . "<blockquote>{$innertext}</blockquote>"; break;
                case 'url': $replacement = '<a href="' . ($param? $param : $innertext) . "\" target=\"_blank\">$innertext</a>"; break;
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
			
		print("<div class=errorText>" . $text . "</div>");
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
 