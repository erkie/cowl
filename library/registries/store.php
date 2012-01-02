<?php

/*
	Class:
		Store
	
	A registry containing data stored in a session until the next request.
	Only store volatile data in the Store.
*/

class Store extends Registry
{
	// Property: Store::$instance
	// See <Registry::$instance>
	protected static $instance;
	
	// Method: Store::instance
	// See <Registry::instance>
	public static function instance()
	{
		return parent::getInstance(__CLASS__, self::$instance);
	}
	
	/*
		Method:
			Store::initialize
		
		Adds $_REQUEST-data to store.
	*/
	
	protected function initialize()
	{
		$this->data =& $_SESSION;
	}
	
	/*
		Method:
			Store::get
		
		Works like <Registry:get>, but removes the found key immediatly after it is finished.
		So a key here can only be retrieved once.
		
		See <Registry::get>
	*/
	
	public function get($value = null)
	{
		$ret = parent::get($value);
		unset($this->data[$value]);
		return unserialize($ret);
	}
	
	/*
		Method:
			Store::set
		
		Set a value that will persist until the next page load.
		
		See <Registry::set>
	*/
	
	public function set($name, $value)
	{
		return parent::set($name, serialize($value));
	}
}
