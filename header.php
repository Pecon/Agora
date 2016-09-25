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

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/html1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">

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