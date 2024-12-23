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

	header("Content-Type: text/plain");
	print($user['username'] . "\n" . html_entity_decode($post['postData'], ENT_SUBSTITUTE | ENT_QUOTES, 'UTF-8'));
?>