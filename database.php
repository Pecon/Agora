<?php

require_once './data.php';
require_once './functions.php';

$_mysqli;
$_mysqli_connected = false;
$_mysqli_numQueries = 0;
$_mysqli_insert_id = 0;

function getSQLConnection()
{
	global $_mysqli, $_mysqli_connected;

	if($_mysqli_connected) // Database connection is already established
	{
		if(!$_mysqli -> connect_error)
			return $_mysqli;
		else
			// Attempt to reconnect 
			unset($_mysqli);
	}

	// Establish the database connection.
	global $servername, $dbusername, $dbpassword, $dbname;

	$_mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
	if($_mysqli -> connect_error)
	{
		fatalError("Agora was unable to connect to the MySQL database. The database has either gone offline/unreachable, or Agora is not configured properly. Please contact the server administrator.<br><br>" . $_mysqli -> connect_error);
		return false;
	}

	return $_mysqli;
}

function disconnectSQL()
{
	global $_mysqli, $_mysqli_connected;

	if($_mysqli_connected)
	{
		mysqli_close($_mysqli);
		$_mysqli_connected = false;
	}

	return true;
}

function querySQL($query)
{
	$mysqli = getSQLConnection();
	$result = $mysqli -> query($query);

	if($result === false)
	{
		// This needs be locked behind some sort of 'debug mode' config since it's a security risk... I'm sure it'll happen eventually.
		fatalError("Agora encountered an SQL query error. This is most likely a bug in Agora, please report this occurence; but make sure that the data below doesn't contain any sensitive information (like your password hash). If it does, censor it before reporting.<br><br>Technical details:<br>\nError: " . $mysqli -> error . " \n<br>\nSource function: " . debug_backtrace()[1]['function'] . "\n<br>\nFull query: " . $query);
		return false;
	}

	global $_mysqli_numQueries, $_mysqli_insert_id;
	$_mysqli_insert_id = $mysqli -> insert_id;
	$_mysqli_numQueries++;
	return $result;
}

function getLastInsertID()
{
	global $_mysqli_insert_id;
	return $_mysqli_insert_id;
}

function sanitizeSQL($value)
{
	$mysqli = getSQLConnection();

	mysqli_set_charset($mysqli, "utf8");
	$result = mysqli_real_escape_string($mysqli, $value);

	return $result;
}

function fatalError($error)
{
	global $_navBarEnabled;
	$_navBarEnabled = false;

	addToBody("<div class=\"fatalErrorBox\">\n<h1>FATAL ERROR</h1><br><br>" . $error . "</div>");
	finishPage();
}
?>
