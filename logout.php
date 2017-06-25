<?php
	if(isSet($_SESSION['loggedin']))
	{
		session_destroy();
		$logout = true;
	}
	else
		$logout = false;

	require_once 'page.php';
	require_once 'data.php';

	setPageTitle("$site_name - Logout");
	
	$head = <<<EOT
	<h1>Logout</h1>
	<br>
	<table class="loginTable">
		<tr>
			<td>
EOT;
	addToBody($head);

	if($logout)
	{
		addToBody("You are now logged out.<br><a href=\"./\">Back</a>");
		addToHead("<meta http-equiv=\"refresh\" content=\"3;URL='./'\" />");
	}
	else
	{
		error("You cannot log out if you haven't logged in yet...<br><a href=\"./login.php\">Log in</a>");
		addToHead("<meta http-equiv=\"refresh\" content=\"3;URL='./login.php'\" />");
	}

	addToBody("</td></tr></table>");

	finishPage();
?>
