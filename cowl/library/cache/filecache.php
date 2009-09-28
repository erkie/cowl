<?php

/*
	Class:
		<FileCache>
	
	Cache for keeping track of the contents of a file.
*/

class FileCache extends Cache
{
	/*
		Constructor:
			<FileCache__construct>
		
		Does basically the same things as <Cache::__construct>, but sets <Cache::$cache_time> to the modification time of the passed $filename.
		
		Parameters:
			string $name - The name(space) of the cached file.
			string $filename - The file to keep track of.
	*/
	
	public function __construct($name, $filename)
	{
		parent::__construct($name, 0);
		$this->cache_time = filemtime($filename);
	}
	
	/*
		Method:
			<FileCache::isOutDated>
		
		This method does the same things as <Cache::isOutDated>.
		
		Returns:
			True if the cached file doesn't exist, or the modification time of the cached file is larger than the mod. time of the tracked file.
	*/
	
	public function isOutDated()
	{
		return ! $this->doesExist() || filemtime($this->file) < $this->cache_time;
	}
}