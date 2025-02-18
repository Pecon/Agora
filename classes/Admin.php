<?php

class Admin
{
	private $adminLevel = 0;
	private $verifiedAction = false;

	public function __construct()
	{
		if(!isset($_SESSION['loggedin']))
		{
			throw new Exception("You must be logged in to perform this action.");
		}

		if(!isset($_SESSION['admin']))
		{
			throw new Exception("Internal error");
		}

		if(isset($_GET['as']) && isset($_SESSION['actionSecret']))
		{
			if($_GET['as'] == $_SESSION['actionSecret'])
				$this -> verifiedAction = true;
		}

		$this -> adminLevel = $_SESSION['admin'];
	}

	public function deletePost(int $postID): bool
	{
		if($this -> adminLevel == 0)
		{
			error("You do not have permission to perform this action. " . $this -> adminLevel);
			return false;
		}

		if(!$this -> verifiedAction)
		{
			error("Incorrect action secret.");
			return false;
		}

		if(!$post = fetchSinglePost($postID))
		{
			error("This post does not exist.");
			return false;
		}

		if($post['threadIndex'] == 0)
		{
			// Delete the thread entry as well
			$thread = findTopicByID($post['topicID']);
			$threadCreator = findUserByID($thread['creatorUserID']);

			$statement = DBConnection::execute('DELETE FROM `topics` WHERE `topicID` = ?', [$post['topicID']]);
			$statement = DBConnection::execute('DELETE FROM `posts` WHERE `topicID` = ?', [$post['topicID']]);
			$statement = DBConnection::execute('DELETE FROM `changes` WHERE `topicID` = ?', [$post['topicID']]);

			adminLog("Deleted topic by \$USERID:{$threadCreator['id']} (({$post['topicID']}) . {$thread['topicName']})");
		}
		else
		{
			// Check if we need to update the latest post data
			$topic = findTopicByID($post['topicID']);

			if($topic['lastpostid'] == $postID)
			{
				// Find the last existing post in the thread.
				$result = DBConnection::execute('SELECT `postID`, `threadIndex`, `postDate` FROM `posts` WHERE `topicID` = ? ORDER BY `threadIndex` DESC LIMIT 1, 1', [$post['topicID']]);

				$newLastPost = $result -> fetch_assoc();
				$newPostCount = $newLastPost['threadIndex'] + 1;

				// Update the thread with the new values
				DBConnection::execute('UPDATE `topics` SET `lastpostid` = ?, `lastposttime` = ?, `numposts` = ? WHERE `topicID` = ?', [$newLastPost['postID'], $newLastPost['postDate'], $newPostCount, $post['topicID']]);
			}

			// Delete just this post out of the thread
			$post = fetchSinglePost($postID);
			$postStuff = str_replace(array("\r", "\n"), " ", $post['postData']);

			DBConnection::execute('DELETE FROM `posts` WHERE `postID` = ?', [$postID]);

			// Fix thread indexes
			DBConnection::execute('UPDATE `posts` SET `threadIndex` = `threadIndex` - 1 WHERE `topicID` = ? AND `threadIndex` > ?', [$post['topicID'], $post['threadIndex']]);

			// De-increment user post count
			DBConnection::execute('UPDATE `users` SET `postCount` = `postCount` - 1 WHERE `id` = ?', [$post['userID']]);

			DBConnection::execute('DELETE FROM `changes` WHERE `postID` = ?', [$postID]);

			adminLog("Deleted post by \$USERID:{$post['userID']} (({$postID}) {$postStuff})");
		}

		return true;
	}
}