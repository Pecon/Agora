<?php
	if(!file_exists("./data/settings.json"))
	{
		header("Location: \"./setup.php\"");
		exit();
	}
	
	$settings = json_decode(file_get_contents("./data/settings.json"), true);
	
	if($settings === null)
		exit("JSON parser error: " . json_last_error_msg() . " <br>As a result, unable to read config file.");
	
	if(isSet($settings['sqlserver']))
		$servername = $settings['sqlserver'];
	else
		error("Missing server config: sqlserver");
	
	if(isSet($settings['sqlusername']))
		$dbusername = $settings['sqlusername'];
	else
		error("Missing server config: sqlusername");
	
	if(isSet($settings['sqlpassword']))
		$dbpassword = $settings['sqlpassword'];
	else
		error("Missing server config: sqlpassword");
	
	if(isSet($settings['sqldatabasename']))
		$dbname = $settings['sqldatabasename'];
	else
		error("Missing server config: sqldatabasename");
	
?>