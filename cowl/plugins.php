<?php

include('plugin.php');

/*
	Class:
		<Plugins>

	Loads all plugins specified in the <Current::$config> and calls them when the appropriate hooks are called in client code. <Plugins> is a singleton.
*/

class Plugins
{
	// Property: <Plugins::$plugins>
	// The plugin instances.
	private $plugins = array();
		
	/*
		Constructor:
			<Plugins::__construct>
		
		Calling the constructor loads and instansiates every plugin.
		
		Parameters:
			$plugins_dir - An existing directory containing the plugins.
	*/
	
	public function __construct($plugins_dir = 'plugins/')
	{
		$this->loadPlugins($plugins_dir);
	}
	
	/*
		Method:
			<Plugns::loadPlugins>
		
		Load plugins from a $dir, will instansiate them too.
		
		Parameters:
			$dir - Directory to scan
	*/
	
	private function loadPlugins($dir)
	{
		$plugins = Current::$config->get('plugins.load');
		
		foreach ( $plugins as $plugin )
		{
			$path = Current::$config->get('plugins.' . $plugin . '.path');
			require($path);
			
			$name = Plugins::makeName($path);
			$this->plugins[] = new $name();
		}
	}
	
	/*
		Method:
			<Plugins::addInstance>
		
		Adds an instance to the <Plugins::$plugins>-array.
		
		Paramaters:
			Plugin $plugin - The plugin instance to append.
	*/
	
	public function addInstance(Plugin $instance)
	{
		$this->plugins[] = $instance;
	}
	
	/*
		Method:
			<Plugins::hook>
		
		Call the plugins' hooks.
		
		Parameters:
			$method - The "name" of the hook.
			$arg1 - Argument to be passed to the hook-method.
			$argN - ...
	*/
	
	public function hook($method)
	{
		$args = func_get_args();
		$args = array_slice($args, 1);
		
		foreach ( $this->plugins as $plugin )
		{
			call_user_func_array(array($plugin, $method), $args);
		}
	}
	
	/*
		Method:
			<Plugins::makeName>
		
		Makes a plugin name from a corresponding filename. Following these conventions:
		
			1. Replace _ with a space ( )
			2. Uppercase the first letter in every word
			3. Remove spaces
		
		Parameters:
			$filename - The filename of the plugin
		
		Returns:
			The Pluginname
	*/
	
	public static function makeName($filename)
	{
		$name = preg_replace('/plugin\.(.*?)\.php/', '$1', end(explode('/', $filename)));
		return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
	}
}
