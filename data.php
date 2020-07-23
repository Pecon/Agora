<?php
	if(!file_exists("./data/.settings.json"))
	{
		header("Location: ./setup.php");
		exit();
	}

	$settings = json_decode(file_get_contents("./data/.settings.json"), true);

	if($settings === null)
		exit("JSON parser error: " . json_last_error_msg() . " <br \>As a result, unable to read config file. Please report this as a bug with your settings.json file attached (Remember to censor your database password).");

	if(isSet($settings['sql_server_address']))
		$servername = $settings['sql_server_address'];
	else
	{
		require_once 'page.php';
		error("Missing server config: sql_server_address");
	}

	if(isSet($settings['sql_username']))
		$dbusername = $settings['sql_username'];
	else
	{
		require_once 'page.php';
		error("Missing server config: sql_username");
	}

	if(isSet($settings['sql_password']))
		$dbpassword = $settings['sql_password'];
	else
	{
		require_once 'page.php';
		error("Missing server config: sql_password");
	}

	if(isSet($settings['sql_database_name']))
		$dbname = $settings['sql_database_name'];
	else
	{
		require_once 'page.php';
		error("Missing server config: sql_database_name");
	}


	// Agora global config

	$sql = "SELECT * FROM `globalSettings`;";
	$result = querySQL($sql);

	if($result === false)
	{
		error("Unable to query global settings. Something is very wrong.");
		return;
	}

	$result = $result -> fetch_all();

	global $globalSettings;
	$globalSettings = Array();

	foreach($result as $setting)
	{
		$globalSettings[$setting['settingName']] = $setting['settingValue'];
	}


	if(isSet($globalSettings['site_timezone']))
		$site_timezone = $globalSettings['site_timezone'];
	else
	{
		require_once 'page.php';
		error("Missing global setting: site_timezone");

		date_default_timezone_set($site_timezone);
	}

	if(isSet($globalSettings['site_name']))
		$site_name = $globalSettings['site_name'];
	else
	{
		require_once 'page.php';
		error("Missing global setting: site_name");
	}

	if(isSet($globalSettings['require_email_verification']))
		$require_email_verification = $globalSettings['require_email_verification'];
	else
	{
		require_once 'page.php';
		error("Missing global setting: require_email_verification");
	}

	if(isSet($globalSettings['min_password_length']))
		$min_password_length = $globalSettings['min_password_length'];
	else
	{
		require_once 'page.php';
		error("Missing global setting: min_password_length");
	}

	if(isSet($globalSettings['force_ssl']))
		$force_ssl = $globalSettings['force_ssl'];
	else
	{
		require_once 'page.php';
		error("Missing global setting: force_ssl");
	}

	if(isSet($globalSettings['items_per_page']))
		$items_per_page = $globalSettings['items_per_page'];
	else
	{
		require_once 'page.php';
		error("Missing global setting: items_per_page");
	}

	if(isSet($globalSettings['theme']))
		$site_theme = $globalSettings['theme'];
	else
	{
		require_once 'page.php';
		error("Missing global setting: theme");
	}

	if(isSet($globalSettings['show_eastereggs']))
		$show_eastereggs = $globalSettings['show_eastereggs'];
	else
		$show_eastereggs = false;
?>
