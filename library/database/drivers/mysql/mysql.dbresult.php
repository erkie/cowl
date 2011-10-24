<?php

/*
	Class:
		MySQLDBResult
	
	A wrapper for a resource returned by mysql_query. Acts as an iterator so it can be passed to foreach() to loop through results.
*/

class MySQLDBResult extends DBResult
{
	// Property: DBresult::$result
	// The resource returned by mysql_query.
	private $result;
	
	/*
		Constructor:
			MySQLDBResult::__construct
		
		Parameters:
			$result - The result returned by mysql_query.
	*/
	
	public function __construct($result)
	{
		$this->result = $result;
	}
	
	/*
		Method:
			DBResult::fetch
		
		Return all rows that could be fetched out of mysql_fetch_assoc. This will loop through all results and add to an array, so if you have fetched alot of rows, consider not calling this method.
		
		Returns:
			An array of all the results.
	*/
	
	public function fetch()
	{
		if ( ! $this->rows )
		{
			$rows = array();
			while ( $row = mysql_fetch_assoc($this->result) )
			{
				$rows[] = $row;
			}
			$this->rows = $rows;
		}
		return $this->rows;
	}
	
	/*
		Method:
			MySQLDBResult::row
		
		Fetch next row without looping through every row. Note that <DBResult::row> and <DBResult::fetch> are not compatible as they both increment the result pointer.
		
		Returns:
			The next row.
	*/
	
	public function row()
	{
		return mysql_fetch_assoc($this->result);
	}
	
	/*
		Method:
			MySQLDBResult::getNumRows
		
		Get <DBResult::$num_rows>. If the value is not already set, set it with mysql_num_rows.
	*/
	
	public function getNumRows()
	{
		if ( is_null($this->num_rows) )
		{
			$this->num_rows = mysql_num_rows($this->result);
		}
		
		return $this->num_rows;
	}
}
