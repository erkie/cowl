<?php

// Constant: COWL_CLI
// True if called from terminal/CLI
define('COWL_CLI', isset($_SERVER['argv']));

// Constant: COWL_DIR
// Contains the path in which Cowl is set up.
define('COWL_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);

// Constant: COWL_TOP
// Contains the path in which the project base is (where index.php lies)
define('COWL_TOP', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

// Constant: COWL_CACHE_DIR
// The path to the cache
define('COWL_CACHE_DIR', COWL_DIR . 'cache' . DIRECTORY_SEPARATOR);

// Constant: COWL_BASE
// The root of the URL. Will almost always be '/' in production.
if ( ! defined('COWL_BASE') )
	define('COWL_BASE', rtrim(dirname($_SERVER['SCRIPT_NAME']) . '/', '/') . '/');

define('COWL_START_TIME', microtime(true));

// Constant: COWL_DEBUG_MODE
// Define to true if you want to debug Cowl.
define('COWL_DEBUG_MODE', false);

/*
	Function:
		array_last
	
	Nice clean way of getting the last element of an array in one line.
	
	Parameters:
		$arr - The array
*/

function array_last($arr)
{
	$last = end($arr);
	return $last;
}

/*
	Class:
		Cowl
	
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
			Cowl::url
		
		Create a URL from the passed pieces. It will be relative to the current directory, so it is recommended to be used everywhere URLs are needed.
		
		It is useful because you do not have to worry about COWL_BASE everywhere. But in templates <url> should be used. (It's prettier)
		
		If the last parameter is an array, it will be used as query string parameters. 
		
		Example:
			// COWL_BASE is /
			echo Cowl::url('forum', 'topic', 'narwhals-and-me');
			 => /forum/topic/narwhals-and-me
			echo Cowl::url()
			 => /
			echo Cowl::url('forum', array('sort' => 'desc'));
			=> /forum?sort=desc
		
		Parameters:
			(optional) $url - An array of pieces, if an array is passed all pieces will be ignored.
			many mixed pieces - The pieces to be turned into a URL
		
		Returns:
			The url string.
	*/
	
	public static function url($url = null)
	{
		$args = is_array($url) ? $url : func_get_args();
		
		// Fetch params
		if ( is_array(array_last($args)) )
		{
			$params = array_pop($args);
			$params = array_map('urlencode', $params);
			$query_string = '?' . fimplode('%__key__;=%__val__;', $params, '&');
		}
		else
		{
			$query_string = '';
		}
		
		return COWL_BASE . implode('/', $args) . $query_string;
	}
	
	/*
		Method:
			Cowl::redirect
		
		Redirects the user to the specified site. This method calls <exit>, so nothing after the <Cowl::redirect>-call will be executed.
		
		Parameters:
			string $url - The URL the user should be redirected to.
	*/
	
	public static function redirect($url)
	{	
		// Make sure override certain request params are passed by upon redirect
		if ( isset($_SERVER['HTTP_X_REQUESTED_WITH']) )
			$url .= (strstr($url, '?') ? '&' : '?') . 'COWL_was_requested_with=' . $_SERVER['HTTP_X_REQUESTED_WITH'];
		
		// COWL_override_response_type is deprecated.
		if ( isset($_REQUEST['COWL_override_response_type']) )
			$url .= (strstr($url, '?') ? '&' : '?') . 'COWL_override_response_type=' . $_REQUEST['COWL_override_response_type'];

		// Use response_type instead
		if ( isset($_REQUEST['response_type']) )
			$url .= (strstr($url, '?') ? '&' : '?') . 'response_type=' . $_REQUEST['response_type'];
		
		header('Location: ' . $url);
		exit($url);
	}
	
	/*
		Method:
			Cowl::timer
		
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
			Cowl::timerEnd
		
		Ends the timer associated with $label. See <Cowl::getTimer> for information on retrieving the time.
		
		Parameters:
			string $label - The name of the timer.
	*/
	
	public static function timerEnd($label)
	{
		self::$timers[$label] = microtime(true) - self::$timers[$label];
		return self::$timers[$label];
	}
	
	/*
		Method:
			Cowl::getTimer
		
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
			Cowl::getTimers
		
		Returns an array containing the results of all the timers. If a timer has not been stopped the start-time in microseconds of that timer is stored.
		
		Returns:
			The array of timer results.
	*/
	
	public static function getTimers()
	{
		return self::$timers;
	}
}

/*
	Function:
		fimplode
	
	Formatted implode. Implode an array of associative of arrays, replacing instances
	of %key; with the value of the key. If the passed array is not associative, the variables
	%__key__; and %__val__; are available.
	
	Examples:
		$arr = array(
			array(
				'id' => 1,
				'name' => 'Foo'
			),
			
			array(
				'id' => 2,
				'name' => 'Bar'
			)
		);
		
		echo fimplode('<a href="?id=%id;">%name;</a>', $arr, ', ');
		// <a href="?id=1">Foo</a>, <a href="?id=2">Bar</a>
	
	Parameters:
		string $format_string - The string to use as template
		array $arr - The array to implode
		string $format_end - A string that shouldn't be added to the last element.
					Subject to the same formatting rules as $format_string
	
	Returns:
		The formatted string.
*/

function fimplode($format_string, $arr, $format_end = '')
{
	if ( ! is_string($format_string) )
	{
		user_error('Format string must be string', E_USER_WARNING);
		die;
	}
	
	if ( ! is_array($arr) )
	{
		user_error('Array must be array', E_USER_WARNING);
		die;
	}
	
	if ( ! is_string($format_end) )
	{
		user_error('Format end must be string, leave empty if you don\'t intend to use it', E_USER_WARNING);
		die;
	}
	
	$len = count($arr);
	$index = 0;
	$string = '';
	foreach ( $arr as $key => $element )
	{
		if ( is_array($element) )
		{
			$keys = array_keys($element);
			$values = array_values($element);
			
			foreach ( $values as $key => $value )
			{
				if ( ! is_string($value) )
				{
					unset($values[$key], $keys[$key]);
				}
			}
		}
		else
		{
			$keys = array('__key__', '__val__');
			$values = array($key, $element);
		}
		foreach ( $keys as $k => $v )
		{
			$keys[$k] = '%' . $v . ';';
		}
		$string .= str_replace($keys, $values, $format_string);
		if ( $index < $len-1 )
		{
			$string .= str_replace($keys, $values, $format_end);
		}
		$index++;
	}
	return $string;
}