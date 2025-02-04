<?php

function fatalError($error)
{
	global $_navBarEnabled;
	$_navBarEnabled = false;

	addToBody("<div class=\"fatalErrorBox\">\n<h1>FATAL ERROR</h1><br><br>" . $error . "</div>");
	finishPage();
}
?>
