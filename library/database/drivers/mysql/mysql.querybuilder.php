<?php

class MySQLQBInvalidArgumentException extends Exception {}
class MySQLQBInvalidFormatModifierException extends Exception {}
class MySQLQBFormatValueNotSpecifiedException extends Exception {}

/*
	Class:
		MySQLQueryBuilder
	
	Takes a table and primary_key and is used by <DataMapper> to build querys.
*/

class MySQLQueryBuilder
{
	// Property: QueryBuilder::$table
	// Contains the table for which the particular <QueryBuilder> is pointed to.
	private $table;
	
	// Property: QueryBuilder::$primary_key
	// Contains the name of the primary key for the table.
	private $primary_key;
	
	// Property: QueryBuilder::$prefix
	// The prefix used for the table name in queries. Default value is p, p for prefix!
	private $prefix = 'p';
	
	/*
		Method:
			QueryBuilder::__construct
		
		Initialized <QueryBuilder::$table> and <QueryBuilder::$primary_key>.
		
		Paramaters:
			$table - The name of table we will be working with.
			$primary_key - The primary key used by the table.
	*/
	
	public function __construct($table, $primary_key)
	{
		$this->table = $table;
		$this->primary_key = $primary_key;
	}
	
	/*
		Method:
			QueryBuilder::buildFind
		
		Builds a SELECT-statement.
		
		Parameters:
			$args - The arguments used for filtering the results, i.e. the arguments for the WHERE-statement. See <QueryBuilder::buildWhere> for further details.
			$orderby - Data for the ORDER BY clause. See <QueryBuilder::buildOrderBy> for further details.
			$offset - The offset for the LIMIT. See <QueryBuilder::buildLimit> for further details.
			$amount - The amount for the LIMIT. See <QueryBuilder::buildLimit> for further details.
	*/
	
	public function buildFind($args, $orderby, $offset, $amount)
	{
		$query = sprintf('SELECT %s.* FROM `%s` as %s', $this->prefix, $this->table, $this->prefix) . PHP_EOL;
		$query .= $this->buildSelectBody($args, $orderby, $offset, $amount);
		
		return $query;
	}
	
	/*
		Method:
			QueryBuilder::format
		
		An advanced sprintf, sort of.
		
		Many times the built in methods of <QueryBuilder> are not enough for advanced queries. If you want to write your own queries (preferably in your <DataMapper>-classes) but still want to be able to use some of the facilities brought to you by <QueryBuilder, <QueryBuilder::format> is right for you.
		
		The formatted values are contained in %(key), where "key" is a key in the $values array. The "key"
		can also be a question mark (?), i.e %(?), this will treat the $values array in a numeric order.
		
		Values can use modifiers. They look like %(modifier->key), where modifier is one of the following:
		
		* quote: Will be modified by <QueryBuilder::quote>
		* value: Will be modified by <QueryBuilder::quouteValue>
		* field: Will be modified by <QueryBuilder::quoteField>
		* string: Will be quoted as a string
		
		Examples:
			$qb->format('
				SELECT * FROM %(table)
				WHERE %(my_field|field) %(my_value|safe)
			', array(
				'my_field' => 'auth_level',
				'my_value >' => '3'
			));
			
			// Will result in:
			"SELECT * FROM users AS us
			WHERE us.`auth_level` > 3"
			
		
		Parameters:
			$query - The string to be formatted.
			$values - An array containing the key => value-pairs to be replaced with.
		
		Returns:
			The formatted string.
	*/
	
	public function format($query, $values)
	{
		$this->values = array_merge($values, array(
			'table' => $this->table,
			'primary_key' => $this->primary_key,
			'prefix' => $this->prefix
		));
		
		$query = preg_replace_callback('/%\(([^)]+)\)/', array($this, 'formatCallback'), $query);
		return $query;
	}
	
	/*
		Method:
			QueryBuilder::formatCallback
		
		Callback used by the preg_replace in <QueryBuilder::format>. See <QueryBuilder::format> for formatting rules.
		
		Paramaters:
			$match - The match passed by preg_replace_callback.
		
		Returns:
			The value taken out of $this->values.
	*/
	
