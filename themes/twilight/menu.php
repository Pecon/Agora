<div class="topMenu">
	<div class="siteImage">
		<a href="./"><img src="./themes/twilight/images/logo.png" alt="Agora logo"></a>
	</div>
	<div class="topMenuContent">
		<?php
			require_once 'data.php';
			global $site_timezone, $site_name;
			date_default_timezone_set($site_timezone);
			$date = str_replace(" ", "&nbsp;", date("F j, Y G:i:s"));

			if(!isSet($_SESSION['loggedin']))
			{
				print('Welcome to ' . $site_name . '! <div class="bottomstuff"><a href="./">Home</a><a href="./?action=login">Log in</a><a href="./?action=register">Register</a></div>');
			}
			
			else if($_SESSION['loggedin'])
			{
				if(!isSet($_SESSION['unreadMessages']))
					$_SESSION['unreadMessages'] = 0;

				print('Hello, <a class="userLink" href="./?action=viewProfile&amp;user=' . $_SESSION['userid'] . '">' . $_SESSION['name'] . '</a>!  The&nbsp;forum&nbsp;time&nbsp;is ' . $date . '. <div class="bottomstuff"><a href="./">Home</a> <a href="./?action=viewProfile&amp;user=' . $_SESSION['userid'] . '">Profile&nbsp;&amp;&nbsp;Settings</a> <a href="./?action=messaging">Messages' . ($_SESSION['unreadMessages'] > 0 ? "&nbsp;(${_SESSION['unreadMessages']})</a>" : "</a>") . ($_SESSION['admin'] == true ? ' <a href="./admin.php">Admin</a> ' : '') . ' <a href="./?action=logout&amp;as=' . $_SESSION['actionSecret'] . '">Log out</a></div>');
			}
			
			else
				print(error("An error occurred loading the navigation menu.", true));
		?>
	</div>
</div>
<?php
	global $show_eastereggs;

	if($show_eastereggs == true && rand(0, 99) == 0)
	{
		if(is_file("./quotes"))
		{
			$quotes = file_get_contents("./quotes");
			$quotes = explode("\n", $quotes);

			$quote = $quotes[rand(0, count($quotes) - 1)];
			$quote = str_replace(Array("[Just]", "[Sterling]"), Array('<span class="icon Just"></span>', '<span class="icon Sterling"></span>'), $quote);
			$quote = trim($quote);

			print($quote . "<br />");
		}
	}
?>