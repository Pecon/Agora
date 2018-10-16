<!DOCTYPE html>
<head>
	<title>
			<?php global $_title; print($_title); ?>
	</title>
	<link rel="stylesheet" type="text/css" href="./themes/twilight/main.css"/>
	<link async href="https://fonts.googleapis.com/css?family=Montserrat:400,400i,700,700i&amp;subset=latin-ext,cyrillic,cyrillic-ext,vietnamese" rel="stylesheet">
	<link rel="icon" type="image/png" href="./themes/twilight/images/favicon.png"/>

	<meta name="viewport" content="device-width">

	<meta name="description" content="<?php global $_description; print($_description); ?>">
	<meta name="keywords" content="<?php global $_tags; print(implode(",", $_tags)); ?>">

	<script src="./js/main.js"></script>

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