<?php
	if(isSet($_SESSION['loggedin']))
	{
		$logout = null;

		if(isSet($_GET['as']))
			if($_SESSION['actionSecret'] == $_GET['as'])
			{
				$sql = "DELETE FROM sessions WHERE userID=${_SESSION['userid']} AND token='${_SESSION['token']}';";
				querySQL($sql);

				session_unset();
				session_destroy();
				$logout = true;
			}
			else
			{
				error("Incorrect action secret.");
			}
		else
		{
			error("Action secret not provided.");
		}
		
	}
	else
		$logout = false;

	global $site_name;
	setPageTitle("$site_name - Logout");

	if($logout === null)
	{

	}
	else if($logout)
	{
		info("You are now logged out.<br><a href=\"./\">Back</a>", "Logout");
		addToHead("<meta http-equiv=\"refresh\" content=\"5;URL='./'\" />");
	}
	else
	{
		info("You cannot log out if you haven't logged in yet... <br><a href=\"./?action=login\">Log in</a>", "Logout");
		addToHead("<meta http-equiv=\"refresh\" content=\"5;URL='./?action=login'\" />");
	}

?>