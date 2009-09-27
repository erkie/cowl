<?php

/*
	Class:
		<Revisions>
	
	Keep track of MySQL revisions contained in a directory. Revisions are named 1.sql, 2.sql, 3.sql, etc.
*/

class Revisions
{
	// Property: <Revisions::$dir>
	// The directory to monitor.
	private $dir;
	
	/*
		Method:
			<Revisions::setDir>
		
		Set <Revisions::$dir>.
		
		Parameters:
			string $dir - The directory in which revisions are contained.
	*/
	
	public function setDir($dir)
	{
		$this->dir = $dir;
	}
	
	/*
		Method:
			<Revisions::update>
		
		Iterately update revisions, starting from (and not including) the $current revision.
		
		Parameters:
			integer $current - The current revision.
		
		Returns:
			The number of the current revision.
	*/
	
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
	
	/*
		Method:
			<Revisions::doRevision>
		
		Fetch contents from $revision and run them. SQL statements are separated by semi-colons (;).
		
		Parameters:
			integer $revision - The revision to update.
		
		Returns:
			False if the revision did not exist, or true if everything went dandy.
	*/
	
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
	
	/*
		Method:
			<Revisions::runStatement>
		
		Run an SQL statement, catching any errors thrown and verbosely outputting them.
		
		Parameters:
			string $statement - The statement to execute.
	*/
	
	private function runStatement($statement)
	{
		try {
			Current::db()->execute($statement);
		} catch ( DBQueryException $e )
		{
			echo '<h1>Versions Error!</h1>';
			echo '<p>' . $e->getMessage() . '<p>';
		}
	}
}
