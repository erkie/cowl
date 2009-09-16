<?php

include('phpcache.php');
include('filecache.php');
include('phpfilecache.php');

class Cache
{
	protected $file;
	protected $extension;
	protected $cache_time;
	
	protected $exists;
	
	protected static $cache_dir = 'cache/';
	
	public function __construct($name, $time = 600)
	{
		$this->setFile($name);
		$this->cache_time = $time;
	}
	
	public static function setDir($dir)
	{
		self::$cache_dir = $dir;	
	}

	protected function setFile($name)
	{
		$name = str_replace('.', DIRECTORY_SEPARATOR, $name);
		$this->file = self::$cache_dir . $name;
		$this->file .= $this->extension;
	}
	
	public function getFile()
	{
		return $this->file;
	}
	
	public function setExtension($ext)
	{
		$this->extension = '.' . $ext;
		$this->file .= $this->extension;
	}
	
	public function get()
	{
		if ( ! $this->isOutDated() )
		{
			return file_get_contents($this->file . $this->extension);
		}
		
		return false;
	}
	
	public function update($contents, $flags = 0)
	{
		if ( ! $this->exists )
		{
			// Create file
			$this->createFile($this->file);
			$this->exists = true;
		}
		
		file_put_contents($this->file, $contents, $flags);
	}
	
	protected function createFile($name)
	{
		// Get directory part out of name
		$directories = substr($name, 0, strrpos($name, '/'));
		if ( ! is_dir($directories))
		{
			mkdir($directories, 0755, 1);
		}
		
		fclose(fopen($name, 'w'));
	}
	
	protected function doesExist()
	{
		if ( is_null($this->exists) )
		{
			$this->exists = file_exists($this->file);
		}
		return $this->exists;
	}
	
	public function isOutDated()
	{
		return ! ($this->doesExist() && filemtime($this->file) > ($_SERVER['REQUEST_TIME'] - $this->cache_time));
	}
}
