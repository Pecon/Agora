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
		print("Username:</td><td><input type=text name=username></td>
					</tr>
					<tr>
					<td>Password:</td><td><input type=password name=password title=\"Use a minimum of 8 characters. I highly recommend using over 12 for better security.\"></td>
					</tr>
					<tr>
					<td>Confirm:</td><td><input type=password name=confirmpassword></td>
					</tr><tr>
					<td><input type=hidden name=registering value=true>
					<input type=submit value=Register>");
	}
	else if(isSet($_POST['username']) && isSet($_POST['password']) && isSet($_POST['ingamename']))
	{
		if($_POST['password'] !== $_POST['confirmpassword'])
		{
			error("Passwords do not match. <br><button onclick=\"goBack()\">Try again</button>");
			exit();
		}
		
		if(strlen($_POST['password']) < 8)
		{
			error("Error: Password is too short. Use at least 8 characters. This is the only requirement aside from your password not being 'password'. <br><button onclick=\"goBack()\">Try again</button>");
			return;
		}
		else if(stripos($_POST['password'], "password") !== false && strlen($_POST['password']) < 12)
		{
			error("You've got to be kidding me. <br><button onclick=\"goBack()\">Try again</button>");
			return;
		}
		
		$username = normalize_special_characters(strip_tags($_POST['username']));
		$password = password_hash(normalize_special_characters($_POST['password']), PASSWORD_BCRYPT);
		$ingamename = $_POST['ingamename'];
		
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
		
		//global $servername, $dbusername, $dbpassword, $dbname;
		
		//Insert query
		$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if($mysqli -> connect_error) 
			exit(error("Connection failed: " . $mysqli -> connect_error, true));
		
		$realUsername = $username;
		$username = mysqli_real_escape_string($mysqli, $username);
		$password = mysqli_real_escape_string($mysqli, $password);
		$ingamename = mysqli_real_escape_string($mysqli, trim(strip_tags($ingamename)));

		$sql = "INSERT INTO users (username, passkey) VALUES ('{$username}', '{$password}'')";

		if ($mysqli->query($sql) === TRUE) 
		{
			print("Registration completed successfully. Your username is {$realUsername}. <a href=\"./login.php\">Log in</a>");
		} 
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