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
	
	public static function db()
	{
		if ( is_null(self::$db) )
		{
			list($server, $user, $pass, $database) = self::$config->gets('db.server', 'db.user', 'db.password', 'db.database');
			self::$db = new DB($server, $user, $pass, $database);
		}
		return self::$db;
	}
	
	// Property: <Current::$user>
	// User object, should really be removed.
	public static $user;
	
	// Property: <Current::$request>
	// $_POST, $_GET data
	public static $request;
	
	// Property: <Current::$db>
	// Database object
	public static $db;
	
	// Property: <Current::$store>
	// User session registry
	public static $store;
	
	// Property: <Current::$registry>
	// Site-wide registry
	public static $registry;
	
	// Property: <Current::$config>
	// Global config object
	public static $config;
	
	// Property: <Current::$plugins>
	// Current plugin object.
	public static $plugins;
	
	// Property: <Current::$auth>
	// Placeholder for a global named auth
	public static $auth;
}
