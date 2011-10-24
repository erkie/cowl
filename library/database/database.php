<?php

require('datamapper.php');
require('domainobject.php');
require('domaincollection.php');
require('dbdriver.php');
require('dbresult.php');

class DatabaseDriverException extends Exception {}

/*
	Class:
		Database
	
	Keeps track of all loaded drivers and loading of drivers.
*/

class Database
{
	private static $path = 'drivers/';
	
	private static $loaded = array();
	
	public static function loadDriver($driver)
	{
		if ( ! in_array($driver, self::$loaded) )
		{
			if ( ! is_dir(self::$path . $driver) )
			{
				throw new DatabaseDriverException($driver);
			}
			
			$path = self::$path . $driver . DIRECTORY_SEPARATOR . $driver . '.';
			
			require($path . 'db.php');
			require($path . 'dbresult.php');
			require($path . 'querybuilder.php');
			
			self::$loaded[] = $driver;
		}
	}
	
	public static function setPath($path)
	{
		self::$path = $path;
	}
}
