<?php
	global $site_name;
	setPageTitle("$site_name - Register");

	?>
		<h1>Forum Registration</h1>
		<br>
		<form method="POST">
		<table class="loginTable">
			<tr>
				<td>
	<?php

	if(!isSet($_POST['registering']))
	{
		?>
					Username:
					</td>
					<td class="loginTable">
						<input type="text" minLength="2" maxLength="20" name="username" tabIndex="1" class="" required pattern="(^[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\x27\x22,./<>?~][A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\x27\x22,./<>?~ ]{0,18}[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\x27\x22,./<>?~]$)" />
					</td>
					</tr>
					<tr>
					<td>Password:</td><td class="loginTable"><input type="password" class="" minLength="<?php print($min_password_length); ?>" maxLength="72" name="password" tabIndex="2" autocomplete="new-password" required pattern="(^[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\x27\x22,./<>?~][A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\x27\x22,./<>?~ ]{0,70}[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\x27\x22,./<>?~]$)" /></td>
					</tr>
					<tr>
					<td>Confirm:</td><td class="loginTable"><input type="password" name="confirmpassword" tabIndex="3" required /></td>
					</tr>
					<tr>
					<td>Email:</td><td class="loginTable"><input class="" type="email" name="email" tabIndex="4" required /></td>
					</tr><tr>
					<td class="loginTable"><input type="hidden" name="registering" value="true" />
					<input style="margin: 0px; height: 100%; width: 100%;" type="submit" value="Register" tabIndex="5" />
					</td>
					<td class="loginTable"></td>
			</tr>
		</table>
		</form>
		<?php
	}
	else if(isSet($_POST['username']) && isSet($_POST['password']) && isSet($_POST['email']))
	{
		// Verify username is OK
		$username = $_POST['username'];

		// Matches a string between 2-20 characters with only alphanumeric characters, spaces, or most ascii special characters. Spaces are not allowed at the beginning or end.
		// This same expression is used in the form html to let the client self-validate.
		if(!preg_match('(^[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~][A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~ ]{0,18}[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~]$)', $username))
		{
			print(error('Username is not valid. Usernames can contain alphanumeric characters, spaces, and common special characters. Unicode characters are not allowed and the username cannot begin or end with a space.  <br /><button onclick="goBack()">Try again</button>', true));
			print("</tr></td></table></form>");
			return;
		}
		$username = htmlentities(html_entity_decode($username));

		// Verify email is OK
		if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
		{
			print(error('Email address is invalid. <br /><button onclick="goBack()">Try again</button>', true));
			print("</tr></td></table></form>");
			return;
		}

		if(checkUserExists($username, $_POST['email']) !== false)
		{
			print(error('Username is taken or an account already exists under this email address. <br /><button onclick="goBack()">Try again</button>', true));
			print("</tr></td></table></form>");
			return;
		}

		// Verify password requirements
		// Matches a string between 2-72 characters with only alphanumeric characters, spaces, or most ascii special characters. Spaces are not allowed at the beginning or end of the string (typo protection since it's unlikely a user would want that intentionally).
		// This same expression is used in the form html to let the client self-validate.
		if(!preg_match('(^[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~][A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~ ]{0,70}[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~]$)', $_POST['password']))
		{
			print(error('Password is not valid. Passwords can contain alphanumeric characters, spaces, and common special characters. Unicode characters are not allowed and the password cannot begin or end with a space.  <br /><button onclick="goBack()">Try again</button>', true));
			print("</tr></td></table></form>");
			return;
		}

		// Verify password matches the confirmation field
		if($_POST['password'] !== $_POST['confirmpassword'])
		{
			print(error('Passwords do not match. <br /><button onclick="goBack()">Try again</button>', false));
			print("</tr></td></table></form>");
			return;
		}

		if(strlen($_POST['password']) < $min_password_length)
		{
			print(error('Error: Password is too short. Use at least ' . $min_password_length . ' characters. <br /><button onclick="goBack()">Try again</button>', true));
			print("</tr></td></table></form>");
			return;
		}
		else if(strlen($_POST['password']) > 72)
		{
			print(error('Error: Password is too long! 72 characters is the maximum safely supported by password_bcrypt. <br /><button onclick="goBack()">Try again</button>', true));
			print("</tr></td></table></form>");
			return;
		}
		else if(stripos($_POST['password'], "password") !== false && strlen($_POST['password']) < 16)
		{
			print(error('You\'ve got to be kidding me. <br /><button onclick="goBack()">Try again</button>', true));
			print("</tr></td></table></form>");
			return;
		}

		$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
		

		if($settings['require_email_verification'])
		{
			$verification = bin2hex(openssl_random_pseudo_bytes(32));
			$domain = $_SERVER['SERVER_NAME']; // Just hope their webserver is configured correctly...

			$uri =  $_SERVER['REQUEST_URI'];
			$uripos = strrchr($uri, '/');
			if($uripos === false)
				$uri = "/";
			else
				$uri = substr($uri, 0, $uripos + 1);

			$url = ($force_ssl ? "https://" : "http://") . $domain . $uri . "index.php?action=verify&code=" . $verification;

			$message = <<<EOF
Thank you for registering on $site_name! Please verify your email by visiting the following url:<br />
<br />
<a target="_BLANK" href="${url}">${url}</a><br />
<br />
If you did not intend to sign up for this forum, you may safely disregard this email.<br />
EOF;

			$error = mail($_POST['email'], "$site_name email verification", $message, "MIME-Version: 1.0\r\nContent-type: text/html; charset=iso-utf-8\r\nFrom: donotreply@${domain}\r\nX-Mailer: PHP/" . phpversion());
			if($error === false)
			{
				finishPage(error("Failed to send verification email. Please try again later.", true));
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
		print("Registration completed successfully. Your username is ${realUsername}.<br><a href=\"./login.php\">Log in</a>");
		print("</tr></td></table></form>");

		disconnectSQL();
	}

?>
