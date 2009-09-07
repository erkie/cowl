<?php

class LibraryNotFoundException extends Exception {}
class LibraryAlreadyExistsException extends Exception {}

class Library
{
	private static $path = 'library/';
	
	public static function load()
	{
		foreach ( func_get_args() as $library )
		{
			$name = self::$path . strtolower($library) . '.php';
			if ( ! file_exists($name) )
			{
				throw new LibraryNotFoundException($library);
			}
			
			if ( class_exists($library) )
			{
				throw new LibraryAlreadyExistsException($library);
			}
			
			include($name);
		}
	}
	
	public static function loadInstance($library)
	{
		self::load($library);
		
		$args = func_get_args();
		$args = array_slice($args, 1);
		
		return call_user_func_array(
			array(new ReflectionClass($library), 'newInstance'),
			$args
		);
	}
	
	public static function setPath($path)
	{
		self::$path = $path;
	}
}