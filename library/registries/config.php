<?php

class ConfigKeyNotFoundException extends RegistryException {}

/*
	Class:
		<Config>
	
	Global config registry. Parses config.php, which returns an array of key values.
	It is also capable of loading several other configuration files.
	
		- A tilde (~) in strings is replaced with the value of paths.base
		- Periods (.) in names is used to namespace values.
	
	The <Config::$path> must be set if the config.php-file lies in another directory than this class.
*/

class Config extends Registry
{
	// Property: <Config::$instance>
	// See <Registry::$instance>
	protected static $instance;

	// Property: <Config::$path>
	// Points to the directory in which the config.php file lies.
	private static $path = '';
	
	// Property: <Config::instance>
	// See <Registry::instance>
	public static function instance()
	{
		return parent::getInstance(__CLASS__, self::$instance);	
	}
	
	/*
		Method:
			<Config::initialize>
		
		Parse ini-file and add variables to store. The values are also stored in the cache file as serialized php.
	*/
	
	protected function initialize()
	{
		$this->parseFile(self::$path . 'config.php');
		
		// Any other files
		$other = $this->get('config.other');
		if ( is_array($other) )
		{
			// Loop over other config files backwards, so we catch
			// if other "config.other"'s are added
			while ( isset($this->data['config.other'][0]) )
			{
				$this->parseFile($this->data['config.other'][0]);
				array_shift($this->data['config.other']);
			}
		}
	}
	
	/*
		Method:
			<Config::parseFile>
		
		Parse a configuration file. Uses the <Config::set>-method to add values to store.
	*/
	
	private function parseFile($filename)
	{
		if ( ! file_exists($filename) )
		{
			return false;
		}
		
		$arr = include($filename);
		$base = isset($arr['paths.base']) ? $arr['paths.base'] : $this->data['paths.base'];
		
		foreach ( $arr as $key => $value )
		{
			$arr[$key] = str_replace('~', $base, $value);
		}
		
		foreach ( $arr as $key => $value )
		{
			if ( isset($this->data[$key]) && is_array($this->data[$key]) )
			{
				$this->data[$key] = array_merge($this->data[$key], $value);
			}
			else
			{
				$this->data[$key] = $value;
			}
		}
	}
	
	/*
		Method:
			<Config::get>
		
		Works almost the same as <Registry::get>, but with a much faster and simpler model for fetching values.
		
		Parameters:
			$key - The key to find.
	*/
	
	public function get($key = '')
	{
		if ( empty($key) )
		{
			return $this->data;
		}
		
		if ( ! isset($this->data[$key]) )
		{
			throw new ConfigKeyNotFoundException($key);
		}
		
		return $this->data[$key];
	}
	
	/*
		Method:
			<Config::setPath>
		
		Sets the path variable.
		
		Parameters:
			$path - The directory in which the config.ini-file lies.
	*/
	
	public static function setPath($path)
	{
		self::$path = $path;
	}
}
