<?php

class admin
{
	private $adminLevel = 0;
	private $verifiedAction = false;

	public function __construct()
	{
		if(!isset($_SESSION['loggedin']))
		{
			throw new Exception("You must be logged in to perform this action.");
			return false;
		}

		if(!isset($_SESSION['admin']))
		{
			return false;
		}

		if(isset($_GET['as']) && isset($_SESSION['actionSecret']))
		{
			if($_GET['as'] == $_SESSION['actionSecret'])
				$this -> verifiedAction = true;
		}

		$this -> adminLevel = $_SESSION['admin'];
		return true;
	}

	public function deletePost(int $postID)
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

			$statement = prepareStatement("DELETE FROM topics WHERE topicID=?");
			$statement -> bind_param('i', $post['topicID']);
			executeStatement($statement);

			$statement = prepareStatement("DELETE FROM posts WHERE topicID=?");
			$statement -> bind_param('i', $post['topicID']);
			executeStatement($statement);

			$statement = prepareStatement("DELETE FROM changes WHERE topicID=?");
			$statement -> bind_param(i, $post['topicID']);
			executeStatement($statement);

			adminLog("Deleted topic by \$USERID:${threadCreator['postID']} ((${post['topicID']}) . ${thread['topicName']})");
		}
		else
		{
			// Check if we need to update the latest post data
			$topic = findTopicByID($post['topicID']);

			if($topic['lastpostid'] == $postID)
			{
				// Find the last existing post in the thread.
				$statement = prepareStatement("SELECT postID, threadIndex, postDate FROM posts WHERE topicID=? ORDER BY threadIndex DESC LIMIT 0,2");
				$statement -> bind_param('i', $post['topicID']);
				$result = executeStatement($statement);

				// Skip the first result since it's going to be the post we're about to delete. We want the one after it.
				$result -> fetch_assoc();
				$newLastPost = $result -> fetch_assoc();
				$newPostCount = $newLastPost['threadIndex'] + 1;

				// Update the thread with the new values
				$statement = prepareStatement("UPDATE topics SET lastpostid=?, lastposttime=?, numposts=? WHERE topicID=?");
				$statement -> bind_param('iiii', $newLastPost['postID'], $newLastPost['postDate'], $newPostCount, $post['topicID']);
				executeStatement($statement);
			}

			// Delete just this post out of the thread
			$post = fetchSinglePost($postID);
			$postStuff = str_replace(array("\r", "\n"), " ", $post['postData']);
			$user = findUserByID($post['userID'])['username'];

			$statement = prepareStatement("DELETE FROM posts WHERE postID=?");
			$statement -> bind_param('i', $postID);
			executeStatement($statement);

			// Fix thread indexes
			$statement = prepareStatement("UPDATE posts SET threadIndex = threadIndex - 1 WHERE topicID=? AND threadIndex > ?");
			$statement -> bind_param('ii', $post['topicID'], $post['threadIndex']);
			executeStatement($statement);

			// De-increment user post count
			$statement = prepareStatement("UPDATE users SET postCount = postCount - 1 WHERE id=?");
			$statement -> bind_param('i', $post['userID']);
			executeStatement($statement);

			$statement = prepareStatement("DELETE FROM changes WHERE postID=?");
			$statement -> bind_param('i', $postID);
			executeStatement($statement);

			adminLog("Deleted post by \$USERID:${post['userID']} ((${postID}) ${postStuff})");
		}

		return true;
	}
}