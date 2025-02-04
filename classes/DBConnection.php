<?php

require_once dirname(__DIR__) . '/data.php';
require_once dirname(__DIR__) . '/page.php';

class DBConnection
{
	private static mysqli $connection;
	private static int $insertID = 0;
	private static int $queryCount = 0;

	private static function connect(): mysqli
	{
		if(!isset(self::$connection))
		{
			global $servername, $dbusername, $dbpassword, $dbname;
			self::$connection = new mysqli($servername, $dbusername, $dbpassword, $dbname);

			if(self::$connection -> connect_error)
			{
				self::sqlError("Agora was unable to connect to the MySQL database. The database has either gone offline/unreachable, or Agora is not configured properly. Please contact the server administrator.<br><br>" . $_mysqli -> connect_error);
			}
		}

		return self::$connection;
	}

	public static function execute(string $sql, ?Array $parameters = null): mysqli_result|true
	{
		$mysqli = self::connect();

		$result = $mysqli -> execute_query($sql, $parameters);

		if(!$result)
		{
			self::sqlError("Agora encountered an SQL query error. This is most likely a bug in Agora, please report this occurence; but make sure that the data below doesn't contain any sensitive information (like your password hash). If it does, censor it before reporting.<br><br>Technical details:<br>\nError: " . $mysqli -> error . " \n<br>\nSource function: " . debug_backtrace()[1]['function'] . "\n<br>\nFull query: " . $sql);
		}

		self::$insertID = $mysqli -> insert_id;
		self::$queryCount++;

		return $result;
	}

	public static function beginTransaction(?string $name = null): bool
	{
		$mysqli = self::connect();

		return $mysqli -> begin_transaction(name: $name);
	}

	public static function rollbackTransaction(?string $name = null): bool
	{
		$mysqli = self::connect();

		return $mysqli -> rollback(name: $name);
	}

	public static function commitTransaction(?string $name = null): bool
	{
		$mysqli = self::connect();

		return $mysqli -> commit(name: $name);
	}

	public static function getInsertID(): int
	{
		return self::$insertID;
	}

	public static function getError(): string
	{
		return self::$mysqli -> error;
	}

	private static function sqlError(): never
	{
		global $_navBarEnabled;
		$_navBarEnabled = false;

		addToBody("<div class=\"fatalErrorBox\">\n<h1>FATAL ERROR</h1><br><br>" . $error . "</div>");
		finishPage();
	}
}