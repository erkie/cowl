<?php

if ( ! defined('COWL_CACHED') )
{
	require('library/registries/registry.php');
	require('library/registries/request.php');
	require('library/registries/config.php');
}

/*
	Class:
		<Current>
	
	A container class for registries and other site-wide classes. It is not meant to be instantiated, and therefor has no non-static methods.
*/

class Current
{
	/*
		Method:
			<Current::initialize>
		
		Add all registries to self, _except_ those that are extra special. Can be called as many times as seen fit, but seeing as every call is to a registry, nothing new will happen the second time around.
	*/
	
	public static function initialize($path)
	{
		Config::setPath($path . 'config/');
		
		self::$request = Request::instance();
		self::$config = Config::instance();
	}
	
	/*
		Method:
			<Current::db>
		
		Method for fetching the database instance. If no instance has yet been created, create one.
		
		Parameters:
			string $driver - The driver to use, e.g "mysql" or "directory", etc
		
		Returns:
			The global database instance.
	*/
	
	public static function db($driver)
	{
		if ( ! isset(self::$db[$driver]) )
		{
			list($server, $user, $pass, $database) = self::$config->gets('db.server', 'db.user', 'db.password', 'db.database');
			$dbclass = $driver . 'DB';
			self::$db[$driver] = new $dbclass($server, $user, $pass, $database);
		}
		return self::$db[$driver];
	}
	
	// Property: <Current::$request>
	// $_POST, $_GET, $_COOKIE data
	public static $request;
	
	// Property: <Current::$db>
	// Database object
	public static $db = array();
	
	// Property: <Current::$store>
	// User session registry
	public static $store;
	
	// Property: <Current::$config>
	// Global config object
	public static $config;
	
	// Property: <Current::$plugins>
	// Current plugin object.
	public static $plugins;
	
	// Property: <Current::$log>
	// A generic log
	public static $log;
	
	// Property: <Current::$user>
	// A user object
	public static $user;
}
