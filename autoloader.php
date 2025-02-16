<?php

// Agora SPL class autoloader
spl_autoload_register(function($className)
{
	$classPath = "./classes/" . $className . ".php";

	if(!is_file($classPath))
	{
		throw new Exception("Cannot load class '$className': '$classPath' file not found.");
	}

	require $classPath;
});