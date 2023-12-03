<?php

class HelpersNotFoundException extends Exception {}

/*
	Class:
		Helpers
	
	Keep track of helper files and create an easily managed interface for including helpers.
*/

class Helpers
{
	// Property: Helpers::$path
	// Holds the path to the helper files
	private static $path = 'helpers/';
	
	// Property: Helpers::$app_path
	// Path to app-wide helper files
	private static $app_path = 'app/helpers/';
	
	private static $loaded = array();
	
	/*
		Method:
			Helpers::load
		
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
		array_walk($helpers, array(self::class, 'fetch'));
	}
	
	/*
		Method:
			Helpers::fetch
		
		Inlude a helper file. Will throw a <HelperNotFoundException> if the helper file did not exist.
		
		Parameters:
			string $helper - The name of the helper file.
	*/
	
	private static function fetch($helper)
	{
		if ( in_array($helper, self::$loaded) )
			return;
		
		self::$loaded[] = $helper;
		
		$pieces = explode('/', $helper);
		$name = array_pop($pieces);
		
		if ( isset($pieces[0]) && $pieces[0] == 'app' )
			array_shift($pieces);
		
		$path = implode('/', $pieces) . '/';
		
		$base_dir = preg_match('#^app/#', $helper) ? self::$app_path : self::$path;
		$filename = $base_dir . $path . 'helpers.' . $name . '.php';
		
		if ( ! file_exists($filename) )
		{
			throw new HelpersNotFoundException($filename);
		}
		
		require($filename);
	}
	
	// Method: Helpers::setPath
	// Set <Helpers::$path>
	public static function setPath($path)
	{
		self::$path = $path;
	}
	
	// Method: Helpers::setAppPath
	// Set <Helpers::$app_path>
	public static function setAppPath($path)
	{
		self::$app_path = $path;
	}
}
