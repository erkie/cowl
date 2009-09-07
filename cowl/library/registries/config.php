<?php

class ConfigKeyNotFoundException extends RegistryFailException {}

/*
	Class:
		<Config>
	
	Global config registry. Parses config.ini according to parse_ini_file with a few exceptions:
	
		- A tilde (~) in strings is replaced with the value of paths.base
		- Periods (.) in names is used to namespace values.
	
	The <Config::$path> must be set if the config.ini-file lies in another directory than this class.
*/

class Config extends Registry
{
	// Property: <Config::$instance>
	// See <Registry::$instance>
	protected static $instance;
	
	// Property: <Config::$path>
	// Points to the directory in which the config.ini file lies.
	private static $path = '';
	
	// Property: <Config::$base>
	// The directory for which the tilde in names is replaced with.
	private $base = '';
	
	// Property: <Config::instance>
	// See <Registry::instance>
	public static function instance()
	{
		return parent::getInstance(__CLASS__, self::$instance);	
	}
	
	/*
		Method:
			<Config::initialize>
		
		Parse ini-file and add variables to store.
	*/
	
	protected function initialize()
	{
		$this->parseIniFile(self::$path . 'config.ini');
	}
	
	/*
		Method:
			<Config::parseIniFile>
		
		<Config>'s version of parse_ini_file. Uses the <Config::set>-method to add values to store.
	*/
	
	private function parseIniFile($filename)
	{
		$arr = parse_ini_file($filename);
		$this->base = $arr['paths.base'];
		
		foreach ( $arr as $key => $value )
		{
			$arr[$key] = str_replace('~', $this->base, $value);
		}
		$this->data = $arr;
	}
	
	/*
		Method:
			<Config::get>
		
		Works almost the same as <Registry::get>, but with a much faster and simpler model for fetching values.
		
		Parameters:
			$key - The key to find.
	*/
	
	public function get($key)
	{
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
