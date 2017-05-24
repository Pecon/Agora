<?php
	session_start();
	$pageTitle = "REforum - Login";
	$metaTags = "<meta HTTP-EQUIV=\"Pragma\" content=\"no-cache\">
				<meta HTTP-EQUIV=\"Expires\" content=\"-1\">";
	require_once './header.php';
	print("<center>");
	require_once './navmenu.php';
	?>
		<h1>Forum Login</h1>
		<br>
		<form method=POST>
		<table class="loginTable">
			<tr>
				<td>
<?php
	require_once './functions.php';

	if(isSet($_SESSION['loggedin']))
	{
		error("You are already logged in.");
		exit();
	}
	if(!isSet($_POST['loggingin']))
	{
		print("Username:</td><td><input type=text name=username></td>
					</tr>
					<tr>
					<td>Password:</td><td><input type=password name=password></td>
					</tr><tr>
					<td><input type=hidden name=loggingin value=true><input type=submit value=\"Log in\"></td>
				</table>
				</form>
				<br><br>
				...or <a href=register.php>register here</a>
				<br><br><br><br><br><h2>Lost password?</h2><br>
				<a href=\"./index.php?action=resetpassword\">Reset your password</a>");
	}
	else
	{
		usleep(50000);

		if(!isSet($_POST['username']) || !isSet($_POST['password']))
		{
			error("Didn't you forget to send some other post variables??? Like, geeze, you're not even trying.");
			exit();
		}

		$userData = findUserByName(trim($_POST['username']));
		if($userData === false)
		{
			error("No user exists by that name.<br><a href=\"./login.php\">Try again</a>");
			exit();
		}

		$username = $userData['username'];
		$passkey = $userData['passkey'];

		if(!password_verify($_POST['password'], $passkey))
		{
			error("Incorrect password.<br><a href=\"./login.php\">Try again</a>");
			exit();
		}

		if($require_email_verification && $userData['verified'] == 0)
		{
			error("You must verify your email address before logging in.");
			exit();
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
			return;
		}

		if($_SESSION['admin'] == true)
			print("Logged in as administrator.<br><script> window.setTimeout(function(){window.location.href = \"./\";}, 1500);</script>\n");

		print("Logged in!<br><a href=\"./\">Continue</a><script> window.setTimeout(function(){window.location.href = \"./\";}, 1500);</script>\n");
	}
?>
<br>
<div class="finetext">
REforum is &#169; 2017 pecon.us <a href="./about.html">About</a>
<br>
Page created in <?php print(round($_script_time * 1000)); ?> milliseconds with <?php print($_mysqli_numQueries . " " . ($_mysqli_numQueries == 1 ? "query" : "queries")); ?>.
</div>
</center>
</body>
</html>
