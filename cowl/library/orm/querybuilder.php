<?php

class QBInvalidArgumentException extends Exception {}

/*
	Class:
		<QueryBuilder>
	
	Takes a table and primary_key and is used by <DataMapper> to build querys.
*/

class QueryBuilder
{
	// Property: <QueryBuilder::$table>
	// Contains the table for which the particular <QueryBuilder> is pointed to.
	private $table;
	
	// Property: <QueryBuilder::$primary_key>
	// Contains the name of the primary key for the table.
	private $primary_key;
	
	public function __construct($table, $primary_key)
	{
		$this->table = $table;
		$this->primary_key = $primary_key;
	}
	
	public function buildFind($args, $orderby, $offset, $amount)
	{
		$query = sprintf('SELECT * FROM `%s`', $this->table) . PHP_EOL;
		$query .= $this->buildSelectBody($args, $orderby, $offset, $amount);
		
		return $query;
	}
	
	public function query($query, DomainObject $object)
	{
		
	}
	
	public function buildSelect(DomainObject $object)
	{
		$query = sprintf('SELECT * FROM `%s`', $this->table) . PHP_EOL;
		$query .= 'WHERE ';
		
		if ( ! $object->getID() )
		{
			foreach ( $object->fetch() as $key => $value )
			{
				$query .= sprintf('`%s` = %s AND', $key, self::quote($value)) . PHP_EOL;
			}
			$query = substr($query, 0, -strlen(' AND' . PHP_EOL));
		}
		else
		{
			$query .= sprintf(' `%s` = %d', $this->primary_key, $object->getID()) . PHP_EOL;
			$query .= ' LIMIT 1' . PHP_EOL;
		}
		
		return $query;
	}
	
	public function buildSelectBody($args, $orderby, $offset, $amount)
	{
		$query = '';
		
		// Where
		if ( is_array($args) && count($args) )
		{
			$query .= 'WHERE ';
			$args = array_map(array('QueryBuilder', 'quoteValue'), $args);
			foreach ( $args as $key => $val )
			{
				if ( is_array($val) )
				{
					$args[$key] = $key . implode(' AND ' . $key, $val);
				}
				else
				{
					$args[$key] = sprintf('`%s`%s', $key, $val);
				}
			}
			$query .= implode(' AND ', $args) . PHP_EOL;
		}
		elseif ( $args != '*' && $args != 'all' )
		{
			$args = (empty($args)) ? 'none passed' : $args;
			throw new QBInvalidArgumentException($args);
		}
		
		// Order by
		if ( is_array($orderby) )
		{
			$query .= 'ORDER BY ' . implode(', ', array_map(array('QueryBuilder', 'quoteField'), $orderby)) . PHP_EOL;
		}
		elseif (! empty($orderby) )
		{
			$query .= 'ORDER BY ' . $orderby . PHP_EOL;
		}
		
		// Limit
		if ( is_null($amount) && ! is_null($offset) )
		{
			$query .= 'LIMIT ' . $offset . PHP_EOL;
		}
		elseif ( ! is_null($offset) && ! is_null($amount) )
		{
			$query .= 'LIMIT ' . $offset . ', ' . $amount . PHP_EOL;
		}
		
		return $query;
	}
	
	public  function buildInsert(DomainObject $object)
	{
		$query = sprintf('INSERT INTO `%s`', $this->table) . PHP_EOL;
		
		$fields = '(';
		$values = 'VALUES(';
		
		$values_arr = $object->fetch();
		foreach ( $values_arr as $key => $value )
		{
			$fields .= '`' . $key . '`, ';
			$values .= self::quote($value) . ', ';
		}
		
		$fields = substr($fields, 0, -2);
		$values = substr($values, 0, -2);
		
		$fields .= ')';
		$values .= ')';
		
		$query .= $fields . PHP_EOL . $values . PHP_EOL;
		return $query;
	}
	
	public  function buildUpdate(DomainObject $object)
	{
		$query = sprintf('UPDATE `%s`', $this->table) . PHP_EOL;
		$query .= 'SET ';
		foreach ( $object->fetch() as $key => $value )
		{
			$query .= PHP_EOL . sprintf('`%s` = %s, ', $key, self::quote($value));
		}
		$query = substr($query, 0, -2);
		$query .= PHP_EOL . sprintf('WHERE `%s` = %s LIMIT 1', $this->primary_key, self::quote($object->getID()));
		return $query;
	}
	
	public function buildDelete($id)
	{
		if ( $id instanceof DomainObject )
		{
			$id = $id->getID();
		}
		
		$query = sprintf('DELETE FROM `%s` WHERE `%s` = %d LIMIT 1', $this->table, $this->primary_key, $id);
		
		return $query;
	}
	
	public function buildCount($args, $orderby, $offset, $amount)
	{
		$query = sprintf('SELECT COUNT(*) FROM `%s`', $this->table) . PHP_EOL;
		$query .= $this->buildSelectBody($args, $orderby, $offset, $amount);
		
		return $query;
	}
	
	public static function quoteValue($value)
	{
		if ( is_array($value) )
		{
			return array_map(array('QueryBuilder', 'quoteValue'), $value);
		}
		
		if ( ! in_array($value[0], array('=', '<', '>', '!')) )
		{
			return ' = ' . self::quote($value);
		}
		else
		{
			$operator = substr($value, 0, strpos($value, ' '));
			$value = substr($value, strpos($value, ' ') + 1);
			return ' ' . $operator . ' ' . self::quote($value);
		}
	}
	
	public static function quoteField($field)
	{
		return '`' . $field . '`';
	}
	
	public static function quote($value)
	{		
		return (is_numeric($value)) ? $value : sprintf('"%s"', $value);
	}
}
