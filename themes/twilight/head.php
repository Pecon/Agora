<!DOCTYPE html>
<head>
	<title>
			<?php print($_title); ?>
	</title>
	<link rel="stylesheet" type="text/css" href="./themes/twilight/main.css"/>
	<link async href="https://fonts.googleapis.com/css?family=Montserrat:400,400i,700,700i&amp;subset=latin-ext,cyrillic,cyrillic-ext,vietnamese" rel="stylesheet">
	<link rel="icon" type="image/png" href="./themes/twilight/images/favicon.png"/>

	<meta HTTP-EQUIV="Pragma" content="no-cache"/>
	<meta HTTP-EQUIV="Expires" content="-1"/>
	<meta name="viewport" content="width=1025">

	<meta name="description" content="<?php print($_description); ?>">
	<meta name="keywords" content="<?php print($keywords); ?>">

	<noscript>
		<style>
			.javascriptButton
			{
				display: none;
			}
		</style>
	</noscript>

	<script type="text/javascript">
	function goBack()
	{
	    window.history.back();
	}
	</script>

	<?php print($_headText); ?>
</head>
<body>
<center>