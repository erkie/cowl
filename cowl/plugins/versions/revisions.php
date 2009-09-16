<?php

class Revisions
{
	private $dir;
	
	public function __construct() {}
	
	public function setDir($dir)
	{
		$this->dir = $dir;
	}
	
	public function update($current)
	{
		$next = (! $current) ? 1 : $current + 1;
		
		while ( $this->doRevision($next) )
		{
			$next++;
		}
		
		// Return current
		return $next - 1;
	}
	
	private function doRevision($revision)
	{
		$filename = $this->dir . $revision . '.sql';
		
		if ( ! file_exists($filename) )
		{
			return false;
		}
		
		$statements = explode(';', trim(file_get_contents($filename), ';'));
		
		array_map(array($this, 'runStatement'), $statements);
		
		return true;
	}
	
	private function runStatement($statement)
	{
		try {
			Current::db()->execute($statement);
		} catch ( DBQueryException $e )
		{
			echo '<h1>Versions Error!</h1>';
			echo '<p>' . $e->getMessage() . '<p>';
			
			return false;
		}
	}
}
