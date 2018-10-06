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
ini_set("session.gc_maxlifetime", 18000);
ini_set("session.cookie_lifetime", 18000);
ob_start();
session_start();

$_title = "";
$_description = "";
$_tags = Array();
$_headText = "";
$_addToBody = "";
$_navBarEnabled = true;

function loadThemePart($part)
{
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
	if(is_file("./themes/$site_theme/$part.php"))
		require_once "./themes/$site_theme/$part.php";
	else
		error("Theme part '$part' could not be found in the theme directory.");
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

	directLoadThemePart("head");

	if($_navBarEnabled)
		directLoadThemePart("menu");

	print($_bodyText);

	global $_version, $_time, $_queries, $_year;
	$_version = "2.0.0b";
	$_time = round((microtime(true) - $_script_start) * 1000, 3);
	$_queries = $_mysqli_numQueries . " " . ($_mysqli_numQueries == 1 ? "query" : "queries");
	$_year = date('Y');
	
	directLoadThemePart("foot");

	exit();
}

?>