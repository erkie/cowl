<?php

class RequestInfoNotFoundException extends RegistryFailException {}

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
		$this->request_data = array();
	}
	
	/*
		Method:
			<Request::setInfo>
		
		Set information about the current request.
		
		Parameters:
			$key - The key to set
			$value = The value
	*/
	
	public function setInfo($key, $value)
	{
		$this->request_data[$key] = $value;
	}
	
	/*
		Method:
			<Request::getInfo>
		
		Fetches information from the <Request::$request_data>-array.
		
		Parameters:
			$key - The key to fetch
		
		Returns
			The value.
	*/
	
	public function getInfo($key)
	{
		if ( ! isset($this->request_data[$key]) )
		{
			throw new RequestInfoNotFoundException($key);
		}
		
		return $this->request_data[$key];
	}
}
