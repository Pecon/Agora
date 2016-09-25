<?php
	session_start();
	$pageTitle = "REforum - Logout";
	$metaTags = "<meta HTTP-EQUIV=\"Pragma\" content=\"no-cache\">
				<meta HTTP-EQUIV=\"Expires\" content=\"-1\">
				<meta http-equiv=\"refresh\" content=\"3; url=./\">";
	require_once './header.php';
	print("<center>");
	require_once './navmenu.php';
	?>
	<?php
	if(isSet($_SESSION['loggedin']))
	{
		session_destroy();
		print("You are now logged out.<br><a href=\"./\">Back</a>");
	}
	else
	{
		error("You cannot log out if you haven't logged in yet...<br><a href=\"./login.php\">Log in</a>");
	}
?>
</body>
</html>