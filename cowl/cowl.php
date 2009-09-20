<?php

// Constant: <COWL_DIR>
// Contains the path in which Cowl is set up.
define('COWL_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);

// Constant: <COWL_CACHE_DIR>
// The path to the cache
define('COWL_CACHE_DIR', COWL_DIR . 'cache' . DIRECTORY_SEPARATOR);

// Constant: <COWL_BASE>
// The root of the URL. Will almost always be '/' in production.
define('COWL_BASE', dirname($_SERVER['SCRIPT_NAME']) . '/');

/*
	Class:
		<Cowl>
	
	Contains information about Cowl.
*/

class Cowl
{
	const version = '1.0';
	
	public static function url()
	{
		$args = func_get_args();
		return COWL_BASE . implode('/', $args);
	}
}
