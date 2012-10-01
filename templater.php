<?php

class TPLTemplateNotExistsException extends Exception {}
class TPLLayoutNotExistsException extends Exception {}

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
	
	// Property: <Templater::$type>
	// The response type of the template. Used for <Templater::$layout>.
	protected $type;
	
	// Property: <Templater::$layout>
	// The layout template in which the template will be included by. Used for different response types, HTML, JSON, XML, etc.
	protected $layout;
	
	// Property: <Templater::$cache>
	// Holds an instance of <Cache>, if the output is cached.
	protected $cache;
	
	// Property: <Templater::$cache_path>
	// Holds the values that will be passed to <Cache> when cacheing is activated.
	protected $cache_path;
	
	// Property: <Templater::$cache_active>
	// True if output should use cached version
	protected $cache_active = false;
	
	// Property: <Templater::$base_dir>
	// Base directory for all templates. See <Templater::$layout_dir> for the layouts.
	protected static $base_dir = 'templates/';
	
	// Property: <Templater::$layout_dir>
	// Path to where all layout files are.
	protected static $layout_dir = 'templates/layouts/';
		
	/*
		Method:
			<Templater::add>
		
		Add information that will be available to the presentational layer.
		
		Parameters:
			mixed $key - If $key is an array it will be merged into the <Templater::$vars>-array, else $key is the name of the variable assosciated with the $value inside the template.
			mixed $value - The value.
		
		Returns:
			Returns the $this-instance for chaining commands.
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
		return $this;
	}
	
	/*
		Method:
			<Templater::render>
		
		Render a file from the <Templater::$dir>, and if cacheing is enabled and the cache is not outdated output the contents of the cache.
		
		The passed $filename will be rendered inside the defined layout. (<Templater::$layout>).
		
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
		if ( $this->cache_active )
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
		include($this->layout);
		
		// Update cache with outputed contents
		if ( $this->cache_active )
		{
			$contents = ob_get_contents();
			$this->cache->update($contents);
		}
	}
	
	/*
		Method:
			<Templater::renderError>

		Render an error page.
	*/
	
	public function renderError($error_code)
	{
		$error_name = sprintf("error.%s.php", $error_code);
		$generic_name = "error.500.php";
		
		if ( file_exists(self::$base_dir . $error_name) )
			$this->template = self::$base_dir . $error_name;
		elseif ( file_exists(self::$base_dir . $generic_name) )
			$this->template = self::$base_dir . $generic_name;
		
		if ( $this->template )
		{
			extract($this->vars);
			include($this->layout);
		}
		else
		{
			printf('<!DOCTYPE><title>%s - Error</title><h1>%s - error</h1><p>Something went wrong</p>', $error_code, $error_code);
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
		return ( ! $this->cache_active ) ? true : $this->cache->isOutDated();
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
		
		Set the type of layout to be used, HTML, JSON, etc. Will throw a TPLLayoutNotExistsException if the layout did not exist.
		
		Parameters:
			string $type - The type of layout. A corresponding layout.type.php file must exist in <Templater::$dir>
	*/
	
	public function setType($type)
	{
		$name = self::$layout_dir . 'layout.' . $type . '.php';
		if ( ! file_exists($name) )
		{
			throw new TPLLayoutNotExistsException($name);
		}
		$this->layout = $name;
		$this->type = $type;
	}
	
	/*
		Method:
			<Templater::getType>
		
		Returns:
			The current response type, <Templater::$type>
	*/
	
	public function getType()
	{
		return $this->type;
	}
	
	/*
		Method:
			<Templater::setCache>
		
		Set values to <Templater::$cache> that will be used as paramaters if cacheing is enabled.
						
		Parameters:
			string $path - The path for <Cache::__construct>.
			int $time - (optiona) The amount of time, in seconds, to cache a template.
	*/
	
	public function setCachePath($path, $time = 600)
	{
		$this->cache_path = array($path, $time);
	}
	
	/*
		Method:
			<Templater::activeCache>
		
		Activate cacheing of output. Create the instance of <Cache> that will be used for output, using the <Templater::$cache_path> as array path.
	*/
	
	public function activateCache()
	{
		$this->cache_active = true;
		$this->cache = new Cache($this->cache_path[0], $this->cache_path[1]);
	}
	
	/*
		Method:
			<Templater::toJSON>
		
		Method for converting members to JSON-data. Because json_encode does not properly encode <DomainObject>s.
		
		Parameters:
			$data - The array to convert, will recursively convert data.
		
		Returns:
			The JSON-data
	*/
	
	public function toJSON($data)
	{
		foreach ( $data as $key => $value )
		{
			if ( $value instanceof DomainCollection )
			{
				$data[$key] = $value->getPublicData();
			}
			else if ( $value instanceof DomainObject )
			{
				$data[$key] = $value->getPublicData();
			}
			else if ( is_array($value) )
			{
				$data[$key] = $this->toJSON($value);
			}
			else if ( is_object($value) && method_exists($value, 'toJSON') )
			{
				$data[$key] = $value->toJSON();
			}
		}
		return $data;
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
	
	// Method: <Templater::setLayoutDir>
	// Set the <Templater::$layout_dir>.
	public static function setLayoutDir($dir)
	{
		self::$layout_dir = $dir;
	}
}
