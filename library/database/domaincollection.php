<?php

/*
	Class:
		DomainCollection

	General purpose class for a database rowset and lazy instantiaton of <DomainObject>-objects.

	This class has a special state when serialized. When serialized it loses everything it knows
	about where it came from and any database related data. It becomes just an array of
	<DomainObject>s.
*/

class DomainCollection implements Iterator, Countable
{
	// Property: DomainCollection::$result
	// Holds the instance of <DBResult> to interface.
	private $result;

	// Property: DomainCollection::$instances
	// Holds the instances of <DomainObjects>
	private $instances = array();

	// Property: DomainCollection::$mapper
	// The mapper used to create <DomainObject>s.
	private $mapper;

	/*
		Constructor:
			DomainCollection::__construct

		Paramaters:
			DBResult $result - The result to interface with.
			DataMapper $mapper - The mapper used to create <DomainObject>s.
	*/

	public function __construct(DBResult $result, DataMapper $mapper)
	{
		$this->result = $result;
		$this->mapper = $mapper;
	}

	public function __sleep()
	{
		// Make sure all instances are loaded before saving
		$this->getData();
		return array('instances');
	}

	public function __wakeup()
	{
		$this->result = false;
		$this->mapper = false;
	}

	// Method: DomainCollection::rewind
	// For <Iterator>
	public function rewind(): void
	{
		if ( ! $this->result )
		{
			reset($this->instances);
			return;
		}

		$this->result->rewind();
	}

	// Method: DomainCollection::current
	// For <Iterator>
	public function current(): mixed
	{
		if ( ! $this->result )
			return current($this->instances);

		return $this->get($this->result->key());
	}

	// Method: DomainCollection::key
	// For <Iterator>
	#[\ReturnTypeWillChange]
	public function key()
	{
		if ( ! $this->result )
			return key($this->instances);
		return $this->result->key();
	}

	// Method: DomainCollection::next
	// For <Iterator>
	#[\ReturnTypeWillChange]
	public function next()
	{
		if ( ! $this->result )
			return next($this->instances);
		$this->result->next();
		return $this->get($this->result->key());
	}

	// Method: DomainCollection::prev
	// Interfaces <DBResult::prev>
	public function prev()
	{
		if ( ! $this->result )
			return prev($this->instances);
		$this->result->prev();
		return $this->current();
	}

	// Method: DomainCollection::valid
	// For <Iterator>
	public function valid(): bool
	{
		if ( ! $this->result )
		{
			$key = key($this->instances);
	        return $key !== null && $key !== false;
		}
		return $this->result->valid();
	}

	/*
		Method:
			DomainCollection::count

		Get amount of results. As <DomainCollection> implements <Countable> the standard PHP function <count> can be used on a <DomainCollection>-object.

		Returns:
			The number of rows as an integer.
	*/

	public function count(): int
	{
		if ( ! $this->result )
			return count($this->instances);
		return $this->result->getNumRows();
	}

	/*
		Method:
			DomainCollection::get

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
			if ( ! $this->result || ! $this->result->get($index) )
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
			DomainCollection::first

		Convenience method for getting the first object contained.

		Returns:
			The first <DomainObject> in this collection.
	*/

	public function first()
	{
		return $this->get(0);
	}

	/*
		Method:
			DomainCollection::last

		Same as <DomainCollection::first>, but for the last element.
	*/

	public function last()
	{
		return $this->get($this->count()-1);
	}

	/*
		Method:
			DomainCollection::indexOf

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
			DomainCollection::combine

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
			DomainCollection::getData

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

	/*
		Method:
			DomainCollection::getPublicData

		Returns all public data from a DomainObject. See <DomainObject::getPublicData> for more
		information about whats public.
	*/

	public function getPublicData()
	{
		$ret = array();
		foreach ( $this as $key => $value )
		{
			$ret[$key] = $value->getPublicData();
		}
		return $ret;
	}

	/*
		Method:
			DomainCollection::toArray

		Transform data to array. Works the same as <DomainCollection::getData>.
		But it can also take a key that you want to retrieve.

		For example:
			(code)

			// Return a DomainCollection where each result only contains an id key
			$data = $mapper->query('select id from table where param = 1');

			// To turn it into a single-dimension array like so:
			// array(1, 2, 3, 4);
			$arr = $data->toArray('id');

			(end code)
	*/

	public function toArray($key = false)
	{
		if ( ! $key )
		{
			return $this->getData();
		}

		$ret = array();
		foreach ( $this as $d )
			$ret[] = $d->$key;

		return $ret;
	}

	/*
		Method:
			DomainCollection::findBy

		Find an entry in the DomainCollection by specified key and value.

		Example:
			(code)

			$collection = new DomainCollection();
			$obj = $collection->findBy('id', 10); // Fetch the DomainObject with id 10

			(end code)

		Parameters:
			(string) $key - The key to match agains
			(mixed) $value - The value to match agains

		Returns:
			The <DomainObject> with the value, or false if not found.
	*/

	public function findBy($key, $value)
	{
		foreach  ( $this as $obj )
		{
			if ( $obj->$key == $value )
			{
				return $obj;
			}
		}
		return false;
	}
}
