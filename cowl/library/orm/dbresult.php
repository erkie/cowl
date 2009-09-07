<?php

class DBResult implements Iterator
{
	private $result;
	private $id;
	private $num_rows;
	private $affected;
	private $rows;
	private $position = 0;
	
	public function __construct($result)
	{
		$this->result = $result;
	}
	
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
	
	public function get($index)
	{
		$this->fetch();
		return isset($this->rows[$index]) ? $this->rows[$index] : false;
	}
	
	public function fetchRow()
	{
		$this->fetch();
		return $this->rows[$this->position++];
	}
	
	public function row()
	{
		return mysql_fetch_assoc($this->result);
	}
	
	public function rewind()
	{
		$this->position = 0;
	}
	
	public function current()
	{
		return $this->get($this->position);
	}
	
	public function key()
	{
		return $this->position;
	}
 	
 	public function next()
 	{
 		return $this->fetchRow();
 	}
 	
 	public function valid()
 	{
 		return (bool)$this->current();
 	}
 	
 	public function setPosition($position)
 	{
 		$this->position = $position;
 		return $this;
 	}
 	
	public function setID($id)
	{
		$this->id = $id;
	}
	
	public function setAffected($rows)
	{
		$this->affected = $rows;
	}
	
	public function getID()
	{
		return $this->id;
	}
	
	public function getNumRows()
	{
		if ( is_null($this->num_rows) )
		{
			$this->num_rows = mysql_num_rows($this->result);
		}
		
		return $this->num_rows;
	}
	
	public function getAffected()
	{
		return $this->affected;
	}
}
