<?php

class HelpersNotFoundException extends Exception {}

/*
	Class:
		<Helpers>
	
	Keep track of helper files and create an easily managed interface for including helpers.
*/

class Helpers
{
	// Property: <Helpers::$path>
	// Holds the path to the helper files
	private static $path = 'helpers/';
	
	/*
		Method:
			<Helpers::load>
		
		Load one or more helper files. Only specify the middle-part of the name. E.g. "forum" in "helpers.forum.php"
		
		Examples:
			Helpers::load('forum', 'profile');
			// Loads helper files helpers/helpers.forum.php, helpers/helpers.profile.php
		
		Paramaters:
			$arg1 - A helper to load
			$arg2 - Another...
			$argN - ...
	*/
	
	public static function load()
	{
		$helpers = func_get_args();
		array_walk($helpers, array('self', 'fetch'));
	}
	
	/*
		Method:
			<Helpers::fetch>
		
		Inlude a helper file. Will throw a <HelperNotFoundException> if the helper file did not exist.
		
		Parameters:
			string $helper - The name of the helper file.
	*/
	
	private static function fetch($helper)
	{
		$filename = self::$path . 'helpers.' . $helper . '.php';
		
		if ( ! file_exists($filename) )
		{
			throw new HelperNotFoundException($filename);
		}
		
		require($filename);
	}
	
	// Method: <Helpers::setPath>
	// Set <Helpers::$path>
	public static function setPath($path)
	{
		self::$path = $path;
	}
}
