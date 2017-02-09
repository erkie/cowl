<?php

/*
	Class:
		MySQLDBResult

	A wrapper for a mysqli object. Acts as an iterator so it can be passed to foreach() to loop through results.
*/

class MySQLDBResult extends DBResult
{
	// Property: DBresult::$statement
	// The mysqli_stmt.
	private $statement;

	// Property: MySQLDBResult::$result
	// The mysqli_result object
	private $result;

	/*
		Constructor:
			MySQLDBResult::__construct

		Parameters:
			$statement - The mysqli_stmt object.
	*/

	public function __construct($statement)
	{
		$this->statement = $statement;
		$this->result = $this->statement->get_result();
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
			if (!$this->result)
			{
				return array();
			}

			$rows = array();
			while ( $row = $this->result->fetch_assoc() )
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
		return $this->result->fetch_assoc();
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
			$this->num_rows = $this->result->num_rows;
		}

		return $this->num_rows;
	}
}
