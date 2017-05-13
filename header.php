<?php
if(isSet($_GET['finishForm']))
{
	if($_GET['finishForm'] == 1)
	{
		if(isSet($_GET['newAction']))
			$pathinfo = "action=" . $_GET['newAction'];
		else
		{
			$pathinfo = $_SERVER['QUERY_STRING'];
			$pathinfo = subStr($pathinfo, 0, -13);
		}

		header('Location: ./?' . $pathinfo);
	}
}
?>

<!DOCTYPE html>
<head>
	<title>
		<?php
			if(isSet($pageTitle))
				print($pageTitle);
			else
				print("REforum");
		?>
	</title>
	<link rel="stylesheet" type="text/css" href="./style/default.css"/>
	<link rel="icon" type="image/png" href="./style/favicon.png"/>

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

	<?php
		if(isSet($metaTags))
			print($metaTags);
	?>
	<!--<script language="javascript"> // Add setting to enable this.
	if(window.location.protocol != "https:")
		window.location.href = "https:" + window.location.href.substring(window.location.protocol.length);
	</script>-->

</head>
<body>
