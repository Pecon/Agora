<?php
	require_once './functions.php';
	
	if(isSet($_GET['user']))
	{
		$avatar = getAvatarByID($_GET['user']);
		
		if($avatar === false)
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
		
		if(strstr(substr($avatar, 0, 6), "PNG") !== false)
			$mime = "image/png";
		else if(strstr(substr($avatar, 0, 6), "GIF") !== false)
			$mime = "image/gif";
		else
			$mime = "application/octet-stream";
		
		header("Content-type: ${mime}");
		header("Cache-control: max-age=10080");
		
		print($avatar);
	}
?>