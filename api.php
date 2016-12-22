<?php
	require_once './functions.php';
	require_once './data.php';
	
	$key = "6513541354"; 
		
	if(!$_GET['key'] == $key)
	{
		print("BAD KEY\r\n");
		return;
	}
	
	else if(!isSet($_GET['BLID']))
	{
		print("BAD ID\r\n");
		return;
	}
	
	$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
	$sql = "SELECT * FROM users WHERE BLID={$_GET['BLID']}";
	
	$result = $mysqli -> query($sql);
	
	if($result == false)
	{
		print("Error: Can't verify account");
		return;
	}
	
	else if($result -> num_rows == 0)
	{
		print("NO ACCOUNT");
		return;
	}
	
	if(isSet($_GET['reset']))
	{
		if(strlen($_GET['reset']) < 5)
		{
			print("Error: New passwords must be at least 5 characters.");
		}
		else
		{
			$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
			$newPassword = password_hash(normalize_special_characters($_GET['reset']), PASSWORD_BCRYPT);
			
			$sql = "UPDATE users SET passkey='{$newPassword}' WHERE BLID={$_GET['BLID']}";
			
			$result = $mysqli->query($sql);
		
			if($result == false)
			{
				print("Error: Can't update password.");
			}
		}
	}
	
	if(isSet($_GET['ingamename']))
	{
		$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		$input = mysqli_real_escape_string($mysqli, htmlentities(strip_tags($_GET['ingamename'])));
		
		$sql = "UPDATE users SET current_ingamename='{$input}' WHERE BLID={$_GET['BLID']}";
		
		$result = $mysqli->query($sql);
		
		if($result == false)
		{
			print("Error: Can't update ingamename");
		}
	}
	
	if(isSet($_GET['title']))
	{
		if(!isSet($_GET['color']))
			$color = "FF1111";
		else
			$color = $_GET['color'];
		
		
		$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		$input = mysqli_real_escape_string($mysqli, $_GET['title']);
		$titlecolor = mysqli_real_escape_string($mysqli, $color);
		
		$sql = "UPDATE users SET title='{$input}',titlecolor='{$titlecolor}' WHERE BLID={$_GET['BLID']}";
		
		$result = $mysqli->query($sql);
		
		if($result == false)
		{
			print("Error: Can't update title");
		}
	}
	
	if(isSet($_GET['admin']))
	{
		$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		$input = mysqli_real_escape_string($mysqli, htmlentities(strip_tags($_GET['admin'])));
		
		$sql = "UPDATE users SET administrator='{$input}' WHERE BLID={$_GET['BLID']}";
		
		$result = $mysqli->query($sql);
		
		if($result == false)
		{
			print("Error: Can't update administrator");
		}
	}
	
	if(isSet($_GET['score']))
	{
		$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		$input = mysqli_real_escape_string($mysqli, htmlentities(strip_tags($_GET['score'])));
		
		$sql = "UPDATE users SET score='{$input}' WHERE BLID={$_GET['BLID']}";
		
		$result = $mysqli->query($sql);
		
		if($result == false)
		{
			print("Error: Can't update score");
		}
	}
	
	if(isSet($_GET['bosscoins']))
	{
		$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		$input = mysqli_real_escape_string($mysqli, htmlentities(strip_tags($_GET['bosscoins'])));
		
		$sql = "UPDATE users SET bosscoins='{$input}' WHERE BLID={$_GET['BLID']}";
		
		$result = $mysqli->query($sql);
		
		if($result == false)
		{
			print("Error: Can't update score");
		}
	}
	
	if(isSet($_GET['numachieves']))
	{
		$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		$input = mysqli_real_escape_string($mysqli, htmlentities(strip_tags($_GET['numachieves'])));
		
		$sql = "UPDATE users SET numAchievements='{$input}' WHERE BLID={$_GET['BLID']}";
		
		$result = $mysqli->query($sql);
		
		if($result == false)
		{
			print("Error: Can't update achievements");
		}
	}
	
	print("DONE\r\n");
?>