<?php

class Revisions
{
	private $revisions_dir = 'revisions/';
	private $cache = 'cache/';
	
	public function __construct() {}
	
	public function setDir($revisions_dir)
	{
		$this->revisions_dir = $revisions_dir;
	}
	
	public function setCache($cache)
	{
		if ( ! file_exists($cache) )
		{
			// create file
			fclose(fopen($cache, 'x+'));
		}
		$this->cache = $cache;
	}
	
	public function uptodate()
	{
		$current = file_get_contents($this->cache);
		$diff = $this->getDiff($current);
		
		if ( count($diff) )
		{
			foreach ( $diff as $revision )
			{
				$this->loadRevision($revision);
			}
			
			file_put_contents($this->cache, $revision);
		}
	}
	
	public function loadRevision($rev)
	{
		//echo $rev, '<br />';
	}
	
	private function getDiff($current)
	{		
		// Fetch revisionfiles filter out . and .., and sort in a natural order
		$revs = scandir($this->revisions_dir);
		foreach ( $revs as $key => $rev )
		{
			if ( $rev == '.' || $rev == '..' )
			{
				unset($revs[$key]);
			}
		}
		natsort($revs);
		return array_slice($revs, array_search($current, $revs));
	}
	
	private function parseRevision()
	{
		
	}
}