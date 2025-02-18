<?php

require_once 'autoloader.php';
require_once 'page.php';

// Return true or false depending on if they have been rate limited for the specified action
function checkRateLimitAction(string $actionName, int $cooldownSeconds, int $threshold): bool
{
	$IP = $_SERVER['REMOTE_ADDR'];

	// Clean up rows that have been unused for more than 60 minutes
	$sql = 'DELETE FROM `rateLimiting` WHERE `lastUseTime` < UNIX_TIMESTAMP() - 3600';
	$result = DBConnection::execute($sql);

	if($result === false)
	{
		error("Could not clean up rateLimiting table..?");
		// Continue anyways I guess
	}

	$sql ='SELECT * FROM `rateLimiting` WHERE `IPAddress` = ? AND `actionName` = ?';
	$result = DBConnection::execute($sql, [$IP, $actionName]);

	if($result === false)
	{
		return false;
	}

	if($result -> num_rows == 0)
	{
		// Insert a ratelimiting row for this IP and action
		$sql = 'INSERT INTO `rateLimiting` (`IPAddress`, `actionName`, `useCount`, `lastUseTime`) VALUES (?, ?, 1, UNIX_TIMESTAMP())';
		$result = DBConnection::execute($sql, [$IP, $actionName]);

		if($result === false)
		{
			error("Could not insert rateLimiting row. Is the server overloaded? Error: " . DBConnection::getError());
			return false;
		}

		return true;
	}

	// Check if they are rate limited
	$row = $result -> fetch_assoc();

	$expireTime = $row['lastUseTime'] + $cooldownSeconds;

	if(time() < $expireTime && $row['useCount'] >= $threshold)
	{
		// Rate limited.
		return false;
	}

	// Update the row
	$sql = 'UPDATE `rateLimiting` SET `useCount` = `useCount` + 1, `lastUseTime` = UNIX_TIMESTAMP() WHERE `IPAddress` = ? AND `actionName` = ?';
	$result = DBConnection::execute($sql, [$IP, $actionName]);

	if($result === false)
	{
		error("Failed to update rateLimiting row. Is the server overloaded? Error: " . DBConnection::getError());
		return false;
	}

	return true;
}