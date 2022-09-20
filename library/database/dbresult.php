<?php

abstract class DBResult implements Iterator
{
	abstract public function fetch();
	abstract public function row();
	abstract public function getNumRows();

	// Property: DBResult::$id
	// The ID returned by mysql_insert_id.
	protected $id;

	// Property: DBResult::$num_rows
	// How many results returned, gotten from mysql_num_rows.
	protected $num_rows;

	// Property: DBResult::$affect
	// How many rows affected, returned by mysql_affected_rows.
	protected $affected;

	// Property: DBResult::$rows
	// All rows fetched.
	protected $rows;

	// Property: DBResult::$position
	// For <Iterator>
	protected $position = 0;

	// Method: DBResult::rewind
	// For <Iterator>
	public function rewind(): void
	{
		$this->position = 0;
	}

	// Method: DBResult::current
	// For <Iterator>
	public function current(): mixed
	{
		return $this->get($this->position);
	}

	// Method: DBResult::key
	// For <Iterator>
	public function key(): mixed
	{
		return $this->position;
	}

	// Method: DBResult::next
	// For <Iterator>
	#[\ReturnTypeWillChange]
 	public function next()
 	{
 		return $this->fetchRow();
 	}

 	// Method: DBResult::prev
 	// Returns the previous result.
	 #[\ReturnTypeWillChange]
 	public function prev()
 	{
 		$this->position--;
 		return $this->current();
 	}

	// Method: DBResult::valid
	// For <Iterator>
 	public function valid(): bool
 	{
 		return (bool)$this->current();
 	}

 	/*
 		Method:
 			DBresult::setPosition

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

	/*
		Method:
			DBResult::get

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
			DBResult::fetchRow

		Fetch one row from <DBResult::$rows>, incrementing <DBResult::$position>.

		Warning:
			This will call <DBResult::fetch>
	*/

	public function fetchRow()
	{
		$this->fetch();
		return isset($this->rows[$this->position]) ? $this->rows[$this->position++] : false;
	}

 	// Method: DBResult::setID
 	// Set <DBResult::$id>
	public function setID($id)
	{
		$this->id = $id;
	}

	// Method: DBResult::setAffected
	// Set <DBresult::$affected>
	public function setAffected($rows)
	{
		$this->affected = $rows;
	}

	// Method: DBResult::getID
	// Get <DBResult::$id>
	public function getID()
	{
		return $this->id;
	}

	// Method: DBResult::getAffected
	// Get <DBResult::$affected>
	public function getAffected()
	{
		return $this->affected;
	}
}
