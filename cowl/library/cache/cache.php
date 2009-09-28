<?php

include('phpcache.php');
include('filecache.php');
include('phpfilecache.php');

/*
	Class:
		<Cache>
	
	Keeps track of cached files in a specified directory.
*/

class Cache
{
	// Property: <Cache::$file>
	// The path to the file in <Cache::$cache_dir>
	protected $file;
	
	// Property: <Cache::$extension>
	// The extension of the file. _Very_ optional. You, the user, should not be bothered with the extension, just the contents.
	protected $extension;
	
	// Property: <Cache::$cache_time>
	// How many seconds should the file be cached.
	protected $cache_time;
	
	// Proprety: <Cache::$exists>
	// Boolean value set to true if the cached file exists, else false.
	protected $exists;
	
	// Property: <Cache::$cache_dir>
	// The path of the cache itself. Declared static so it is available over all instances.
	protected static $cache_dir = 'cache/';
	
	/*
		Constructor:
			<Cache::__construct>
		
		Set filename and cache time.
		
		Parameters:
			string $name - The name (or namespace, see <Cache::setFile>) of the file contained in cache dir.
			integer $time - The amount of seconds the file should be cached. Default is 600 seconds (ten minutes).
	*/
	
	public function __construct($name, $time = 600)
	{
		$this->setFile($name);
		$this->cache_time = $time;
	}
	
	/*
		Method:
			<Cache::setDir>
		
		Set the directory in which cached files are contained.
		
		Parameters:
			string $dir - An existing directory with read/write permissions.
	*/
	
	public static function setDir($dir)
	{
		self::$cache_dir = $dir;	
	}

	/*
		Method:
			<Cache::setFile>
		
		Set the name of the cached file.
		
		To make the filename easier to remeber, and separate the path of the cached file from the name of the cached files, periods (.) can be used as path separators. If you feel this is simply moronic you can still use your DIRECTORY_SEPARATOR of choice.
		
		Note that the extension is appended, so to set the extension of the cached files name use <Cache::setExtension>
		
		Examples:
			$cache->setFile('plugins.csscompiler.site');
			-> $cache_dir/plugins/csscompiler/site
			$cache->setFile('plugins/csscompiler/site');
			-> $cache_dir/plugins/csscompiler/site
			// Exactly the same
		
		Parameters:
			string $name - The name of the file.
	*/
	
	protected function setFile($name)
	{
		$name = str_replace('.', DIRECTORY_SEPARATOR, $name);
		$this->file = self::$cache_dir . $name;
		$this->file .= $this->extension;
	}
	
	// Method: <Cache::getFile>
	// Returns: The filename of the cache, <Cache::$file>
	public function getFile()
	{
		return $this->file;
	}
	
	/*
		Method:
			<Cache::setExtension>
		
		Set the extension of the cached files filename. Totally unnecessary.
		
		Parameters:
			$ext - The extension, without a leading period.
	*/
	
	public function setExtension($ext)
	{
		$this->extension = '.' . $ext;
		$this->file .= $this->extension;
	}
	
	/*
		Method:
			<Cache::get>
		
		Returns the contents of the file in cache. Returns false if either the file is outdated, or doesn't exist.
		
		Returns:
			The contents of the cache, only if it's not outdated, else false.
	*/
	
	public function get()
	{
		if ( ! $this->isOutDated() )
		{
			return file_get_contents($this->file . $this->extension);
		}
		
		return false;
	}
	
	/*
		Method:
			<Cache::update>
		
		Update the contents of the cache. This can be done by replacing the contents, or even appending the contents to the end.
		
		Parameters:
			mixed $contents - The contents to be added to the file
			int $flags - The flags. See flags for file_put_contents.
	*/
	
	public function update($contents, $flags = 0)
	{
		if ( ! $this->exists )
		{
			// Create file
			$this->createFile($this->file);
			$this->exists = true;
		}
		echo get_class($this) . ' updated<br />"' . $contents . '"';
		if ( get_class($this) == 'Cache' )
			new Nothing();
		file_put_contents($this->file, $contents, $flags);
	}
	
	/*
		Method:
			<Cache::createFile>
		
		Create a file in the <Cache::$cache_dir>.
		
		Parameters:
			string $name - The name of the file to be created, including <Cache::$cache_dir>
	*/
	
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
	
	/*
		Method:
			<Cache::doesExist>
		
		Checks wether the cache file exists. Because HDD lookups can be slow, the call to file_exists is only invoked if <Cache::$exists> is not set.
		
		Returns:
			True if it exists, else false.
	*/
	
	protected function doesExist()
	{
		if ( is_null($this->exists) )
		{
			$this->exists = file_exists($this->file);
		}
		return $this->exists;
	}
	
	/*
		Method:
			<Cache::isOutDated>
		
		Checks if the file in cache is outdated.
		
		Returns:
			False if the file does not exist, or it has been modified.
	*/
	
	public function isOutDated()
	{
		return ! ($this->doesExist() && filemtime($this->file) > ($_SERVER['REQUEST_TIME'] - $this->cache_time));
	}
}
