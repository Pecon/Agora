<?php
	require_once 'page.php';
	require_once 'functions.php';

	setPageTitle("$site_name - Login");

	$start = <<<EOT
		<h1>Forum Login</h1>
		<br>
		<form method="POST">
		<table class="loginTable">
			<tr>
				<td>
EOT;
	addToBody($start);

	if(isSet($_SESSION['loggedin']))
	{
		error("You are already logged in.");
		finishPage();
	}
	if(!isSet($_POST['loggingin']))
	{
		addToBody("Username:</td><td><input type=text name=username></td>
					</tr>
					<tr>
					<td>Password:</td><td><input type=password name=password></td>
					</tr><tr>
					<td><input type=hidden name=loggingin value=true><input type=submit value=\"Log in\"></td>
				</table>
				</form>
				<br><br>
				...or <a href=register.php>register here</a>
				<br><br><br><h2>Lost password?</h2><br>
				<a href=\"./index.php?action=resetpassword\">Reset your password</a><br>");
	}
	else
	{
		usleep(5000);

		if(!isSet($_POST['username']) || !isSet($_POST['password']))
		{
			error("Didn't you forget to send some other post variables??? Like, geeze, you're not even trying.");
			finishPage();
		}

		$userData = findUserByName(trim($_POST['username']));
		if($userData === false)
		{
			error("No user exists by that name.<br><a href=\"./login.php\">Try again</a>");
			finishPage();
		}

		$username = $userData['username'];
		$passkey = $userData['passkey'];

		if(!password_verify($_POST['password'], $passkey))
		{
			error("Incorrect password.<br><a href=\"./login.php\">Try again</a>");
			finishPage();
		}

		if($require_email_verification && $userData['verified'] == 0)
		{
			error("You must verify your email address before logging in.");
			finishPage();
		}

		$_SESSION['loggedin'] = true;
		$_SESSION['name'] = $username;
		$_SESSION['admin'] = $userData['administrator'];
		$_SESSION['banned'] = $userData['banned'];
		$_SESSION['userid'] = $userData['id'];
		$_SESSION['lastpostdata'] = "";
		$_SESSION['lastpostingtime'] = time();

		if($_SESSION['banned'] == true)
		{
			error("Your account is banned. Goodbye.");
			session_destroy();
		}
		else
		{
			if($_SESSION['admin'] == true)
				addToBody("Logged in as administrator.");

			addToBody("Logged in!<br><a href=\"./\">Continue</a>");
			addToHead("<meta http-equiv=\"refresh\" content=\"3;URL='./'\" />");
		}

		addToBody("</td></tr></table></form>");
	}

	finishPage();
?>