	private function formatCallback($match)
	{
		// Set and ensure key
		$key = $value = $match[1];
		
		if ( strstr($key, '->') )
		{
			$pieces = explode('->', $key);
			$key = array_pop($pieces);
		}
		else if ( strstr($key, '|') )
		{
			$pieces = explode('|', $key);
			$key = array_shift($pieces);
		}
		
		if ( $key == '?' )
		{
			$value = array_shift($this->values);
		}
		elseif ( ! in_array(substr($key, 0, 1), array('"', "'")) && ! in_array(substr($key, -1), array('"', "'")) )
		{
			if ( ! isset($this->values[$key]) )
			{
				throw new MySQLQBFormatValueNotSpecifiedException($key);
			}
		
			// Process value
			$value = $this->values[$key];
		}
		else
		{
			$value = substr(substr($key, 1), 0, -1);
		}
		
		if ( isset($pieces) )
		{
			foreach ( $pieces as $piece )
			{
				switch ( $piece )
				{
					case 'quote': $value = $this->quote($value); break;
					case 'value': $value = $this->quoteValue($value); break;
					case 'field': $value = $this->quoteField($value); break;
					case 'string': $value = self::escape($value); break;
					case 'safe': $value = $this->quote($value); break;
					case 'this': $value = $this->$value; break;
					default: throw new MySQLQBInvalidFormatModifierException(implode(' ', $pieces)); break;
				}
			}
		}
		
		return $value;
	}
	
	/*
		Method:
			QueryBuilder::buildFindObject
		
		Build a query using information from a passed <DomainObject>. If the <DomainObject> has set an ID, that will be the WHERE parameter. Otherwise all values of the <DomainObject> that do exist will be used for the WHERE clause.
		
		Parameters:
			DomainObject $object
	*/
	
	public function buildFindObject(DomainObject $object)
	{
		$query = sprintf('SELECT * FROM `%s` as %s', $this->table, $this->prefix) . PHP_EOL;
		$query .= 'WHERE ';
		
		if ( $object->getID() === false )
		{
			$pairs = $object->fetch();
			if ( count($pairs) )
			{
				foreach ( $object->fetch() as $key => $value )
				{
					$query .= sprintf('%s.`%s` = %s AND', $this->prefix, $key, $this->quote($value)) . PHP_EOL;
				}
				$query = substr($query, 0, -strlen(' AND' . PHP_EOL));
			}
		}
		else
		{
			$query .= sprintf(' %s = %d', $this->quoteField($this->primary_key), $object->getID()) . PHP_EOL;
			$query .= ' LIMIT 1' . PHP_EOL;
		}
		
		return $query;
	}
	
	/*
		Method:
			QueryBuilder::buildSelectBody
		
		Builds the SELECT body. This includes WHERE, ORDER BY and LIMIT clauses.
		
		Parameters:
			$args - See <QueryBuilder::buildWhere> for further details.
			$orderby - Data for the ORDER BY clause. See <QueryBuilder::buildOrderBy> for further details.
			$offset - The offset for the LIMIT. See <QueryBuilder::buildLimit> for further details.
			$amount - The amount for the LIMIT. See <QueryBuilder::buildLimit> for further details.
	*/
	
	public function buildSelectBody($args, $orderby, $offset, $amount)
	{
		$query = '';
		
		$query .= $this->buildWhere($args);		
		$query .= $this->buildOrderBy($orderby);		
		$query .= $this->buildLimit($offset, $amount);
		
		return $query;
	}
	
	/*
		Method:
			QueryBuilder::buildWhere
		
		Build the WHERE clause for an SQL query. The following rules are used while constructing the WHERE:
		
		* If $args is an array and empty, an empty string will be returned.
		* If $args is something else, not including the strings "all" or "*": an empty string is returned.
		* If $args is a non-empty array:
			* Recursively quote every value appropriately.
			* If any value is an array, join them together with AND
			* else put together the key and value.
		
		Parameters:
			$args - Must be either an array or one of the following strings: "all" or "*".
	*/
	
