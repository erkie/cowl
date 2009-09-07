<?php

class TPLTemplateNotExistsException extends Exception {}
class TPLShellNotExistsException extends Exception {}

class Templater
{
	protected $vars = array();
	protected $dir = 'templates/';
	protected $template;
	protected $shell;
	protected static $cache_dir = 'tplcache/';
	protected static $base_dir = 'templates/';
	
	public function __construct()
	{
	}
	
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
	
	public function render($filename)
	{
		if ( ! $this->exists($filename) )
		{
			throw new TPLTemplateNotExistsException($this->dir . $filename);
		}
		
		$this->template = $this->dir . $filename;
		if ( ! $this->reloadCache($this->template) )
		{
			extract($this->vars);
			include($this->shell);
		}
		else
		{
			die('reloading cache');
		}
	}
	
	private function reloadCache($filename)
	{
		return false;
	}
	
	public function exists($filename)
	{
		return file_exists($this->dir . $filename);
	}
	
	public function setType($type)
	{
		$name = self::$base_dir . 'shell.' . $type . '.php';
		if ( ! file_exists($name) )
		{
			throw new TPLShellNotExistsException($name);
		}
		$this->shell = $name;
	}
	
	public function setDir($dir)
	{
		$this->dir = $dir;
	}
	
	public static function setBaseDir($dir)
	{
		self::$base_dir = $dir;
	}
	
	public static function setCacheDir($dir)
	{
		self::$cache_dir = $dir;
	}
}
