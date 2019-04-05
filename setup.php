<?php
	$site_timezone = "UTC";
	$site_theme = "twilight";
	require_once 'page.php';

	setNavBarEnabled(false);
	setPageTitle("Agora setup");

	if(!isSet($_POST['setup']))
	{
			$license = file_get_contents("./LICENSE");
			$form = <<<EOT
			<h1>Agora Setup</h1><br />
			<hr /><br />
			<h2>Welcome to Agora!</h2><br />
			<br />
			This setup script will help you configure Agora for the first time so you can begin using it.<br />
			However, first we need to make sure you understand that this software is licensed under the GNU Affero General Public License.<br />
			<br />
			<textarea class="license" spellcheck="false" readonly>
$license
			</textarea>
			<br /><br />
			<form method="POST">
				I have read and understand the license terms: <input type="checkbox" value="true" name="acceptLicense" required />
				<input type="hidden" name="setup" value="1" />
				<br />
				<input type="submit" value="Continue" />
			</form>
EOT;
		addToBody($form);
		finishPage();
	}

	$step = $_POST['setup'];

	if($step == 1)
	{
		if(!isSet($_POST['acceptLicense']))
			$acceptLicense = false;
		else
			$acceptLicense = boolval($_POST['acceptLicense']);

		if(!$acceptLicense)
		{
			$form = <<<EOT
			<h1>You must accept the license terms.</h1>
EOT;
			addToBody($form);
			finishPage();
		}

		$form = <<<EOT
			<h1>Agora Setup</h1><br />
			<hr /><br />
			<h2>Checking server requirements...</h2>
			<br />
EOT;
		addToBody($form);

		$issue = false;
		$problem = false;

		$software = $_SERVER['SERVER_SOFTWARE'];
		addToBody("Webserver: " . $software . " ... ");
		if(stripos($software, "apache") > -1)
			addToBody("good.<br /><br />\r\n");
		else if(stripos($software, "nginx") > -1)
			addToBody("good.<br /><br />\r\n");
		else
		{
			$problem = true;
			warn("OK.<br />" . error("Please make sure that you make the ./data directory protected from public view, as it will contain sensitive information such as your mysql server credentials.", true) . "<br /><br />");
		}

		addToBody("PHP version: " . PHP_VERSION . " ... ");
		if(version_compare(PHP_VERSION, '5.5.0') >= 0)
			addToBody("good.<br /><br />\r\n");
		else
		{
			$issue = true;
			addToBody(error("bad. Minimum requirement is 5.5.0 <br /><br />", true));
		}

		addToBody("Checking mysqli is installed: ... ");
		if(extension_loaded('mysqli'))
			addToBody("Yes.<br /><br />\r\n");
		else
		{
			$issue = true;
			addToBody(error("No. mysqli extension must be installed for Agora to work.<br /><br />", true));
			addToBody("Hint: Try installing the php-mysql package for your OS.<br /><br />");
		}

		addToBody("Checking json is installed: ... ");
		if(extension_loaded('json'))
			addToBody("Yes.<br /><br />\r\n");
		else
		{
			$issue = true;
			addToBody(error("No. json extension must be installed for Agora to work.<br /><br />", true));
			addToBody("Hint: Try installing the php-json package for your OS.<br /><br />");
		}

		addToBody("Checking mbstring is installed: ... ");
		if(extension_loaded('mbstring'))
			addToBody("Yes.<br /><br />\r\n");
		else
		{
			$issue = true;
			addToBody(error("No. mbstring extension must be installed for Agora to work.<br />", true));
			addToBody("Hint: Try installing the php-mbstring package for your OS.<br /><br />");
		}

		addToBody("Checking GD image library is installed: ... ");
		if(extension_loaded('GD'))
			addToBody("Yes.<br /><br />\r\n");
		else
		{
			$problem = true;
			addToBody(warn("No. GD image library is required for user avatars to work. This is optional, but the avatar system will likely be non functional.<br /><br />", true));
			addToBody("Hint: Try installing the php-gd package for your OS.<br /><br />");
		}

		addToBody("Checking crypto function strength: ... ");
		openssl_random_pseudo_bytes(5, $strong);
		if($strong === false)
		{
			$problem = true;
			warn("Poor. Email registration and password reset systems may be more susceptible to prediction attacks. Consider upgrading your operating system or installing a newer version of openssl.");
		}
		else
			addToBody("Strong. <br /><br />\r\n");

		if(!$issue)
		{
			if($problem)
				addToBody("Agora is probably compatible with this server. You should check the errors above to see if you can improve at all.");
			else
				addToBody("Agora is compatible with this server.");

			$form = <<<EOT
			<br />
			<form method="POST">
				<input type="hidden" name="setup" value="2" />
				<input type="submit" value="Continue" />
			</form>
EOT;
			addToBody($form);
			finishPage();
		}
		else
		{
			addToBody("Your server has one more issues that makes it imcompatible with Agora.");
			finishPage();
		}
	}

	else if($step == 2)
	{
		// Get list of timezones
		$tzlist = DateTimeZone::listIdentifiers(DateTimeZone::ALL);

		if(!isSet($_POST['server_addr']))
		{
			// Generate timezone options list
			$timezones = "";
			foreach($tzlist as $zone)
			{
				$timezones = $timezones . "\n<option value=\"$zone\" " . ($zone == "America/New_York" ? "selected" : "") . ">$zone</option>";
			}

			$form = <<<EOT
			<h1>Agora Setup</h1><br />
			<hr /><br />
			<h2>Configuration</h2><br />
			<div style="text-align: left; width: 75%">
				<form method="POST">
					<table style="width: 100%;">
					<tr><td>Forum name/Site name:<span class="rightAlign"><input type="text" name="sitename" value="Agora" required/></span></td><td class="finetext">This will show up in the brower's title bar when visiting the home page.</td></tr>
					<tr><td>Forum timezone:<span class="rightAlign"><select name="timezone">$timezones</select></span></td><td class="finetext">This timezone will be used across the site by default.</td></tr>
					<tr><td>Require email verification:<span class="rightAlign"><select name="verify"><option value="false" selected>No</option><option value="true">Yes</option></select></span></td><td class="finetext">If enabled, requires users to click a link sent to their email address before logging in for the first time.</td></tr>
					<tr><td>Minimum password length:<span class="rightAlign"><input type="text" name="passminchars" value="12" minlength="2" maxlength="72" inputmode="numeric" required /></span></td><td class="finetext">The minimum number of characters users will need to have in their passwords. Recommended value: 12.</td></tr>
					<tr><td>Force site-wide ssl:<span class="rightAlign"><select name="ssl"><option value="false" selected>No</option><option value="true">Yes</option></select></span></td><td class="finetext">If enabled, will force all traffic to be redirected to https. If you don't know what this is or https doesn't work on your site, leave this disabled.</td></td>
					<tr></tr>

					<tr><td>MySQL server:<span class="rightAlign"><input type="text" name="server_addr" value="localhost"/></span></td><td class="finetext">This value should be localhost in most configurations.</td></tr>
					<tr><td>MySQL server port:<span class="rightAlign"><input type="text" name="port" value="3306"/></span></td><td class="finetext">3306 is the default port value for most configurations.</td></td>
					<tr><td>MySQL server username:<span class="rightAlign"><input type="text" name="username" /></span></td><td class="finetext"></td></tr>
					<tr><td>MySQL server password:<span class="rightAlign"><input type="password" name="password" /></span></td><td class="finetext"></td></tr>
					<tr><td>MySQL database name:<span class="rightAlign"><input type="text" name="database" /></span></td><td class="finetext">Make sure ths name isn't the same as a database being used by other software.</td></tr>

					<tr><td><input type="hidden" name="setup" value="2"/><input type="submit" value="Save" /></tr><tr></tr></td>
					</table>
				</form>
			</div>
EOT;
			addToBody($form);
		}
		else
		{
			$site_name = htmlentities($_POST['sitename']);
			$site_timezone = $_POST['timezone'];
			$emailVerify = ($_POST['verify'] == "true" ? true : false);
			$passwordMinimum = intval($_POST['passminchars']);
			$force_ssl = ($_POST['ssl'] == "true" ? true : false);
			$server = $_POST['server_addr'];
			$port = intval($_POST['port']);
			$user = $_POST['username'];
			$pass = $_POST['password'];
			$database = $_POST['database'];

			$form = <<<EOT
			<h1>Agora Setup</h1><br />
			<hr /><br />
			<h2>Testing MySQL configuration</h2><br />
EOT;
			addToBody($form);

			$timer = microtime();
			error_reporting(E_ERROR); // Temporarily supress warnings because mysqli will throw a warning if the connection is bad, which we're testing with connect_error below. 
			$mysqli = new mysqli($server, $user, $pass, "", $port);
			error_reporting(E_ALL);

			if($mysqli -> connect_error)
			{
				addToBody(error("MySQL connection failed. Please double check the connection settings.", true));

				// Generate timezone options list
				$timezones = "";
				foreach($tzlist as $zone)
				{
					$timezones = $timezones . "\n<option value=\"$zone\" " . ($zone == $site_timezone ? "selected" : "") . ">$zone</option>";
				}

				$form = <<<EOT
			<div style="text-align: left; width: 75%">
				<form method="POST">
					<table style="width: 100%;">
					<tr><td>Forum name/Site name:<span class="rightAlign"><input type="text" name="sitename" value="$site_name" required/></span></td><td class="finetext">This will show up in the brower's title bar when visiting the home page.</td></tr>
					<tr><td>Forum timezone:<span class="rightAlign"><select name="timezone">$timezones</select></span></td><td class="finetext">This timezone will be used across the site by default.</td></tr>
					<tr><td>Require email verification:<span class="rightAlign"><select name="verify"><option value="false" selected>No</option><option value="true">Yes</option></select></span></td><td class="finetext">If enabled, requires users to click a link sent to their email address before logging in for the first time.</td></tr>
					<tr><td>Minimum password length:<span class="rightAlign"><input type="text" name="passminchars" value="$passwordMinimum" minlength="2" maxlength="72" inputmode="numeric" required /></span></td><td class="finetext">The minimum number of characters users will need to have in their passwords. Recommended value: 12.</td></tr>
					<tr><td>Force site-wide ssl:<span class="rightAlign"><select name="ssl"><option value="false" selected>No</option><option value="true">Yes</option></select></span></td><td class="finetext">If enabled, will force all traffic to be redirected to https. If you don't know what this is or https doesn't work on your site, leave this disabled.</td></td>
					<tr></tr>

					<tr><td>MySQL server:<span class="rightAlign"><input type="text" name="server_addr" value="$server"/></span></td><td class="finetext">This value should be localhost in most configurations.</td></tr>
					<tr><td>MySQL server port:<span class="rightAlign"><input type="text" name="port" value="$port"/></span></td><td class="finetext">3306 is the default port value for most configurations.</td></td>
					<tr><td>MySQL server username:<span class="rightAlign"><input type="text" name="username" value="$user" /></span></td><td class="finetext"></td></tr>
					<tr><td>MySQL server password:<span class="rightAlign"><input type="password" name="password" value="$pass" /></span></td><td class="finetext"></td></tr>
					<tr><td>MySQL database name:<span class="rightAlign"><input type="text" name="database" value="$database" /></span></td><td class="finetext">Make sure ths name isn't the same as a database being used by other software.</td></tr>

					<tr><td><input type="hidden" name="setup" value="2"/><input type="submit" value="Save" /></tr><tr></tr></td>
					</table>
				</form>
			</div>
EOT;
				addToBody($form);
				finishPage();
			}

			if(($connectTime = (microtime() - $timer) * 1000) > 50)
			{
				addToBody(warn("Warning: The MySQL server took a significant amount of time to respond (${connectTime} ms). Forum performance may be sub-optimal.<br />", true));

				if(strtolower($server) != "localhost")
					addToBody(warn("Using a remote MySQL server will probably degrade performance. Consider using a local one.", true));
			}

			$sql = "CREATE DATABASE IF NOT EXISTS ${database};";
			$result = $mysqli -> query($sql);
			if($result === false)
				finishPage(error("Failed to create database. " . $mysqli -> error, true));
			else
				addToBody("Database is created...<br />\n");

			$sql = "USE ${database};";
			$result = $mysqli -> query($sql);
			if($result === false)
				finishPage(error("Failed to select database. " . $mysqli -> error, true));
			else
				addToBody("Selected database...<br />\n");

			$sql = "CREATE TABLE IF NOT EXISTS `changes` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`lastChange` int unsigned DEFAULT NULL,
	`postData` mediumtext NOT NULL,
	`changeTime` int unsigned DEFAULT '0',
	`postID` int unsigned NOT NULL,
	`threadID` int unsigned NOT NULL,
	KEY(`postID`),
	PRIMARY KEY (`id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;";
			$result = $mysqli -> query($sql);
			if($result === false)
				finishPage(error("Failed to create changes table. " . $mysqli -> error, true));
			else
				addToBody("Created changes table...<br />\n");

			$sql = "CREATE TABLE IF NOT EXISTS `posts` (
	`postID` int unsigned NOT NULL AUTO_INCREMENT,
	`userID` int unsigned DEFAULT NULL,
	`threadID` int unsigned DEFAULT NULL,
	`postDate` bigint unsigned DEFAULT '0',
	`postData` mediumtext,
	`postPreparsed` mediumtext NOT NULL,
	`threadIndex` int unsigned DEFAULT '0',
	`changeID` int unsigned DEFAULT NULL,
	FULLTEXT(`postData`),
	KEY(`threadID`),
	KEY(`postDate`),
	KEY(`threadIndex`),
	PRIMARY KEY (`postID`)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;";
			$result = $mysqli -> query($sql);
			if($result === false)
				finishPage(error("Failed to create posts table. " . $mysqli -> error, true));
			else
				addToBody("Created posts table...<br />\n");

			$sql = "CREATE TABLE IF NOT EXISTS `topics` (
	`topicID` int unsigned NOT NULL AUTO_INCREMENT,
	`creatorUserID` int unsigned DEFAULT NULL,
	`topicName` varchar(130) DEFAULT 'No Subject',
	`lastposttime` bigint unsigned DEFAULT '0',
	`lastpostid` int unsigned DEFAULT '0',
	`numposts` int unsigned DEFAULT '0',
	`sticky` tinyint NOT NULL DEFAULT '0',
	`locked` tinyint NOT NULL DEFAULT '0',
	FULLTEXT(`topicName`),
	KEY(`lastposttime`),
	PRIMARY KEY (`topicID`)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;";
			$result = $mysqli -> query($sql);
			if($result === false)
			{
				finishPage(error("Failed to create topics table. " . $mysqli -> error, true));
			}
			else
				addToBody("Created topics table...<br />\n");

			$sql = "CREATE TABLE IF NOT EXISTS `boards` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`boardtitle` varchar(256) NOT NULL,
	`boardcategory` varchar(256) NOT NULL,
	`usergroup` enum('unverified','member','moderator','admin','superuser') DEFAULT NULL,
	PRIMARY KEY (`id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;";
			$result = $mysqli -> query($sql);
			
			if($result === false)
				finishPage(error("Failed to create boards table. " . $mysqli -> error, true));
			else
				addToBody("Created boards table...<br />\n");

			$sql = "CREATE TABLE IF NOT EXISTS `users` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`username` varchar(20) NOT NULL,
	`passkey` varchar(256) NOT NULL,
	`reg_date` bigint unsigned NOT NULL DEFAULT '0',
	`lastActive` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`email` varchar(256) NOT NULL DEFAULT '',
	`verification` varchar(64) NOT NULL DEFAULT '0',
	`verified` tinyint NOT NULL DEFAULT '1',
	`newEmail` varchar(256) DEFAULT NULL,
	`emailVerification` varchar(64) NOT NULL DEFAULT '0',
	`banned` tinyint DEFAULT '0',
	`usergroup` enum('unverified','member','moderator','admin','superuser') DEFAULT NULL,
	`postCount` int unsigned DEFAULT '0',
	`profiletext` varchar(1000) DEFAULT '',
	`profiletextPreparsed` varchar(3000) DEFAULT '',
	`tagline` varchar(40) NOT NULL DEFAULT '',
	`website` varchar(256) NOT NULL DEFAULT '',
	`avatar` blob DEFAULT NULL,
	`avatarUpdated` bigint unsigned NOT NULL DEFAULT '0',
	KEY(`username`),
	KEY(`email`),
	KEY(`verification`),
	PRIMARY KEY (`id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;";
			$result = $mysqli -> query($sql);
			
			if($result === false)
				finishPage(error("Failed to create users table. " . $mysqli -> error, true));
			else
				addToBody("Created users table...<br />\n");

			$sql = "CREATE TABLE IF NOT EXISTS `sessions` ( 
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`userID` INT UNSIGNED NOT NULL,
	`loginTime` BIGINT UNSIGNED NOT NULL,
	`lastSeenTime` BIGINT UNSIGNED NOT NULL,
	`token` TINYTEXT NOT NULL,
	`creationIP` TINYTEXT NOT NULL,
	`lastSeenIP` TINYTEXT NOT NULL,
	PRIMARY KEY (`id`),
	INDEX (`userID`)) 
	ENGINE = InnoDB;";
			$result = $mysqli -> query($sql);
			
			if($result === false)
				finishPage(error("Failed to create sessions table. " . $mysqli -> error, true));
			else
				addToBody("Created sessions table...<br />\n");

			$sql = "CREATE TABLE IF NOT EXISTS `privateMessages` (
	`messageID` int unsigned NOT NULL AUTO_INCREMENT,
	`senderID` int unsigned DEFAULT NULL,
	`recipientID` int unsigned DEFAULT NULL,
	`messageDate` bigint unsigned DEFAULT '0',
	`messageData` mediumtext NOT NULL,
	`messagePreparsed` mediumtext NOT NULL,
	`subject` varchar(130) DEFAULT 'No Subject',
	`read` tinyint DEFAULT '0',
	`deleted` tinyint DEFAULT '0',
	FULLTEXT(`messageData`),
	FULLTEXT(`subject`),
	KEY(`senderID`),
	KEY(`recipientID`),
	KEY(`messageDate`),
	PRIMARY KEY (`messageID`)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;";
			$result = $mysqli -> query($sql);
			if($result === false)
				finishPage(error("Failed to create privateMessage table. " . $mysqli -> error, true));
			else
				addToBody("Created private messaging table...<br />\n");

			// Make configuration file
			$json = array();
			$json['sql_server_address'] = $server;
			$json['sql_username'] = $user;
			$json['sql_password'] = $pass;
			$json['sql_database_name'] = $database;
			$json['min_password_length'] = $passwordMinimum; // Make this part of the setup at some point plz me
			$json['require_email_verification'] = $emailVerify;
			$json['force_ssl'] = $force_ssl;
			$json['site_name'] = $site_name;
			$json['site_timezone'] = $site_timezone;
			$json['items_per_page'] = 15;
			$json['theme'] = "twilight";
			$json['show_eastereggs'] = false;

			$jsonText = json_encode($json, JSON_PRETTY_PRINT);

			if($jsonText === false)
				finishPage(error("Fatal error: Unable to encode json file."));

			if(!file_exists("./data"))
			{
				if(mkdir("./data") === false)
				{
					chmod(".", 0775);

					if(mkdir("./data") === false)
						finishPage(error("Fatal error: Unable to create data directory. Make sure the directory Agora is installed in is writable."));
				}

				chmod("./data", 0775);
			}


			if(file_put_contents("./data/.settings.json", $jsonText) === false)
				finishPage(error("Fatal error: Unable to save settings file. Make sure the ./data directory is writable."));

			chmod("./data/.settings.json", 0775);

			$form = <<<EOT
			<br /><br />
			Server configuration completed successfully.<br />

			<table style="border: hidden;">
				<tr>
					<td style="border:hidden;">
						<form method="POST">
							<input type="hidden" name="setup" value="2" />
							<input type="submit" value="Redo configuration" />
						</form>
					</td>
					<td style="border:hidden;">
						<form method="POST">
							<input type="hidden" name="setup" value="3" />
							<input type="submit" value="Continue" />
						</form>
					</td>
				</tr>
			</table>
EOT;
			addToBody($form);
		}
	}
	else if($step == 3)
	{
		require_once './functions.php';

		if(findUserByID(1) !== false)
		{
			unlink("./setup.php");
			finishPage("<h1>Upgrade complete.</h1>");
		}

		if(!isSet($_POST['username']))
		{
			$form = <<<EOT
			<h1>Agora Setup</h1><br />
			<hr /><br />
			<h2>Create first administrator account</h2><br />
			<form method="POST">
				<input type="hidden" name="setup" value="3"/>
				Username: <input type="text" name="username" maxlength="19" /><br />
				Password: <input type="password" name="password" maxlength="72" /><br />
				Confirm Password: <input type="password" name="confirmpassword" /></span><br />
				Email: <input type="email" name="email" /><br />
				<input type="submit" value="Create" />
			</form>
EOT;
			addToBody($form);
			finishPage();
		}


		// Verify username is OK
		$username = normalize_special_characters(strip_tags($_POST['username']));
		if(strLen($username) > 20)
		{
			error("Username is too long. Pick something under 20 characters.");
			finishPage();
		}

		// Verify email is OK
		if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
			finishPage(error("Email address is invalid.", true));

		// Verify password is OK
		if($_POST['password'] !== $_POST['confirmpassword'])
		{
			error("Passwords do not match.");
			finishPage();
		}

		if(strlen($_POST['password']) < $min_password_length)
		{
			error("Error: Password is too short. Use at least ${min_password_length} characters. This is the only requirement aside from your password not being 'password'.");
			finishPage();
		}
		else if(stripos($_POST['password'], "password") !== false && strlen($_POST['password']) < 16)
		{
			error("You've got to be kidding me.");
			finishPage();
		}


		$password = password_hash(normalize_special_characters($_POST['password']), PASSWORD_BCRYPT);

		$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if($mysqli -> connect_error)
			finishPage(error("Connection failed: " . $mysqli -> connect_error, true));

		$realUsername = $username;
		$username = mysqli_real_escape_string($mysqli, $username);
		$password = mysqli_real_escape_string($mysqli, $password);
		$email = mysqli_real_escape_string($mysqli, $_POST['email']);
		$regDate = time();

		$sql = "INSERT INTO users (username, passkey, reg_date, email, usergroup, tagline) VALUES ('${username}', '${password}', ${regDate}, '${email}', 'admin', 'Administrator')";

		if($mysqli -> query($sql) === TRUE)
		{
			addToBody("<h1>Installation complete</h1><br>");
			addToBody("Your username is ${realUsername}.<br><a href=\"./?action=login\">Log in</a><br /><br /><br />For security reasons, setup.php has been deleted.");
			unlink("./setup.php"); // Delete the setup file afterwards for security reasons.
		}
		else
			finishPage(error($mysqli -> error, true));
	}

	finishPage();
?>
