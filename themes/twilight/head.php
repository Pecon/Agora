<!DOCTYPE html>
<html>
<head>
	<title>
			<?php global $_title; print($_title); ?>
	</title>
	<link rel="stylesheet" type="text/css" href="./themes/twilight/main.css"/>
	<link rel="icon" type="image/png" href="./themes/twilight/images/favicon.png"/>

	<meta name="viewport" content="width=device-width, initial-scale=1">

	<meta name="description" content="<?php global $_description; print($_description); ?>">
	<meta name="keywords" content="<?php global $_tags; print(implode(",", $_tags)); ?>">

	<noscript>
		<style>
			.javascriptButton
			{
				display: none;
			}
		</style>
	</noscript>

	<?php global $_headText; print($_headText); ?>
</head>
<body>
<center>
<div class="contentContainer">