<?php
	require_once 'page.php';
	require_once './database.php';
	require_once './functions.php';

	setPageTitle("$site_name - Register");

	$head = <<<EOT
		<h1>Forum Registration</h1>
		<br>
		<form method="POST">
		<table class="loginTable">
			<tr>
				<td>
EOT;
	addToBody($head);

	if(!isSet($_POST['registering']))
	{
		$form = <<<EOT
					Username:
				</td>
				<td>
					<input type="text" maxLength="20" name="username">
				</td>
			</tr>
			<tr>
			<td>Password:</td><td><input type="password" class="validate" minLength="${min_password_length}" maxLength="72" name="password"></td>
			</tr>
			<tr>
			<td>Confirm:</td><td><input type="password" name="confirmpassword"></td>
			</tr>
			<tr>
			<td>Email:</td><td><input class="validate" type="email" name="email"></td>
			</tr><tr>
			<td><input type="hidden" name="registering" value="true">
			<input type="submit" value="Register">
EOT;
		addToBody($form);
	}
	else if(isSet($_POST['username']) && isSet($_POST['password']) && isSet($_POST['email']))
	{
		// Verify username is OK
		$username = filter_var($_POST['username'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW);
		$username = trim(normalize_special_characters(strip_tags(trim($username))));
		if(strLen($username) > 20)
		{
			error("Username is too long. Pick something under 20 characters. <br><button onclick=\"goBack()\">Try again</button>");
			finishPage();
		}
		else if(strLen($username) < 1)
		{
			error("Username cannot be blank. <br><button onclick=\"goBack()\">Try again</button>");
			finishPage();
		}

		// Verify email is OK
		if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
			finishPage(error("Email address is invalid. <br><button onclick=\"goBack()\">Try again</button>", true));


		if($result = checkUserExists($username, $_POST['email']) !== false)
		{
			error("Username is taken or you have already created an account under this email address. <br><button onclick=\"goBack()\">Try again</button>");
			finishPage();
		}

		// Verify password is OK
		if($_POST['password'] !== $_POST['confirmpassword'])
		{
			error("Passwords do not match. <br><button onclick=\"goBack()\">Try again</button>");
			finishPage();
		}

		if(strlen($_POST['password']) < $min_password_length)
		{
			error("Error: Password is too short. Use at least ${min_password_length} characters. This is the only requirement aside from your password not being 'password'. <br><button onclick=\"goBack()\">Try again</button>");
			finishPage();
		}
		else if(stripos($_POST['password'], "password") !== false && strlen($_POST['password']) < 16)
		{
			error("You've got to be kidding me. <br><button onclick=\"goBack()\">Try again</button>");
			finishPage();
		}


		$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
		$regDate = time();

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

			$sql = "INSERT INTO users (username, passkey, reg_date, email, profiletext, profiletextPreparsed, verification, verified) VALUES ('${username}', '${password}', ${regDate}, '${_POST['email']}', 'New user', 'New user', '${verification}', 0);";
		}
		else
			$sql = "INSERT INTO users (username, passkey, reg_date, email, profiletext, profiletextPreparsed, verification, verified) VALUES ('${username}', '${password}', ${regDate}, '${email}', 'New user', 'New user', 0, 1);";

		$realUsername = $username;
		$username = sanitizeSQL($username);
		$password = sanitizeSQL($password);
		$email = sanitizeSQL($mysqli, $_POST['email']);
		$regDate = time();

		querySQL($sql);
		addToBody("Registration completed successfully. Your username is ${realUsername}.<br><a href=\"./login.php\">Log in</a>");

		disconnectSQL();
	}

	finishPage();
?>