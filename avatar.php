<?php
	require_once './functions.php';
	
	if(isSet($_GET['user']))
	{
		$avatar = getAvatarByID($_GET['user']);
		
		if($avatar === false)
			exit();
		
		header("Content-type: image/png");
		header("Cache-control: max-age=1800");
		
		print($avatar);
	}
?>