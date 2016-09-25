<table class=navmenu border=1>
	<tr>
		<td class=logocell>
			<a href="./"><img src="./style/logo.png"></a>
		</td>
		<td>
			<?php
				if(!isSet($_SESSION['loggedin']))
				{
					print("Hello, GUEST. Please <a href=login.php>Log in</a> to post on this forum!<div class=bottomstuff><a class=bottomstuff href=\"./\">Home</a> <a class=bottomstuff href=\"login.php\">Log in</a> <a class=bottomstuff href=register.php>Register</a></div>\n");
				}
				
				else if($_SESSION['loggedin'])
				{
					date_default_timezone_set("America/Los_Angeles");
					$date = date("F j, Y G:i:s");
					print("Welcome, <a href=\"./?action=viewProfile&user={$_SESSION['userid']}\">{$_SESSION['name']}</a>! The current forum datetime is {$date} <div class=bottomstuff><a class=bottomstuff href=\"./\">Home</a>" . ($_SESSION['admin'] == true ? " <a class=bottomstuff href=\"./admin.php\">Admin</a> " : "") . "<a class=bottomstuff href=\"./?action=search\">Search</a> <a class=bottomstuff href=logout.php>Log out</a></div>\n");
				}
				
				else
					print("An error occurred loading the navigation menu.");
			?>
		</td>
</table>
<br>
<hr>