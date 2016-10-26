<?php
	session_start();
	$pageTitle = "REforum - Register";
	$metaTags = "<meta HTTP-EQUIV=\"Pragma\" content=\"no-cache\">
				<meta HTTP-EQUIV=\"Expires\" content=\"-1\">";
	require_once './header.php';
	print("<center>");
	require_once './navmenu.php';
	?>
	
<script>
function goBack()
{
    window.history.back();
}
</script>
		<br>
		<h1>Forum Registration</h1>
		<br>
		<form method=POST>
		<table border=1>
			<tr>
				<td>
<?php
	require_once './functions.php';
	
	
	if(!isSet($_POST['registering']))
	{
		print("Username:</td><td><input type=text maxLength=20 name=username></td>
					</tr>
					<tr>
					<td>Password:</td><td><input type=password class=validate minLength=${min_password_length} maxLength=72 name=password></td>
					</tr>
					<tr>
					<td>Confirm:</td><td><input type=password name=confirmpassword></td>
					</tr>
					<tr>
					<td>Email:</td><td><input class=validate type=email name=email></td>
					</tr><tr>
					<td><input type=hidden name=registering value=true>
					<input type=submit value=Register>");
	}
	else if(isSet($_POST['username']) && isSet($_POST['password']) && isSet($_POST['email']))
	{
		// Verify username is OK
		$username = filter_var($_POST['username'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW);
		$username = trim(normalize_special_characters(strip_tags(trim($username))));
		if(strLen($username) > 20)
		{
			error("Username is too long. Pick something under 20 characters. <br><button onclick=\"goBack()\">Try again</button>");
			exit();
		}
			
		if($result = checkUserExists($username) !== false)
		{
			error("Username is taken or you have already created an account under this ID. <br><button onclick=\"goBack()\">Try again</button>");
			exit();
		}
		
		// Verify email is OK
		if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
			exit(error("Email address is invalid. <br><button onclick=\"goBack()\">Try again</button>", true));
		
		// Verify password is OK
		if($_POST['password'] !== $_POST['confirmpassword'])
		{
			error("Passwords do not match. <br><button onclick=\"goBack()\">Try again</button>");
			exit();
		}
		
		if(strlen($_POST['password']) < $min_password_length)
		{
			error("Error: Password is too short. Use at least ${min_password_length} characters. This is the only requirement aside from your password not being 'password'. <br><button onclick=\"goBack()\">Try again</button>");
			exit();
		}
		else if(stripos($_POST['password'], "password") !== false && strlen($_POST['password']) < 16)
		{
			error("You've got to be kidding me. <br><button onclick=\"goBack()\">Try again</button>");
			exit();
		}
		
		
		$password = password_hash(normalize_special_characters($_POST['password']), PASSWORD_BCRYPT);
		
		$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if($mysqli -> connect_error) 
			exit(error("Connection failed: " . $mysqli -> connect_error, true));
		
		$realUsername = $username;
		$username = mysqli_real_escape_string($mysqli, $username);
		$password = mysqli_real_escape_string($mysqli, $password);
		$email = mysqli_real_escape_string($mysqli, $_POST['email']);
		$regDate = time();

		$sql = "INSERT INTO users (username, passkey, reg_date, email, profiletext, profiletextPreparsed) VALUES ('${username}', '${password}', ${regDate}, '${email}', 'New user', 'New user')";

		if ($mysqli -> query($sql) === TRUE) 
			print("Registration completed successfully. Your username is {$realUsername}.<br><a href=\"./login.php\">Log in</a>");
		else 
			exit(error($mysqli -> error, true));

		$mysqli -> close();
	}
?>
				</td>
			</tr>
		</table>
		</form>
	</center>
</body>
</html>