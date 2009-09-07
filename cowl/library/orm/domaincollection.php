<?php

/*
	Class:
		<DomainCollection>
	
	General purpose class for a database rowset and lazy instantiaton of <DomainObject>-objects.
*/

class DomainCollection implements Iterator
{
	private $result;
	private $instances = array();
	private $mapper;
	
	public function __construct(DBResult $result, DataMapper $mapper)
	{
		$this->result = $result;
		$this->mapper = $mapper;
	}
	
	public function rewind()
	{
		$this->result->rewind();
	}
	
	public function current()
	{
		return $this->get($this->result->key());
	}
	
	public function key()
	{
		return $this->result->key();
	}
	
	public function next()
	{
		$this->result->next();
		return $this->get($this->result->key());
	}
	
	public function valid()
	{
		return $this->result->valid();
	}
	
	public function count()
	{
		return $this->result->getNumRows();
	}
	
	public function get($index)
	{		
		if ( ! isset($this->instances[$index]) )
		{
			if ( ! $this->result->get($index) )
			{
				return false;
			}
			
			$instance = $this->mapper->createObject();
			$this->mapper->populateFromRow($instance, $this->result->get($index));
			$this->instances[$index] = $instance;
		}
		return $this->instances[$index];
	}
}
