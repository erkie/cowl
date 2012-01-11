<?php

class RegistryException extends Exception {}
class RegistryMemberNotFoundException extends RegistryException {}

abstract class Registry
{
	/*
		Property:
			<Registry::$data>
		
		Contains the data for the store.
	*/
	
	protected $data = array();
	
	/*
		Property:
			<Registry::$is_dirty>
		
		Contains a flag to indicate whether a change has been made to the data. This flag can be used by sub-classes to store new data when the registry is __destructed.
	*/
	
	protected $is_dirty = false;
	
	/*
		Property:
			<Registry::$instance>
		
		Singletone instance.
	*/
	
	protected static $instance;
	
	/*
		Method:
			<Registry::__construct>
		
		Calls the user-defined $this->initialize. Cannot, and should not, be overwritten.
	*/
	
	final protected function __construct()
	{
		$this->initialize();
	}
	
	/*
		Method:
			<Registry::initialize>
		
		Because of all the problems caused by PHP's lack of features a custom initialize method is used to initialize a registry.
	*/
	
	protected function initialize() {}
	
	/*
		Method:
			<Registry::instance>
		
		This method has to be redefined by all subclasses. Check the code below for suggestions.
		
		(begin code)

		public static function instance()
		{
			return parent::getInstance(__CLASS__, self::$instance);
		}
		
		(end code)
	*/
	
	public static function instance() { throw new Exception("Registry::instance is an abstract method"); }
	
	/*
		Method:
			<Registry::getInstance>
		
		getInstance creates a new instance and returns it, in a singletonish way. This method should _only_ be called from instance();
	
		Parameters:
			$name - The name of the class to initialized.
			&$instance - A reference to the instance variable. This will be changed or left unmodified and returned.
		
		Returns:
			The newly created instance or $instance.
	*/
	
	protected static function getInstance($name, &$instance)
	{
		if ( ! $instance )
		{
			$instance = new $name();
		}
		return $instance;
	}
	
	/*
		Method:
			<Registry::get>
		
		Fetches the $value from the registry's store. Namespacing can be applied.
		
		(start code)
		
		> $inst->get('version')
		'1.2.3'
		> $inst->get('paths.cache')
		'cowl/cache/'
		> $inst->get('plugins.app.version');
		'0.9.3'
		
		(end)
		
		Parameters:
			$value - The key to be fetched
		
		Returns:
			The value, or false if none was found.
	*/
	
	public function get($value = null)
	{
		if ( is_null($value) )
		{
			return $this->data;
		}
		
		$namespaces = explode('.', $value);
		
		$current = &$this->data;
		foreach ( $namespaces as $space )
		{
			if ( ! isset($current[$space]) )
			{
				throw new RegistryMemberNotFoundException($value);
			}
			
			$current = &$current[$space];
		}
		
		return $current;
	}
	
	/*
		Method:
			Request::has
		
		Checks to see if the key exists.
		
		Returns:
			True if it does, else false.
	*/
	
	public function has($key)
	{
		try { $this->get($key); return true; }
		catch ( RegistryMemberNotFoundException $e ) {}
		return false;
	}
	
	/*
		Method:
			Registry::getOr
		
		Attempt to fetch a value from the store, if it doesn't exist, just 
		return a default value. A fail-gracefully version of <Registry::get>
		
		Parameters:
			(string) $key - The key to get
			(optional) $default_value - The default value to return if key is not present.
										Defaults to an empty string.
		
		Returns:
			The requested value, or the default value.
	*/
	
	public function getOr($key, $default_value = '')
	{
		try {
			return $this->get($key);
		}
		catch ( Exception $e )
		{}
		
		return $default_value;
	}
	
	/*
		Method:
			Registry::gets
		
		Works like <Registry::get> accept it can take several values to be found, returning them in the order of appearence in the argument list.
		
		Examples:
			> list($path, $cache) = $inst->gets('plugins.versions.path', 'plugins.versions.cache');
		
		Parameters:
			$value1 - The key to fetch.
			$valuen - ...
		
		Returns:
			An array of the values fetched.
	*/
	
	public function gets()
	{
		$args = func_get_args();
		$ret = array();
		foreach ( $args as $arg )
		{
			$ret[] = $this->get($arg);
		}
		return $ret;
	}
	
	/*
		Method:
			Registry::fetch
		
		Fetches a piece of the data store.
		
		Parameters:
			$value1 - The key to fetch.
			$valuen - ...
		
		Returns:
			An associative array of the values fetched.
	*/
	
	public function fetch()
	{
		$args = func_get_args();
		$values = array_map(array($this, 'get'), $args);
		return array_combine($args, $values);
	}
	
	/*
		Method:
			Registry::set
		
		Sets the corresponding $key, $value in the data store, overwriting existing values. This method will also set the is_dirty flag to true. Namespacing is allowed, just as in <Registry::get>.
		
		Parameters:
			$name - The key of the...
			$value - Value to be entered into the store.
		
		Returns:
			Null, always, because it should _always_ succeed.
	*/
	
	public function set($name, $value)
	{
		$this->is_dirty = true;
		
		$namespaces = explode('.', $name);
		
		$current = &$this->data;
		foreach ( $namespaces as $space )
		{
			$current = &$current[$space];
		}
		$current = $value;
	}
}
