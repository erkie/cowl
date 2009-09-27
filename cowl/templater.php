<?php

class TPLTemplateNotExistsException extends Exception {}
class TPLShellNotExistsException extends Exception {}

/*
	Class:
		<Templater>
	
	A class to help separate logic from presentation.
*/

class Templater
{
	// Property: <Templater::$vars>
	// The variabels that will be available to templates.
	protected $vars = array();
	
	// Property: <Templater::$dir>
	// The directory in which templates reside.
	protected $dir = 'templates/';
	
	// Property: <Templater::$template>
	// The name of the template that this instance will use.
	protected $template;
	
	// Property: <Templater::$shell>
	// The shell template in which the template will be included by. Used for different response types, HTML, JSON, XML, etc.
	protected $shell;
	
	// Property: <Templater::$cache>
	// Holds an instance of <Cache>, if the output is cached.
	protected $cache;
	
	// Property: <Templater::$base_dir>
	// Base directory for all templates.
	protected static $base_dir = 'templates/';
		
	/*
		Method:
			<Templater::add>
		
		Add information that will be available to the presentational layer.
		
		Parameters:
			mixed $key - If $key is an array it will be merged into the <Templater::$vars>-array, else $key is the name of the variable assosciated with the $value inside the template.
			mixed $value - The value.
	*/
	
	public function add($key, $value = null)
	{
		if ( is_array($key) )
		{
			$this->vars = array_merge($this->vars, $key);
		}
		else
		{
			$this->vars[$key] = $value;
		}
	}
	
	/*
		Method:
			<Templater::render>
		
		Render a file from the <Templater::$dir>, and if cacheing is enabled and the cache is not outdated output the contents of the cache.
		
		The passed $filename will be rendered inside the defined shell. (<Templater::$shell>).
		
		Parameters:
			string $filename - The file, contained in <Templater::$dir>, to render.
	*/
	
	public function render($filename)
	{
		if ( ! $this->exists($filename) )
		{
			throw new TPLTemplateNotExistsException($this->dir . $filename);
		}
		
		$this->template = $this->dir . $filename;
		
		// If cacheing is enabled and not outdated use that copy
		// Else start output buffering so we can catch outputed contents.
		if ( ! is_null($this->cache) )
		{
			if ( ! $this->isOutDated() )
			{
				echo $this->cache->get();
				return;
			}
			else
			{
				ob_start();
			}
		}
		
		// Magic
		extract($this->vars);
		include($this->shell);
		
		// Update cache with outputed contents
		if ( ! is_null($this->cache) )
		{
			$contents = ob_get_contents();
			$this->cache->update($contents);
		}
	}
	
	/*
		Method:
			<Templater::isOutDated>
		
		If the cache needs updating.
		
		Returns:
			True if the cache is outdated.
	*/
	
	public function isOutDated()
	{
		return ( is_null($this->cache) ) ? true : $this->cache->isOutDated();
	}
	
	/*
		Method:
			<Templater::exists>
		
		Checks wether a template exists inside <Templater::$dir>
		
		Parameters:
			string $filename - The file to be checked.
	*/
	
	public function exists($filename)
	{
		return file_exists($this->dir . $filename);
	}
	
	/*
		Method:
			<Templater::setType>
		
		Set the type of shell to be used, HTML, JSON, etc. Will throw a TPLShellNotExistsException if the shell did not exist.
		
		Parameters:
			string $type - The type of shell. A corresponding shell.type.php file must exist in <Templater::$dir>
	*/
	
	public function setType($type)
	{
		$name = self::$base_dir . 'shell.' . $type . '.php';
		if ( ! file_exists($name) )
		{
			throw new TPLShellNotExistsException($name);
		}
		$this->shell = $name;
	}
	
	/*
		Method:
			<Templater::setCache>
		
		Create new instance of <Cache> that will be used for output. This effectively enables caching of output.
				
		Parameters:
			string $path - The path for <Cache::__construct>.
			int $time - (optiona) The amount of time, in seconds, to cache a template.
	*/
	
	public function setCache($path, $time = 600)
	{
		$this->cache = new Cache($path, $time);
	}
	
	// Method: <Templater::setDir>
	// Set the directory in which templates are contained.
	public function setDir($dir)
	{
		$this->dir = $dir;
	}
	
	// Method: <Templater::setBaseDir>
	// Set the <Templater::$base_dir>.
	public static function setBaseDir($dir)
	{
		self::$base_dir = $dir;
	}
}
