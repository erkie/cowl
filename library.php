<?php

class LibraryNotFoundException extends Exception {}

/*
	Class:
		Library
	
	Keeps track and loads libraries.
*/

class Library
{
	// Property: <Library::$path>
	// The directory in which library files are contained.
	private static $path = 'library/';
	
	/*
		Method:
			Library::load
		
		Loads all libraries. If they are not found in <Library::$path> a <LibraryNotFoundException> is thrown. 
		
		Parameters:
			string $library1 - The name of the library to load
			string $libraryN - ...
	*/
	
	public static function load()
	{
		foreach ( func_get_args() as $library )
		{
			$name = self::$path . strtolower($library) . '.php';
			if ( ! file_exists($name) )
				throw new LibraryNotFoundException($library);
			
			// Already exists?
			if ( class_exists($library) )
				return true;
			
			require($name);
		}
	}
	
	/*
		Method:
			Library::loadInstance
		
		Load $library and return an instance of that class.
		
		Parameters:
			string $library - The name of the library to load.
			mixed $arg1 - Argument to be passed to the constructor of the new instance.
			mixed $argN - ...
	*/
	
	public static function loadInstance($library)
	{
		self::load($library);
		
		// Get constructor arguments
		$args = func_get_args();
		$args = array_slice($args, 1);
		
		return call_user_func_array(
			array(new ReflectionClass($library), 'newInstance'),
			$args
		);
	}
	
	/*
		Method:
			Library::setPath

		Set the path in which libraries are loaded from.
		
		Parameters:
			string $path - The path to the libraries.
	*/
	
	public static function setPath($path)
	{
		self::$path = $path;
	}
}
