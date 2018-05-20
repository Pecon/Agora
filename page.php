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
	$_title = strip_tags($title);
}

function setPageDescription($description)
{
	global $_description;
	$_description = strip_tags($description);
}

function addPageTag($tag)
{
	global $_tags;
	$tag = str_replace(",", ";", $tag);
	array_push($_tags, $tag);
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
			return '<div class="errorText">' . $text . "</div>";

	addToBody('<div class="errorText">' . $text . "</div>");
}

function warn()
{
	$numArgs = func_num_args();

	if($numArgs < 1)
		return;

	$text = func_get_arg(0);

	if($numArgs > 1)
		if(func_get_arg(1))
			return '<div class="warningText">' . $text . "</div>";

	addToBody('<div class="warningText">' . $text . "</div>");
}

function finishPage()
{
	$numArgs = func_num_args();

	if($numArgs > 0)
		addToBody(func_get_arg(0));

	global $_headText, $_bodyText, $_title, $_tags, $_description, $_script_start, $_mysqli_numQueries, $_navBarEnabled, $site_timezone;

	date_default_timezone_set($site_timezone);

	$_mysqli_numQueries = intval($_mysqli_numQueries);
	$keywords = implode(",", $_tags);

	$header = <<<EOT
<!DOCTYPE html>
<head>
	<title>
			$_title
	</title>
	<link rel="stylesheet" type="text/css" href="./style/default.css"/>
	<link async href="https://fonts.googleapis.com/css?family=Montserrat:400,400i,700,700i&amp;subset=latin-ext,cyrillic,cyrillic-ext,vietnamese" rel="stylesheet">
	<link rel="icon" type="image/png" href="./style/favicon.png"/>

	<meta HTTP-EQUIV="Pragma" content="no-cache"/>
	<meta HTTP-EQUIV="Expires" content="-1"/>
	<meta name="viewport" content="width=1100">

	<meta name="description" content="$_description">
	<meta name="keywords" content="$keywords">

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
	$year = date('Y');
	$footer = <<<EOT
<br />
<br />
<div class="finetext">
Powered by Agora &#169; $year pecon.us <a href="./about.html">About</a>
<br />
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