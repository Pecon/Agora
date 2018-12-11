<?php
	if(isSet($_SESSION['loggedin']))
	{
		session_unset();
		session_destroy();
		$logout = true;
	}
	else
		$logout = false;

	global $site_name;
	setPageTitle("$site_name - Logout");

	if($logout)
	{
		info("You are now logged out.<br><a href=\"./\">Back</a>", "Logout");
		addToHead("<meta http-equiv=\"refresh\" content=\"3;URL='./'\" />");
	}
	else
	{
		info("You cannot log out if you haven't logged in yet... <br><a href=\"./login.php\">Log in</a>", "Logout");
		addToHead("<meta http-equiv=\"refresh\" content=\"3;URL='./login.php'\" />");
	}

?>