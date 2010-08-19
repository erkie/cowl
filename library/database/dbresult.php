<?php

abstract class DBResult implements Iterator
{
	abstract public function get($index);
	abstract public function fetchRow();
	abstract public function row();
	
	// Property: <DBResult::$position>
	// For <Iterator>
	protected $position = 0;
	
	// Method: <DBResult::rewind>
	// For <Iterator>
	public function rewind()
	{
		$this->position = 0;
	}	
	
	// Method: <DBResult::current>
	// For <Iterator>
	public function current()
	{
		return $this->get($this->position);
	}
	
	// Method: <DBResult::key>
	// For <Iterator>
	public function key()
	{
		return $this->position;
	}
	
	// Method: <DBResult::next>
	// For <Iterator>
 	public function next()
 	{
 		return $this->fetchRow();
 	}
 	
 	// Method: <DBResult::prev>
 	// Returns the previous result.
 	public function prev()
 	{
 		$this->position--;
 		return $this->current();
 	}
	
	// Method: <DBResult::valid>
	// For <Iterator>
 	public function valid()
 	{
 		return (bool)$this->current();
 	}
}