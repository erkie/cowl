<?php

/*
	Class:
		<Request>
	
	A registry containing the $_REQUEST-data from the current request, and information about the current reuqest.
*/

class Request extends Registry
{
	// Property: <Request::$instance>
	// See <Registry::$instance>
	protected static $instance;
	
	// Property: <Request::$request_data>
	// The data about the request.
	protected $request_data = array();
	
	// Method: <Request::instance>
	// See <Registry::instance>
	public static function instance()
	{
		return parent::getInstance(__CLASS__, self::$instance);
	}
	
	/*
		Method:
			<Request::initialize>
		
		Adds $_REQUEST-data to store.
	*/
	
	protected function initialize()
	{
		$this->data = $_REQUEST;
		if ( function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc() )
			$this->data = $this->stripSlashes($this->data);
		$this->request_data = array();
	}
	
	/*
		Method:
			<Request::setInfo>
		
		Set information about the current request. Used to set misc. information that is not
		sent as parameters from the client. For example CSS stylesheets and JS files.
		
		Examples:
			// Set simple key-value data
			$request = Request::getInstance();
			$request->setInfo('username', 'bob');
			
			$request->getInfo('username'); // -> bob
			
			// It can also be used to set several values on an object
			$request->setInfo('css[]', 'one.css');
			$request->setInfo('css[], 'two.css');
			
			$request->getInfo('css'); // -> array('one.css', 'two.css');
		
		Parameters:
			$key - The key to set
			$value - The value
	*/
	
	public function setInfo($key, $value)
	{
		// Check for array appending
		if ( substr($key, -2) == '[]' )
		{
			$key = substr($key, 0, -2);
			
			// Ensure that existing key exists
			if ( ! $this->getInfo($key) )
			{
				$this->setInfo($key, array());
			}
			
			$old_value = $this->getInfo($key);
			$old_value[] = $value;
			$value = $old_value;
		}
		$this->request_data[$key] = $value;
	}
	
	/*
		Method:
			<Request::getInfo>
		
		Fetches information from the <Request::$request_data>-array.
		
		Parameters:
			$key - The key to fetch
		
		Returns:
			The value.
	*/
	
	public function getInfo($key)
	{
		if ( ! isset($this->request_data[$key]) )
		{
			return null;
		}
		
		return $this->request_data[$key];
	}
	
	/*
		Method:
			<Request::stripSlashes>
		
		Recursively strip slashes from request variables. This should only be called if get_magic_quotes is on.
		Stupid magic quotes.
		
		Parameters:
			$arr - The array to strip slashes on
	*/
	
	private function stripSlashes($arr)
	{
		foreach ( $arr as $key => $val )
		{
			if ( is_array($val) )
			{
				$arr[$key] = $this->stripSlashes($val);
			}
			else
			{
				$arr[$key] = stripslashes($val);
			}
		}
		return $arr;
	}
}
