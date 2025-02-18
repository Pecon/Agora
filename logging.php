<?php
require_once 'autoloader.php';

// addLogMessage(string logMessage, enum logType [, int logUserID, string logIPAddress]
function addLogMessage(string $logMessage, string $logType = 'info', ?int $logUserID = null, ?string $logIPAddress = null): bool
{
	if(strlen($logMessage) < 1)
	{
		warn("Refusing to create an empty log entry.");
		return false;
	}

	if(!$logUserID)
	{
		if(isSet($_SESSION['userid']))
		{
			$logUserID = $_SESSION['userid'];
		}
	}

	if(!$logIPAddress)
	{
		if(isSet($_SERVER['REMOTE_ADDR']))
		{
			$logIPAddress = $_SERVER['REMOTE_ADDR'];
		}
	}

	$logMessage = htmlentities(html_entity_decode($logMessage), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8");

	$query = "INSERT INTO `logs`";
	$fields = "(`logType`, `logMessage`, `logTime`";
	$values = " VALUES(?, ?, UNIX_TIMESTAMP()";
	$valueBindings = [$logType, $logMessage];

	if($logUserID)
	{
		$fields .= ",`logUserID`";
		$values .= ",?";
		array_push($valueBindings, $logUserID);
	}

	if($logIPAddress)
	{
		$fields .= ",`logIPAddress`";
		$values .= ",?";
		array_push($valueBindings, $logIPAddress);
	}

	$query .= $fields . ")" . $values . ");";

	DBConnection::execute($query, $valueBindings);
	return true;
}

// Add a message to just the admin log
function adminLog(string $logMessage): bool
{
	return addLogMessage($logMessage, 'admin');
}

// $types should be a valid string for the log type to get, or an array containing valid strings for the log types to get
// Returns array of log entries
function getLogs(string|Array $types, int $start, int $count): ?Array
{
	$validTypes = Array('info', 'warning', 'error', 'admin', 'security');
	$valueBindings = [];

	if(is_string($types))
	{
		$valid = false;

		foreach($validTypes as $checkType)
		{
			if($types === $checkType)
			{
				$valid = true;
				break;
			}
		}

		if(!$valid)
			return null;

		$valueBindings = [$types, $start, $count];
		$sql = 'SELECT * FROM `logs` WHERE `logType` = ? ORDER BY `logTime` DESC LIMIT ?, ?';
	}
	else if(is_array($types))
	{
		$valid = true;
		foreach($types as $type)
		{
			if(!$valid)
				break;

			$valid = false;

			foreach($validTypes as $checkType)
			{
				if($type === $checkType)
				{
					$valid = true;
					break;
				}
			}
		}

		if(!$valid)
			return null;

		foreach($types as $type)
		{
			array_push($valueBindings, $type);

			if(!isSet($whereClause))
				$whereClause = '`logType` = ?';
			else
				$whereClause .= ' OR `logType` = ?';
		}

		array_push($valueBindings, $start);
		array_push($valueBindings, $count);
		$sql = 'SELECT * FROM `logs` WHERE ' . $whereClause . ' ORDER BY `logTime` DESC LIMIT ?, ?';
	}

	$result = DBConnection::execute($sql, $valueBindings);

	if($result === false)
		return null;

	$result = $result -> fetch_all(MYSQLI_ASSOC);
	return $result;
}

function findLogReplacers(string $log, string $replacer): Generator
{
	while(true)
	{
		$found = preg_match('/\$' . $replacer . ':(\d+)/', $log, $matches);

		if(!$found)
			break;

		yield $matches;
		$log = str_replace($matches[0], "", $log);
	}
}

function expandLogLinks(string $log): string
{
	// Replace POSTID with links to posts
	$replacements = findLogReplacers($log, "POSTID");
	foreach($replacements as $replacement)
	{
		$id = $replacement[1];
		$post = fetchSinglePost($id);
		$link = '<a href="?action=gotopost&amp;post=' . $id . '" target="_BLANK">' . ($post ? $id : '[Deleted post]') . '</a>';

		$log = str_replace($replacement[0], $link, $log);
	}

	//Replace TOPICID with links to topics
	$replacements = findLogReplacers($log, "TOPICID");
	foreach($replacements as $replacement)
	{
		$id = $replacement[1];
		$topic = findTopicByID($id);
		$link = '<a href="?topic=' . $id . '" target="_BLANK">' . ($topic['topicName'] ?? '[Deleted topic]') . '</a>';

		$log = str_replace($replacement[0], $link, $log);
	}

	//Replace USERID with links to users
	$replacements = findLogReplacers($log, "USERID");
	foreach($replacements as $replacement)
	{
		$id = $replacement[1];
		$user = findUserByID($id);
		$link = '<a href="?action=viewProfile&amp;user=' . $id . '" target="_BLANK">' . ($user['username'] ?? 'Deleted user') . '</a>';

		$log = str_replace($replacement[0], $link, $log);
	}

	return $log;
}