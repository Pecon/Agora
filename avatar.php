<?php
	require_once './functions.php';
	$userID = $_GET['user'] ?? null;

	if(!$userID)
	{
		http_response_code(400);
		exit();
	}
	$user = getUserByID($_GET['user']);

	if(!$user)
	{
		http_response_code(404);
		exit();
	}

	$avatar = $user['avatar'];
	
	if(!$avatar)
	{
		$defaultAvatar = "./themes/$site_theme/images/defaultavatar.png";

		if(!is_file($defaultAvatar))
		{
			exit();
		}
		
		header("Cache-control: max-age=10080");
		header("Content-type: " . mime_content_type($defaultAvatar));
		readfile($defaultAvatar);
	}
	else
	{
		if(strstr(substr($avatar, 0, 6), "PNG") !== false)
			$mime = "image/png";
		else if(strstr(substr($avatar, 0, 6), "GIF") !== false)
			$mime = "image/gif";
		else
			$mime = "application/octet-stream";
		
		header("Content-Type: {$mime}");
		header("Cache-control: max-age=10080");
		
		print($avatar);
	}