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

	function adminLog(string $logMessage)
	{
		addLogMessage($logMessage, 'admin');
	}


?>