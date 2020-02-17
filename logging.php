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

		$logMessage = sanitizeSQL($logMessage);

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
				if(!isSet($whereClause))
					$whereClause = "`logType` = '${type}'";
				else
					$whereClause .= " OR `logType` = '${type}'"
			}

			$sql = "SELECT * FROM `logs` WHERE ${whereClause} ORDER BY `logTime` DESC LIMIT ${start}, ${count}";
		}

		$result = querySQL($sql);

		if($result === false)
			return false;

		$result = $result -> fetch_assoc();
		return $result;
	}
?>