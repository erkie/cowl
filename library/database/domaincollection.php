<?php

/*
	Class:
		<DomainCollection>
	
	General purpose class for a database rowset and lazy instantiaton of <DomainObject>-objects.
*/

class DomainCollection implements Iterator, Countable
{
	// Property: <DomainCollection::$result>
	// Holds the instance of <DBResult> to interface.
	private $result;
	
	// Property: <DomainCollection::$instances>
	// Holds the instances of <DomainObjects>
	private $instances = array();
	
	// Property: <DomainCollection::$mapper>
	// The mapper used to create <DomainObject>s.
	private $mapper;
	
	/*
		Constructor:
			<DomainCollection::__construct>
		
		Paramaters:
			DBResult $result - The result to interface with.
			DataMapper $mapper - The mapper used to create <DomainObject>s.
	*/
	
	public function __construct(DBResult $result, DataMapper $mapper)
	{
		$this->result = $result;
		$this->mapper = $mapper;
	}
	
	// Method: <DomainCollection::rewind>
	// For <Iterator>
	public function rewind()
	{
		$this->result->rewind();
	}
	
	// Method: <DomainCollection::current>
	// For <Iterator>
	public function current()
	{
		return $this->get($this->result->key());
	}
	
	// Method: <DomainCollection::key>
	// For <Iterator>
	public function key()
	{
		return $this->result->key();
	}
	
	// Method: <DomainCollection::next>
	// For <Iterator>
	public function next()
	{
		$this->result->next();
		return $this->get($this->result->key());
	}
	
	// Method: <DomainCollection::prev>
	// Interfaces <DBResult::prev>
	public function prev()
	{
		$this->result->prev();
		return $this->current();
	}
	
	// Method: <DomainCollection::valid>
	// For <Iterator>
	public function valid()
	{
		return $this->result->valid();
	}
	
	/*
		Method:
			<DomainCollection::count>
		
		Get amount of results. As <DomainCollection> implements <Countable> the standard PHP function <count> can be used on a <DomainCollection>-object.
		
		Returns:
			The number of rows as an integer.
	*/
	
	public function count()
	{
		return $this->result->getNumRows();
	}
	
	/*
		Method:
			<DomainCollection::get>
		
		Get an instance at specified index.
		
		Parameters:
			integer $index - The index to fetch.
		
		Returns:
			The <DomainObject> correspondning to the $index.
	*/
	
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
	
	/*
		Method:
			<DomainCollection::indexOf>
		
		Searches for the index of the passed <DomainObject>.
		
		Parameters:
			DomainObject $object - The object to search for
		
		Returns:
			The index of $object, else false.
	*/
	
	public function indexOf(DomainObject $object)
	{
		$i = 0;
		foreach ( $this as $key => $o )
		{
			if ( $object == $o )
			{
				return $i;
			}
			$i++;
		}
		return false;
	}
	
	/*
		Method:
			<DomainCollection::combine>
		
		Combine this <DomainCollection> with another iterable object (or array).
		
		Parameters:
			$res - The other iterable object (or array) to combine with
		
		Returns:
			The array containing $this and $res.
	*/
	
	public function combine($res)
	{
		$new = array();
		foreach ( $this as $value )
		{
			$new[] = $value;
		}
		
		foreach ( $res as $value )
		{
			$new[] = $value;
		}
		return $new;
	}
	
	/*
		Method:
			<DomainCollection::getData>
		
		Returns the data of all <DomainObject>:s contained as an array.
	*/
	
	public function getData()
	{
		$ret = array();
		foreach ( $this as $key => $value )
		{
			$ret[$key] = $value->getData();
		}
		return $ret;
	}
}
