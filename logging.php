<?php
	require_once './database.php';

	// addLogMessage(string logMessage, enum logType [, int logUserID, string logIPAddress]
	function addLogMessage()
	{
		$numArgs = func_num_args();

		if($numArgs > 0)
			$logMessage = func_get_arg(0);

		if($numArgs > 1)
			$logType = func_get_arg(1);

		if($numArgs > 2)
			$logUserID = func_get_arg(2);

		if($numArgs > 3)
			$logIPAddress = func_get_arg(3);


		if(!isSet($logMessage))
		{
			warn("Refusing to create an empty log entry.");
			return false;
		}

		if(!isSet($logType))
			$logType = "info";
		else
			$logType = sanitizeSQL($logType);

		if(!isSet($logUserID))
			if(isSet($_SESSION['userid']))
				$logUserID = $_SESSION['userid'];

		if(!isSet($logIPAddress))
			if(isSet($_SERVER['REMOTE_ADDR']))
				$logIPAddress = $_SERVER['REMOTE_ADDR'];

		$logMessage = sanitizeSQL(htmlentities(html_entity_decode($logMessage), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8"));

		$query = "INSERT INTO `logs`";
		$fields = "(`logType`, `logMessage`, `logTime`";
		$values = " VALUES('${logType}', '${logMessage}', UNIX_TIMESTAMP()";

		if(isSet($logUserID))
		{
			$logUserID = intval($logUserID);

			$fields .= ",`logUserID`";
			$values .= ",${logUserID}";
		}

		if(isSet($logIPAddress))
		{
			$logIPAddress = sanitizeSQL($logIPAddress);

			$fields .= ",`logIPAddress`";
			$values .= ",'${logIPAddress}'";
		}

		$query .= $fields . ")" . $values . ");";

		querySQL($query);
	}

	// Add a message to just the admin log
	function adminLog(string $logMessage)
	{
		addLogMessage($logMessage, 'admin');
	}

	// $types should be a valid string for the log type to get, or an array containing valid strings for the log types to get
	// Returns array of log entries
	function getLogs($types, int $start, int $count)
	{
		$validTypes = Array('info', 'warning', 'error', 'admin', 'security');

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
				return false;

			$types = sanitizeSQL($types);
			$sql = "SELECT * FROM `logs` WHERE `logType` = '${types}' ORDER BY `logTime` DESC LIMIT ${start}, ${count};";
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
				return false;

			foreach($types as $type)
			{
				$type = sanitizeSQL($type);

				if(!isSet($whereClause))
					$whereClause = "`logType` = '${type}'";
				else
					$whereClause .= " OR `logType` = '${type}'";
			}

			$sql = "SELECT * FROM `logs` WHERE ${whereClause} ORDER BY `logTime` DESC LIMIT ${start}, ${count}";
		}

		$result = querySQL($sql);

		if($result === false)
			return false;

		$result = $result -> fetch_all(MYSQLI_BOTH);
		return $result;
	}

	function findLogReplacers($log, $replacer)
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

	function expandLogLinks($log)
	{
		// Replace POSTID with links to posts
		$replacements = findLogReplacers($log, "POSTID");
		foreach($replacements as $replacement)
		{
			$id = $replacement[1];
			$post = fetchSinglePost($id);
			$link = '<a href="?action=gotopost&amp;post=' . $id . '" target="_BLANK">' . $id . '</a>';

			$log = str_replace($replacement[0], $link, $log);
		}

		//Replace TOPICID with links to topics
		$replacements = findLogReplacers($log, "TOPICID");
		foreach($replacements as $replacement)
		{
			$id = $replacement[1];
			$topic = findTopicByID($id);
			$link = '<a href="?topic=' . $id . '" target="_BLANK">' . $topic['topicName'] . '</a>';

			$log = str_replace($replacement[0], $link, $log);
		}

		//Replace USERID with links to users
		$replacements = findLogReplacers($log, "USERID");
		foreach($replacements as $replacement)
		{
			$id = $replacement[1];
			$user = findUserByID($id);
			$link = '<a href="?action=viewProfile&amp;user=' . $id . '" target="_BLANK">' . $user['username'] . '</a>';

			$log = str_replace($replacement[0], $link, $log);
		}

		return $log;
	}
?>