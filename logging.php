<?php
	require_once './database.php';

	function addLogMessage($logMessage, $logType, $logUserID, $logIPAddress)
	{
		if(!isSet($logMessage))
		{
			warn("Refusing to create an empty log entry.");
			return false;
		}

		if(!isSet($logType))
			$logType = "info";
		else
			$logType = sanitizeSQL($logType);

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

	function adminLog(string $logMessage, int $adminUserID)
	{

	}


?>