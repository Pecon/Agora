<?php
	session_start();
	$pageTitle = "REforum - Logout";
	$metaTags = "<meta HTTP-EQUIV=\"Pragma\" content=\"no-cache\">
				<meta HTTP-EQUIV=\"Expires\" content=\"-1\">";
	require_once './header.php';
	print("<center>");
	require_once './navmenu.php';
	?>
	<h1>Logout</h1>
	<br>
	<table class="loginTable">
		<tr>
			<td>
	<?php
	if(isSet($_SESSION['loggedin']))
	{
		session_destroy();
		print("You are now logged out.<br><a href=\"./\">Back</a><script> window.setTimeout(function(){window.location.href = \"./\";}, 1500);</script>");
	}
	else
	{
		error("You cannot log out if you haven't logged in yet...<br><a href=\"./login.php\">Log in</a>");
	}
?>
			</td>
		</tr>
	</table>
	<br>
	<br>
	<div class="finetext">
	REforum is &#169; 2017 pecon.us <a href="./about.html">About</a>
	<br>
	Page created in <?php print(round($_script_time * 1000)); ?> milliseconds with <?php print($_mysqli_numQueries . " " . ($_mysqli_numQueries == 1 ? "query" : "queries")); ?>.
	</div>
</body>
</html>
