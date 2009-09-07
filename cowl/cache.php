<?php

class Cache
{
	private $file;
	private $cache_time;
	
	public function __construct($filename, $time = 600)
	{
		$this->file = $filename;
		$this->cache_time = $time;
		
		$this->exists = file_exists($this->file);
	}
	
	public function getContents()
	{
		return file_get_contents($this->file);
	}
	
	public function update($contents, $flags = 0)
	{
		if ( ! $this->exists )
		{
			fclose(fopen($this->file, 'w'));
			$this->exists = true;
		}
		
		file_put_contents($this->file, $contents, $flags);
	}
	
	public function isOutDated()
	{
		return !($this->exists && filemtime($this->file) > ($_SERVER['REQUEST_TIME'] - $this->cache_time));
	}
}
