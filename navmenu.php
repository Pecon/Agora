<table class="navmenu">
	<tr>
		<td class="logocell">
			<a href="./"><img src="./style/logo.png"></a>
		</td>
		<td class="navmenu">
			<?php
				require_once 'data.php';
				global $site_timezone, $site_name;
				date_default_timezone_set($site_timezone);
				$date = date("F j, Y G:i:s");

				if(!isSet($_SESSION['loggedin']))
				{
					print("Welcome to $site_name! Please <a href=login.php>Log in</a> to participate on this forum!<div class=\"bottomstuff\"><a class=\"bottomstuff\" href=\"./\">Home</a> <a class=\"bottomstuff\" href=\"login.php\">Log in</a> <a class=\"bottomstuff\" href=\"register.php\">Register</a></div>\n");
				}
				
				else if($_SESSION['loggedin'])
				{
					print("Welcome back, <a class=\"userLink\" href=\"./?action=viewProfile&amp;user={$_SESSION['userid']}\">{$_SESSION['name']}</a>! The current forum datetime is $date. <div class=\"bottomstuff\"><a class=\"bottomstuff\" href=\"./\">Home</a> <a class=\"bottomstuff\" href=\"./?action=viewProfile&amp;user={$_SESSION['userid']}\">Profile &amp; Settings</a>" . ($_SESSION['admin'] == true ? " <a class=\"bottomstuff\" href=\"./admin.php\">Admin</a> " : "") . "<a class=\"bottomstuff\" href=\"logout.php\">Log out</a></div>\n");
				}
				
				else
					print(error("An error occurred loading the navigation menu.", true));
			?>
		</td>
</table>
<br>
<hr>