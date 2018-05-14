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
					print('Welcome to ' . $site_name . '! Please <a href="login.php">Log in</a> to participate on this forum!<div class="bottomstuff" ><a href="./">Home</a><a href="login.php">Log in</a><a href="register.php">Register</a></div>');
				}
				
				else if($_SESSION['loggedin'])
				{
					if(!isSet($_SESSION['unreadMessages']))
						$_SESSION['unreadMessages'] = 0;

					print('Hello, <a class="userLink" href="./?action=viewProfile&amp;user=' . $_SESSION['userid'] . '">' . $_SESSION['name'] . '</a>! The current forum time is ' . $date . '. <div class="bottomstuff"><a href="./">Home</a> <a href="./?action=viewProfile&amp;user=' . $_SESSION['userid'] . '">Profile &amp; Settings</a><a href="./?action=messaging">Messages' . ($_SESSION['unreadMessages'] > 0 ? " (${_SESSION['unreadMessages']})</a>" : "</a>") . ($_SESSION['admin'] == true ? '<a href="./admin.php">Admin</a> ' : '') . '<a href="logout.php">Log out</a></div>');
				}
				
				else
					print(error("An error occurred loading the navigation menu.", true));
			?>
		</td>
</table>
<br>
<hr>