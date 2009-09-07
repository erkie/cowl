<?php

// Constant: <COWL_DIR>
// Contains the path in which Cowl is set up.
define('COWL_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);

// Constant: <COWL_BASE>
// The root of the URL. Will almost always be '/' in production.
define('COWL_BASE', dirname($_SERVER['SCRIPT_NAME']) . '/');

class Cowl
{
	public static function url()
	{
		$args = func_get_args();
		return COWL_BASE . implode('/', $args);
	}
}
