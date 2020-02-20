<?php
	global $site_name, $min_password_length, $force_ssl, $settings;
	setPageTitle("$site_name - Register");
	setPageDescription("Register for $site_name to participate in this community!");

	function showRegisterForm($fillUsername, $fillPassword, $fillEmail)
	{
		global $site_name, $min_password_length, $force_ssl, $settings;
		$fillUsername = htmlentities($fillUsername);
		$fillPassword = htmlentities($fillPassword);
		$fillEmail = htmlentities($fillEmail);

		?>
<h1>Forum Registration</h1>
<br>
<br>
<br>
<form method="POST">
<table class="loginTable">
	<tr>
		<td>
			Username:
		</td>
		<td class="loginTable">
				<input type="text" class="registerUsername" minLength="2" maxLength="20" name="username" tabIndex="1" required pattern="(^[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\x27\x22,./<>?~][A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\x27\x22,./<>?~ ]{0,18}[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\x27\x22,./<>?~]$)" value="<?php print($fillUsername); ?>" />
				<p class="usernameRequirements requirements">
					Usernames can be 2 to 20 characters long. Usernames can use most normal ASCII characters, but cannot start or end with spaces.
				</p>
		</td>
	</tr>
	<tr>
		<td>Password:</td>
		<td class="loginTable">
			<input type="password" class="registerPassword" minLength="<?php print($min_password_length); ?>" maxLength="72" name="password" tabIndex="2" autocomplete="new-password" required pattern="(^[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\x27\x22,./<>?~][A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\x27\x22,./<>?~ ]{0,70}[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\x27\x22,./<>?~]$)" value="<?php print($fillPassword); ?>"/>
			<p class="passwordRequirements requirements">
				Passwords must be at least <?php print($min_password_length); ?> characters and no more than 72 characters long. Only letters, numbers, and most common ASCII special characters are allowed. Your password cannot begin or end with spaces.
			</p>
		</td>
	</tr>
	<tr>
		<td>Confirm:</td>
		<td class="loginTable">
			<input type="password" class="registerConfirmPassword" name="confirmpassword" tabIndex="3" required />
			<p class="passwordConfirm requirements">
				Re-enter your chosen password to confirm that you know it well enough to log in again with it.
			</p>
		</td>
	</tr>
	<tr>
		<td>Email:</td>
		<td class="loginTable">
			<input class="registerEmail" type="email" name="email" tabIndex="4" required value="<?php print($fillEmail); ?>" />
			<p class="emailRequirements requirements">
				You must enter a valid email address which can be used for password recovery. 
				<?php if($settings['require_email_verification']) { ?>
				You will have to confirm this email before you can log in.
				<?php } else { ?>
				Email confirmation is not required.
				<?php } ?>
			</p>
		</td>
	</tr>
	<tr>
		<td class="loginTable">
			<input style="margin: 0px; height: 100%; width: 100%;" type="submit" value="Register" tabIndex="5" />
		</td>
		<td class="loginTable"></td>
	</tr>
</table>
</form>
		<?php
	}

	if(isSet($_POST['username']) && isSet($_POST['password']) && isSet($_POST['email']))
	{
		// Verify username is OK
		$username = $_POST['username'];

		// Matches a string between 2-20 characters with only alphanumeric characters, spaces, or most ascii special characters. Spaces are not allowed at the beginning or end.
		// This same expression is used in the form html to let the client self-validate.
		if(!preg_match('(^[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~][A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~ ]{0,18}[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~]$)', $username))
		{
			error('Username is not valid. Usernames can contain alphanumeric characters, spaces, and common special characters. Unicode characters are not allowed and the username cannot begin or end with a space.');
			showRegisterForm("", $_POST['password'], $_POST['email']);
			return;
		}
		$username = htmlentities(html_entity_decode($username));

		// Verify email is OK
		if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
		{
			error('Email address is invalid.');
			showRegisterForm($_POST['username'], $_POST['password'], "");
			return;
		}

		if(checkUserExists($username, $_POST['email']) !== false)
		{
			error('Username is taken or an account already exists under this email address.');
			showRegisterForm($_POST['username'], $_POST['password'], $_POST['email']);
			return;
		}

		// Verify password requirements
		// Matches a string between 2-72 characters with only alphanumeric characters, spaces, or most ascii special characters. Spaces are not allowed at the beginning or end of the string (typo protection since it's unlikely a user would want that intentionally).
		// This same expression is used in the form html to let the client self-validate.
		if(!preg_match('(^[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~][A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~ ]{0,70}[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~]$)', $_POST['password']))
		{
			error('Password is not valid. Passwords can contain alphanumeric characters, spaces, and common special characters. Unicode characters are not allowed and the password cannot begin or end with a space.');
			showRegisterForm($_POST['username'], "", $_POST['email']);
			return;
		}

		// Verify password matches the confirmation field
		if($_POST['password'] !== $_POST['confirmpassword'])
		{
			error('Passwords do not match.');
			showRegisterForm($_POST['username'], $_POST['password'], $_POST['email']);
			return;
		}

		if(strlen($_POST['password']) < $min_password_length)
		{
			error('Error: Password is too short. Use at least ' . $min_password_length . ' characters.');
			showRegisterForm($_POST['username'], $_POST['password'], $_POST['email']);
			return;
		}
		else if(strlen($_POST['password']) > 72)
		{
			error('Error: Password is too long! 72 characters is the maximum safely supported by password_bcrypt.');
			showRegisterForm($_POST['username'], $_POST['password'], $_POST['email']);
			return;
		}
		else if(stripos($_POST['password'], "password") !== false && strlen($_POST['password']) < 16)
		{
			error('Use a better password.');
			showRegisterForm($_POST['username'], "", $_POST['email']);
			return;
		}

		$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

		// Check rate limiting status
		if(!checkRateLimitAction("register", 300, 2))
		{
			error("You may only attempt two registrations per five minutes. Please try again later.");
			showRegisterForm($_POST['username'], "", $_POST['email']);
			return;
		}
		

		if($settings['require_email_verification'])
		{
			$verification = bin2hex(openssl_random_pseudo_bytes(32));
			$domain = $_SERVER['SERVER_NAME']; // Just hope their webserver is configured correctly...

			if(!isSet($_SERVER['REQUEST_URI']))
				$uri = "/";
			else
			{
				$uri = $_SERVER['REQUEST_URI'];

				$uri = substr($uri, 0, strrpos($uri, '/') + 1);
				if(strlen($uri) == 0)
					$uri = "/";
			}

			$url = ($force_ssl ? "https://" : "http://") . $domain . $uri . "index.php?action=verify&code=" . $verification;

			$message = <<<EOF
Thank you for registering on $site_name! Your username is ${username}. Please verify your email by visiting the following url:<br />
<br />
<a target="_BLANK" href="${url}">${url}</a><br />
<br />
If you did not intend to sign up for this forum, you may safely disregard this email.<br />
EOF;

			$error = mail($_POST['email'], "$site_name email verification", $message, "MIME-Version: 1.0\r\nContent-type: text/html; charset=iso-utf-8\r\nFrom: donotreply@${domain}\r\nX-Mailer: PHP/" . phpversion());
			if($error === false)
			{
				error("Failed to send verification email. Please try again later.");
				addLogMessage("Failed to send new user verification email.", 'error');
				finishPage();
			}
		}
		else
			$verification = 0;

		$regDate = time();
		$realUsername = $username;
		$username = sanitizeSQL($username);
		$password = sanitizeSQL($password);
		$email = sanitizeSQL($_POST['email']);

		$sql = "INSERT INTO users (username, passkey, reg_date, email, profiletext, profiletextPreparsed, verification, usergroup) VALUES ('${username}', '${password}', ${regDate}, '${email}', 'New user', 'New user', '${verification}', '" . (boolval($settings['require_email_verification']) ? "unverified" : "member") . "');";

		querySQL($sql);
		info("Registration completed successfully. Your username is ${realUsername}.<br><a href=\"./?action=login\">Log in</a>", "Register");
		addLogMessage("User account created.", 'info');

		disconnectSQL();
	}
	else
		showRegisterForm("", "", "");

?>
