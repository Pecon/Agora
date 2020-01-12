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
			$uripos = strrpos($uri, '/');
			if($uripos === false)
				$uri = "/";

			$url = ($force_ssl ? "https://" : "http://") . $domain . $uri;
			header('Location: ' . $url);
			exit();
		}
		else if(strlen($_SERVER['HTTPS']) < 1 || $_SERVER['HTTPS'] == "off")
		{
			$domain = $_SERVER['SERVER_NAME']; // Just hope their webserver is configured correctly...

			$uri =  $_SERVER['REQUEST_URI'];
			$uripos = strrpos($uri, '/');
			if($uripos === false)
				$uri = "/";

			$url = ($force_ssl ? "https://" : "http://") . $domain . $uri;
			header('Location: ' . $url);
			exit();
		}
	}

global $_script_start, $_script_nonce;

if(!isSet($_script_start))
	$_script_start = microtime(true);

$_script_nonce = bin2hex(openssl_random_pseudo_bytes(12));

error_reporting(E_ALL);
ini_set("log_errors", true);
ini_set("error_log", "./php_error.log");
ini_set("session.gc_maxlifetime", 18000);
ini_set("session.cookie_lifetime", 18000);
header('cache-control: private');
header('expires: 0');
header("Content-Security-Policy: script-src 'nonce-${_script_nonce}';");
session_start();

$_title = "";
$_description = "";
$_tags = Array();
$_headText = "";
$_addToBody = "";
$_navBarEnabled = true;
$_errors = Array();
$_warns = Array();
$_infos = Array();

function loadThemePart($part)
{
	global $site_theme;

	if(is_file("./themes/$site_theme/$part.php"))
	{
		ob_start();
		require_once "./themes/$site_theme/$part.php";
		addToBody(ob_get_clean());
	}
	else
		error("Theme part '$part' could not be found in the theme directory.");
}

function directLoadThemePart($part)
{
	global $site_theme;

	if(is_file("./themes/$site_theme/$part.php"))
		require_once "./themes/$site_theme/$part.php";
	else
		error("Theme part '$part' could not be found in the theme directory.");
}

function getThemePart($part)
{
	global $site_theme;

	if(is_file("./themes/$site_theme/$part.php"))
	{
		ob_start();
		require_once "./themes/$site_theme/$part.php";
		return ob_get_clean();
	}
	else
		error("Theme part '$part' could not be found in the theme directory.");

	return false;
}

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

function info()
{
	$numArgs = func_num_args();

	if($numArgs < 2)
		return;

	$text = '<div class="infoBox infoBoxClass"><div class="infoBoxHeader infoBoxHeaderClass">' . func_get_arg(1) . '</div>' . func_get_arg(0) . '</div>';

	if($numArgs > 2)
		if(func_get_arg(2))
			return $text;

	global $_infos;
	array_push($_infos, $text);
}

function error()
{
	$numArgs = func_num_args();

	if($numArgs < 1)
		return;

	$text = '<div class="errorBox infoBoxClass"><div class="errorBoxHeader infoBoxHeaderClass"></div>' . func_get_arg(0) . '</div>';

	if($numArgs > 1)
		if(func_get_arg(1))
			return $text;

	global $_errors;
	array_push($_errors, $text);
}

function warn()
{
	$numArgs = func_num_args();

	if($numArgs < 1)
		return;

	$text = '<div class="warnBox infoBoxClass"><div class="warnBoxHeader infoBoxHeaderClass"></div>' . func_get_arg(0) . '</div>';

	if($numArgs > 1)
		if(func_get_arg(1))
			return $text;

	global $_warns;
	array_push($_warns, $text);
}

function finishPage()
{
	$numArgs = func_num_args();

	if($numArgs > 0)
		addToBody(func_get_arg(0));

	global $_bodyText, $_script_start, $_mysqli_numQueries, $_navBarEnabled, $site_timezone, $_errors, $_warns, $_infos;

	date_default_timezone_set($site_timezone);

	$_mysqli_numQueries = intval($_mysqli_numQueries);

	directLoadThemePart("head");

	if($_navBarEnabled)
		directLoadThemePart("menu");

	// Display infoboxes and errors between the menu and the page content
	foreach($_errors as $error)
		print($error . "\n");

	foreach($_warns as $warn)
		print($warn . "\n");

	foreach($_infos as $info)
		print($info . "\n");

	print($_bodyText);

	global $_version, $_time, $_queries, $_year;
	$_version = "2.0.0-beta3";
	$_time = round((microtime(true) - $_script_start) * 1000, 1);
	$_queries = $_mysqli_numQueries . " " . ($_mysqli_numQueries == 1 ? "query" : "queries");
	$_year = date('Y');
	
	directLoadThemePart("foot");

	exit();
}

?>