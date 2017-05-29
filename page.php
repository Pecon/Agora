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
		exit();
	}
}

if(isSet($force_ssl))
	if($force_ssl)
	{
		if(!isSet($_SERVER['HTTPS']))
		{
			$domain = $_SERVER['SERVER_NAME']; // Just hope their webserver is configured correctly...

			$uri =  $_SERVER['REQUEST_URI'];
			$uripos = strrchr($uri, '/');
			if($uripos === false)
				$uri = "/";
			else
				$uri = substr($uri, 0, $uripos + 1);

			$url = ($force_ssl ? "https://" : "http://") . $domain . $uri;
			header('Location: ' . $url);
		}
		else if(strlen($_SERVER['HTTPS']) < 1 || $_SERVER['HTTPS'] == "off")
		{
			$domain = $_SERVER['SERVER_NAME']; // Just hope their webserver is configured correctly...

			$uri =  $_SERVER['REQUEST_URI'];
			$uripos = strrchr($uri, '/');
			if($uripos === false)
				$uri = "/";
			else
				$uri = substr($uri, 0, $uripos + 1);

			$url = ($force_ssl ? "https://" : "http://") . $domain . $uri;
			header('Location: ' . $url);
		}
	}

$_script_start = microtime(true);
error_reporting(E_ALL);
ini_set("log_errors", true);
ini_set("error_log", "./php-error.log");
ob_start();
session_start();

$_title = "";
$_description = "";
$_tags = Array();
$_headText = "";
$_addToBody = "";
$_navBarEnabled = true;


function setPageTitle($title)
{
	global $_title;
	$_title = htmlentities(html_entity_decode(trim($title)), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8");
}

function setPageDescription($description)
{
	global $_description;
	$_description = htmlentities(html_entity_decode(trim($description)), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8");
}

function addPageTag($tag)
{
	global $_tags;
	array_push($_tags, htmlentities(html_entity_decode(trim($tag)), ENT_SUBSTITUTE | ENT_QUOTES, "UTF-8"));
}

function addToHead($tag)
{
	global $_headText;
	$_headText = $_headText . "\n$tag";
}

function addToBody($text)
{
	global $_bodyText;
	$_bodyText = $_bodyText . "\n$text";
}

function setNavBarEnabled($bool)
{
	global $_navBarEnabled;
	$_navBarEnabled = $bool;
}

function error()
{
	$numArgs = func_num_args();

	if($numArgs < 1)
		return;

	$text = func_get_arg(0);

	if($numArgs > 1)
		if(func_get_arg(1))
			return "<div class=errorText>" . $text . "</div>";

	addToBody("<div class=\"errorText\">" . $text . "</div>");
}

function warn()
{
	$numArgs = func_num_args();

	if($numArgs < 1)
		return;

	$text = func_get_arg(0);

	if($numArgs > 1)
		if(func_get_arg(1))
			return "<div class=warningText>" . $text . "</div>";

	addToBody("<div class=\"warningText\">" . $text . "</div>");
}

function finishPage()
{
	$numArgs = func_num_args();

	if($numArgs > 1)
		addToBody(func_get_arg(0));

	global $_headText, $_bodyText, $_title, $_tags, $_description, $_script_start, $_mysqli_numQueries, $_navBarEnabled;

	$_mysqli_numQueries = intval($_mysqli_numQueries);

	$header = <<<EOT
<!DOCTYPE html>
<head>
	<title>
			$_title
	</title>
	<link rel="stylesheet" type="text/css" href="./style/default.css"/>
	<link rel="icon" type="image/png" href="./style/favicon.png"/>

	<meta HTTP-EQUIV="Pragma" content="no-cache"/>
	<meta HTTP-EQUIV="Expires" content="-1"/>

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

	$_headText
</head>
<body>
<center>
EOT;
	print($header);

	if($_navBarEnabled)
		require_once 'navmenu.php';

	print($_bodyText);

	$time = round((microtime(true) - $_script_start) * 1000, 3);
	$queries = $_mysqli_numQueries . " " . ($_mysqli_numQueries == 1 ? "query" : "queries");
	$year = 2017;
	$footer = <<<EOT
<br />
<br />
<div class="finetext">
Powered by REforum &#169; $year pecon.us <a href="./about.html">About</a>
<br>
Page created in $time milliseconds with $queries.
</div>
</center>
</body>
</html>
EOT;
	print($footer);
	exit();
}

?>