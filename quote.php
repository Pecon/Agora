<?php
	require_once 'functions.php';

	if(!isSet($_GET['id']))
	{
		header('Status: 404 Not Found');
		exit();
	}

	$id = intval($_GET['id']);
	$post = fetchSinglePost($id);

	if($post === false)
	{
		print("Error\nCouldn't find post.");
		return;
	}

	$user = findUserByID($post['userID']);

	if($user === false)
	{
		print("Error\nCouldn't find user.");
		return;
	}

	print($user['username'] . "\n" . $post['postData']);
?>