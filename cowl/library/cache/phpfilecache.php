<?php

class PHPFileCache extends PHPCache
{
	public function __construct($name, $filename)
	{
		parent::__construct($name, 0);
		$this->cache_time = filemtime($filename);
	}
	
	public function isOutDated()
	{
		return ! ($this->doesExist() && filemtime($this->file) - $this->cache_time > 0);
	}
}
