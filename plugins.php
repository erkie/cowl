<?php

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
		
		Calling the constructor loads and instansiates every plugin specified in plugins.load
	*/
	
	public function __construct()
	{
		$this->loadPlugins();
	}
	
	/*
		Method:
			<Plugns::loadPlugins>
		
		Load plugins
	*/
	
	private function loadPlugins()
	{
		$plugins = Current::$config->get('plugins.load');
		
		foreach ( $plugins as $plugin )
		{
			$path = Current::$config->get('plugins.' . $plugin . '.path');
			require($path);
			
			$name = Plugins::makeName($path);
			$this->plugins[$plugin] = new $name();
		}
	}

	public function get($plugin)
	{
		if ( ! isset($this->plugins[$plugin]) )
			return false;
		return $this->plugins[$plugin];
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
			<Plugins::removeInstance>
		
		Removes an instance from <Plugins::$plugins>.
		
		Parameters:
			Plugin $plugin - The instance to remove.
	*/
	
	public function removeInstance(Plugin $instance)
	{
		foreach ( $this->plugins as $key => $plugin )
		{
			if ( $plugin === $instance )
			{
				unset($this->plugins[$key]);
			}
		}
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
		$name = preg_replace('/plugin\.(.*?)\.php/', '$1', array_last(explode('/', $filename)));
		return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
	}
}

/*
	Class:
		<Plugin>
	
	Abstract base class for all plugins.
*/

abstract class Plugin
{
	// FrontController-related hooks
	public function prePathParse(Controller $controller, StaticServer $server) {}
	public function postPathParse($args) {}
	public function postRun() {}
	
	public function preStaticServe(StaticServer $server) {}
	public function postStaticServe(StaticServer $server) {}
	
	// Command-related hooks
	public function commandRun(Command $command, $method, $args) {}
	
	// ORM-related hooks
	public function dbPopulate(DataMapper $mapper, DomainObject $object) {}
	public function dbFind(DataMapper $mapper, $args) {}
	public function dbInsert(DataMapper $mapper, DomainObject $object) {}
	public function dbUpdate(DataMapper $mapper, DomainObject $object) {}
	public function dbRemove(DataMapper $mapper, $id) {}
	public function postDBQuery(DataMapper $mapper, $query, DBDriver $db) {}
}
