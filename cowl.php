<?php

// Constant: <COWL_DIR>
// Contains the path in which Cowl is set up.
define('COWL_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);

// Constant: <COWL_TOP>
// Contains the path in which the project base is (where index.php lies)
define('COWL_TOP', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

// Constant: <COWL_CACHE_DIR>
// The path to the cache
define('COWL_CACHE_DIR', COWL_DIR . 'cache' . DIRECTORY_SEPARATOR);

// Constant: <COWL_BASE>
// The root of the URL. Will almost always be '/' in production.
if ( ! defined('COWL_BASE') )
	define('COWL_BASE', rtrim(dirname($_SERVER['SCRIPT_NAME']) . '/', '/') . '/');

/*
	Class:
		<Cowl>
	
	Contains information about Cowl.
*/

class Cowl
{
	const version = '1.0';
	
	// Property: <Cowl::$timers>
	// Contains the results of times created by <Cowl::timer>
	private static $timers = array();
	
	/*
		Method:
			<Cowl::url>
		
		Create a URL from the passed pieces. It will be relative to the current directory, so it is recommended to be used everywhere URLs are needed.
		
		It is useful because you do not have to worry about COWL_BASE everywhere. But in templates <url> should be used. (It's prettier)
		
		Example:
			// COWL_BASE is /
			
			// Echoes /forum/topic/narwhals-and-me
			echo Cowl::url('forum', 'topic', 'narwhals-and-me');
			// Echoes /
			echo Cowl::url()
		
			// COWL_BASE is /my-site/
			
			// Echoes /my-site/forum/topic/eat-cowl
			echo Cowl::url('forum', 'topic', 'eat-cowl');
			// Echoes /my-site/
			echo Cowl::url();
		
		Parameters:
			(optional) $url - An array of pieces, if an array is passed all pieces will be ignored.
			many mixed pieces - The pieces to be turned into a URL
		
		Returns:
			The url string.
	*/
	
	public static function url($url = null)
	{
		$args = is_array($url) ? $url : func_get_args();
		return COWL_BASE . implode('/', $args);
	}
	
	/*
		Method:
			<Cowl::redirect>
		
		Redirects the user to the specified site. This method calls <exit>, so nothing after the <Cowl::redirect>-call will be executed.
		
		Parameters:
			string $url - The URL the user should be redirected to.
	*/
	
	public static function redirect($url)
	{
		if ( isset($_SERVER['HTTP_X_REQUESTED_WITH']) )
			$url .= (strstr($url, '?') ? '&' : '?') . 'COWL_was_requested_with=' . $_SERVER['HTTP_X_REQUESTED_WITH'];
		
		if ( isset($_REQUEST['COWL_override_response_type']) )
			$url .= (strstr($url, '?') ? '&' : '?') . 'COWL_override_response_type=' . $_REQUEST['COWL_override_response_type'];
		
		header('Location: ' . $url);
		exit($url);
	}
	
	/*
		Method:
			<Cowl::timer>
		
		Examples:
			Cowl::timer('Instantiate 1000 objects');
			// Code to be timed
			Cowl::timerEnd('Instantiate 1000 objects');
			
			printf("That took %f seconds.")
		
		Start timing from the point <Cowl::timer> is called. The name of the timer is the passed $label. So be sure to remember it!
		
		Parameters:
			string $label - The key associated with the timer.
	*/
	
	public static function timer($label)
	{
		self::$timers[$label] = microtime(true);
	}
	
	/*
		Method:
			<Cowl::timerEnd>
		
		Ends the timer associated with $label. See <Cowl::getTimer> for information on retrieving the time.
		
		Parameters:
			string $label - The name of the timer.
	*/
	
	public static function timerEnd($label)
	{
		self::$timers[$label] = microtime(true) - self::$timers[$label];
	}
	
	/*
		Method:
			<Cowl::getTimer>
		
		Returns the results of a specified timer in seconds.
		
		Parameters:
			string $label - The name of the time to be returned.
		
		Returns:
			The results in seconds.
	*/
	
	public static function getTimer($label)
	{
		return self::$timers[$label];
	}
	
	/*
		Method:
			<Cowl::getTimers>
		
		Returns an array containing the results of all the timers. If a timer has not been stopped the start-time in microseconds of that timer is stored.
		
		Returns:
			The array of timer results.
	*/
	
	public static function getTimers()
	{
		return self::$timers;	
	}
}
