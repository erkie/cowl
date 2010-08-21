<?php

class ConfigKeyNotFoundException extends RegistryException {}

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
	
	// Property: <Config::$cache>
	// The <PHPCache>-instance for the config-file.
	private $cache;
	
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
		$cache_name = self::$path . 'config.ini';
		
		$this->cache = new PHPFileCache('cowl.config', $cache_name);
		if ( false === ($this->data = $this->cache->get()) ) // when working on this don't forget to turn of cacheing
		{
			$this->data = array();
			$this->parseIniFile(self::$path . 'config.ini');
			
			// Any other files 
			$other = $this->get('config.other');
			if ( is_array($other) )
			{
				array_walk($other, array($this, 'parseIniFile'));
			}
			
			$this->cache->update($this->data);
		}
	}
	
	/*
		Method:
			<Config::parseIniFile>
		
		<Config>'s version of parse_ini_file. Uses the <Config::set>-method to add values to store.
	*/
	
	private function parseIniFile($filename)
	{
		if ( ! file_exists($filename) )
		{
			return false;
		}
		
		$arr = parse_ini_file($filename);
		$base = isset($arr['paths.base']) ? $arr['paths.base'] : $this->data['paths.base'];
		
		foreach ( $arr as $key => $value )
		{
			$arr[$key] = str_replace('~', $base, $value);
		}
		$this->data = array_merge($this->data, $arr);
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
