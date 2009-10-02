<?php

/*
	Class:
		<DBResult>
	
	A wrapper for a resource returned by mysql_query. Acts as an iterator so it can be passed to foreach() to loop through results.
*/

class MySQLDBResult extends DBResult
{
	// Property: <DBresult::$result>
	// The resource returned by mysql_query.
	private $result;
	
	// Property: <DBResult::$id>
	// The ID returned by mysql_insert_id.
	private $id;
	
	// Property: <DBResult::$num_rows>
	// How many results returned, gotten from mysql_num_rows.
	private $num_rows;
	
	// Property: <DBResult::$affect>
	// How many rows affected, returned by mysql_affected_rows.
	private $affected;
	
	// Property: <DBResult::$rows>
	// All rows fetched.
	private $rows;
	
	/*
		Constructor:
			<DBResult::__construct>
		
		Parameters:
			$result - The result returned by mysql_query.
	*/
	
	public function __construct($result)
	{
		$this->result = $result;
	}
	
	/*
		Method:
			<DBResult::fetch>
		
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
			<DBResult::get>
		
		Get one result from specified $index.
		
		Parameters:
			integer $index - The index to get.
		
		Returns:
			The row, or false if none was found.
		
		Warning:
			This will call <DBResult::fetch>
	*/
	
	public function get($index)
	{
		$this->fetch();
		return isset($this->rows[$index]) ? $this->rows[$index] : false;
	}
	
	/*
		Method:
			<DBResult::fetchRow>
		
		Fetch one row from <DBResult::$rows>, incrementing <DBResult::$position>.
		
		Warning:
			This will call <DBResult::fetch>
	*/
	
	public function fetchRow()
	{
		$this->fetch();
		return $this->rows[$this->position++];
	}
	
	/*
		Method:
			<DBResult::row>
		
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
 			<DBresult::setPosition>
 		
 		Set <DBResult::$position> at given index.
 		
 		Parameters:
 			integer $position - The new position.
 		
 		Returns:
 			$this for call chaining.
 	*/
 	
 	public function setPosition($position)
 	{
 		$this->position = $position;
 		return $this;
 	}
 	
 	// Method: <DBResult::setID>
 	// Set <DBResult::$id>
	public function setID($id)
	{
		$this->id = $id;
	}
	
	// Method: <DBResult::setAffected>
	// Set <DBresult::$affected>
	public function setAffected($rows)
	{
		$this->affected = $rows;
	}
	
	// Method: <DBResult::getID>
	// Get <DBResult::$id>
	public function getID()
	{
		return $this->id;
	}
	
	/*
		Method:
			<DBResult::getNumRows>
		
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
	
	// Method: <DBResult::getAffected>
	// Get <DBResult::$affected>
	public function getAffected()
	{
		return $this->affected;
	}
}
