<?php
	if(!file_exists("./data/.settings.json"))
	{
		header("Location: ./setup.php");
		exit();
	}

	$settings = json_decode(file_get_contents("./data/.settings.json"), true);

	if($settings === null)
		exit("JSON parser error: " . json_last_error_msg() . " <br>As a result, unable to read config file. Please report this as a bug with your settings.json file attached (Remember to censor your database password).");

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

	if(isSet($settings['site_name']))
		$site_name = $settings['site_name'];
	else
	{
		require_once 'page.php';
		error("Missing server config: site_name");
	}
	
	if(isSet($settings['site_timezone']))
		$site_timezone = $settings['site_timezone'];
	else
	{
		require_once 'page.php';
		error("Missing server config: site_timezone");

		date_default_timezone_set($site_timezone);
	}

	if(isSet($settings['require_email_verification']))
		$require_email_verification = $settings['require_email_verification'];
	else
	{
		require_once 'page.php';
		error("Missing server config: require_email_verification");
	}

	if(isSet($settings['min_password_length']))
		$min_password_length = $settings['min_password_length'];
	else
	{
		require_once 'page.php';
		error("Missing server config: min_password_length");
	}

	if(isSet($settings['force_ssl']))
		$force_ssl = $settings['force_ssl'];
	else
	{
		require_once 'page.php';
		error("Missing server config: force_ssl");
	}

	if(isSet($settings['items_per_page']))
		$items_per_page = $settings['items_per_page'];
	else
	{
		require_once 'page.php';
		error("Missing server config: items_per_page");
	}

	if(isSet($settings['theme']))
		$site_theme = $settings['theme'];
	else
	{
		require_once 'page.php';
		error("Missing server config: theme");
	}


	if(isSet($settings['show_eastereggs']))
		$show_eastereggs = $settings['show_eastereggs'];
	else
		$show_eastereggs = false;
?>