	public function buildWhere($args)
	{
		$query = '';
		if ( is_array($args) && count($args) )
		{
			$query .= 'WHERE ';
			$args = array_map(array($this, 'quoteValue'), $args);
			
			foreach ( $args as $key => $val )
			{
				if ( is_array($val) )
				{
					$args[$key] = $key . implode(' AND ' . $key, $val);
				}
				else
				{
					$args[$key] = sprintf('%s%s', $this->quoteStatement($key), $val);
				}
			}
			$query .= implode(' AND ', $args) . PHP_EOL;
		}
		elseif ( $args != '*' && $args != 'all' )
		{
			throw new MySQLQBInvalidArgumentException((empty($args)) ? 'none passed' : $args);
		}
		return $query;
	}
	
	/*
		Method:
			QueryBuilder::buildLimit
		
		Build the LIMIT clause for an SQL query. The methods parameters work the same way as they do in an SQL LIMIT clause.
		
		Parameters:
			$offset - The offset of the LIMIT
			$amount - The amount of results to returned.
	*/
	
	public function buildLimit($offset, $amount)
	{
		$query = '';
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
	
	/*
		Method:
			QueryBuilder::buildOrderBy
		
		Build an ORDER BY clause for an SQL statement. If an array is passed I will join them together will commas (,). All fields are quoted with <QueryBuilder::quoteField>.
		
		Parameters:
			$orderby - Either an array or string. The value used for the ORDER BY.
	*/
	
	public function buildOrderBy($orderby)
	{
		$query = '';
		if ( is_array($orderby) )
		{
			$query .= 'ORDER BY ' . implode(', ', array_map(array($this, 'quoteStatement'), $orderby)) . PHP_EOL;
		}
		elseif (! empty($orderby) )
		{
			$query .= 'ORDER BY ' . $this->quoteStatement($orderby) . PHP_EOL;
		}
		return $query;
	}
	
	/*
		Method:
			QueryBuilder::buildInsert
		
		Build an INSERT clause for an SQL query, using fields from the passed <DomainObject>.
		
		Parameters:
			DomainObject $object - The object to be inserted.
	*/
	
	public  function buildInsert(DomainObject $object)
	{
		$query = sprintf('INSERT INTO `%s`', $this->table) . PHP_EOL;
		
		// Create both (...values...) and VALUES(...field names...)
		// At the same time
		$fields = '(';
		$values = 'VALUES(';
		
		$values_arr = $object->fetch();
		foreach ( $values_arr as $key => $value )
		{
			$fields .= '`' . $key . '`, ';
			$values .= $this->quote($value) . ', ';
		}
		
		// Remove last comma and space
		$fields = substr($fields, 0, -2);
		$values = substr($values, 0, -2);
		
		$fields .= ')';
		$values .= ')';
		
		$query .= $fields . PHP_EOL . $values . PHP_EOL;
		return $query;
	}
	
	
	/*
		Method:
			QueryBuilder::buildUpdate
		
		Build an UPDATE clause for an SQL query. All the passed values contained in the passed <DomainObject> will be updated.
		
		Parameters:
			DomainObject $object - The object to be updated. _Must_ have an ID specified.
	*/
	
	public  function buildUpdate(DomainObject $object)
	{
		$values = $object->fetchDirty();
		
		if ( ! count($values) )
			return false;
		
		$query = sprintf('UPDATE `%s` AS %s', $this->table, $this->prefix) . PHP_EOL;
		
		$query .= 'SET ';
		foreach ( $values as $key => $value )
		{
			$query .= PHP_EOL . sprintf('%s = %s, ', $this->quoteField($key), $this->quote($value));
		}
	
		// Remove last ", "
		$query = substr($query, 0, -2);
		
		$query .= PHP_EOL . sprintf('WHERE %s = %s LIMIT 1', $this->quoteField($this->primary_key), $this->quote($object->getID()));
		return $query;
	}
	
	/*
		Method:
			QueryBuilder::buildDelete
		
		Build a DELETE statement.
		
		Parameters:
			$id - Can be either an $id or a <DomainObject>. If a <DomainObject> is passed it _must_ have an ID specified.
	*/
	
	public function buildDelete($id)
	{
		if ( $id instanceof DomainObject )
		{
			$id = $id->getID();
		}
		
		$query = sprintf('DELETE FROM `%s` WHERE `%s` = %d LIMIT 1', $this->table, $this->primary_key, $id);
		
		return $query;
	}
	
	/*
		Method:
			QueryBuilder::buildCount
		
		Build a statement that counts the row in the table. See <QueryBuilder::buildSelectBody> for details about the WHERE clauses, etc.
	*/
	
	public function buildCount($args, $orderby, $offset, $amount)
	{
		$query = sprintf('SELECT COUNT(*) FROM `%s` as %s', $this->table, $this->prefix) . PHP_EOL;
		$query .= $this->buildSelectBody($args, $orderby, $offset, $amount);
		
		return $query;
	}
	
	/*
		Method:
			QueryBuilder::setPrefix
		
		Set <QueryBuilder::$prefix>.
		
		Parameters:
			$prefix - Can be any valid prefix name for SQL.
	*/
	
	public function setPrefix($prefix)
	{
		$this->prefix = $prefix;
		return $this;
	}
	
	// Utility functions
	
	/*
		Method:
			QueryBuilder::quoteField
		
		Quote a field to fit with as an SQL field. Add backticks to it and add the prefix.
		
		Examples:
			// prefix is "p"
			$qb->quoteField('name');
			> p.`name`
		
		Paremeters:
			$field - The field name
		
		Returns: 
			The quoted field name
	*/
	
	public function quoteField($field)
	{
		return $this->prefix . '.`' . $field . '`';
	}
	
	/*
		Method:
			QueryBuilder::quoteStatement
		
		Appropriately quote a statement using backticks. Don't use prefixes ("p.field_name")
		
		Examples:
			$qb->quoteStatement('name');
			> `name`
			$qb->quoteStatement('name DESC');
			> `name` DESC
			$qb->quoteStatement('points >');
			> `points` > 
		
		Parameters:
			$field - The value to be quoted.
		
		Returns:
			The quoted value.
	*/
	
	public function quoteStatement($field)
	{
		$pieces = explode(' ', trim($field));
		
		if ( count($pieces) == 1 )
		{
			return $this->quoteField($pieces[0]) . ' = ';
		}
		
		return $this->quoteField($pieces[0]) . ' ' . $pieces[1] . ' ';
	}
	
	/*
		Method:
			QueryBuilder::quoteValue
		
		Just quote a value so it's not SQL injectable. Takes an array and recursively quotes them
		
		Examples:
			$qb->quoteValue('Mozilla just released it's new project Snowl, Cowl sues.');
			> "Mozilla just released it's new project Snowl, Cowl sues."
			$qb->quoteValue(10);
			> 10
			$qb->quoteValue('10');
			> "10"
			$qb->quoteValue('foobar');
			> "foobar"
		
		Parameters:
			$value - The value to transform.
		
		Returns:
			The transformed value. See examples for results.
	*/
	
	public function quoteValue($value)
	{
		// Recurse to all values
		if ( is_array($value) )
		{
			return array_map(array($this, 'quoteValue'), $value);
		}
		
		return $this->quote($value);
	}
	
	/*
		Method:
			QueryBuilder::quote
		
		Adds quotes to the passed value if anything but numerical. If the value is numerical it is left unchanged.
		
		Examples:
			$qb->quote('Hello world!');
			-> '"Hello world!"'
			$qb->quote(10);
			-> '10'
		
		Parameters:
			$value - The value to quote.
		
		Returns:
			The quoted value.
	*/
	
	public function quote($value)
	{
		return (is_int($value) || is_float($value)) ? $value : sprintf('"%s"', self::escape($value));
	}
	
	public static function escape($str)
	{
		return mysql_real_escape_string($str);
	}
}
