<?php

/*
	Class:
		<PHPFileCache>
	
	A merge of a <PHPCache> and a <FileCache>.
*/

class PHPFileCache extends PHPCache
{
	// See <FileCache::__construct> for more details.
	public function __construct($name, $filename)
	{
		parent::__construct($name, 0);
		$this->cache_time = filemtime($filename);
	}
	
	// See <FileCache::isOutDated> for more details.
	public function isOutDated()
	{
		return ! $this->doesExist() || filemtime($this->file) < $this->cache_time;
	}
}
