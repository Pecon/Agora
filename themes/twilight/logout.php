<?php
	session_start();
	if(isSet($_SESSION['loggedin']))
	{
		session_unset();
		session_destroy();
		$logout = true;
	}
	else
		$logout = false;

	global($site_name);
	setPageTitle("$site_name - Logout");
	
	?>
	<h1>Logout</h1>
	<br>
	<table class="loginTable">
		<tr>
			<td>
	<?php

	if($logout)
	{
		print("You are now logged out.<br><a href=\"./\">Back</a>");
		addToHead("<meta http-equiv=\"refresh\" content=\"3;URL='./'\" />");
	}
	else
	{
		print(error("You cannot log out if you haven't logged in yet... <br><a href=\"./login.php\">Log in</a>", true));
		addToHead("<meta http-equiv=\"refresh\" content=\"3;URL='./login.php'\" />");
	}

	?>
			</td>
		</tr>
	</table>