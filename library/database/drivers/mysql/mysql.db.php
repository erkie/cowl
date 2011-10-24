<?php

class MySQLDBConnectionException extends Exception {}
class MySQLDBDatabaseSelectException extends Exception {}
class MySQLDBQueryException extends Exception {}

/*
	Class:
		<DB>
	
	MySQL database wrapper with built in data sanitation.
*/

class MySQLDB extends DBDriver
{
	// Property: <DataMapper::$output_query>
	// Output the current query using <var_dump>. Debug-flag, will be set to false after use.
	public $output_query = false;

	// Property: <MySQLDB::$conn>
	// Holds the connection ID returned by mysql_query.
	private $conn;

	/*
		Constructor:
			<MySQLDB::__construct>
		
		Connect to MySQL server. This should ideally only be created once, so be sure to keep track of all instances created.
		
		Parameters:
			See <MySQLDB::connect> parameter list.
	*/
	
	public function __construct($server, $user, $password, $database)
	{
		$this->connect($server, $user, $password, $database);
	}
	
	/*
		Method:
			<MySQLDB::connect>
		
		Create connection to server. Will throw a <DBConnectionException> if there was a problem connecting. Will throw a <DBDatabaseSelectException> will be thrown if the chosen database could not be selected.
		
		Parameters:
			string $server - The server to connect to.
			string $user - The user for which the connection is owned.
			string $password - Super secret password.
			string $database - The name of the database to connect to.
	*/
	
	private function connect($server, $user, $password, $database)
	{
		if ( ! $this->conn )
		{
			$this->conn = @mysql_connect($server, $user, $password);
			
			if ( ! $this->conn )
			{
				throw new MySQLDBConnectionException(mysql_error());
			}
			
			if ( ! mysql_select_db($database) )
			{
				throw new MySQLDBDatabaseSelectException(mysql_error());
			}
		}
	}
	
	/*
		Method:
			<MySQLDB::execute>
		
		Execute a query. All input variables are sanitized by <DB::sanitize>.
		
		Parameters:
			string $query - The query to execute.
			mixed $arg1 - An argument to be inserted into the query.
			mixed $argN - ...
		
		Returns:
			An instance to <DBResult> with the insert ID and affected rows.
	*/
	
	public function execute($query)
	{
		$args = func_get_args();
		$args = array_slice($args, 1);
		
		$this->startTimer();
		$res = $this->query($query, $args);
		$this->endTimer();
		
		$result = new MySQLDBResult($res);
		$result->setID(mysql_insert_id());
		$result->setAffected(mysql_affected_rows());
		
		return $result;
	}
	
	/*
		Method:
			<MySQLDB::query>
		
		Execute a query, throwing a DBQueryExecption on failure. Sanitizes input.
		
		Parameters:
			string $query - The query to execute.
			array $args - An array of arguments to be passed to the query.
		
		Returns:
			The result ID returned by mysql_query.
	*/
	
	private function query($query, $args)
	{
		$query = $query;
		if ( $this->output_query )
		{
			var_dump($query);
			$this->output_query = false;
		}
		
		$ret = mysql_query($query);
		if ( ! $ret )
		{
			throw new MySQLDBQueryException(mysql_error());
		}
		return $ret;
	}
	
	/*
		Method:
			<MySQLDB::sanitize>
		
		Sanitize input by removing slashes and mysql_real_escape_string:ing. This is done recursively on an array.
		
		Parameters:
			mixed $data - The data to sanitize. If $data is an array it will be recursively sanitized.
	*/
	
	private static function sanitize($data)
	{
		// Recursively sanitize arrays.
		if ( is_array($data) )
		{
			foreach ( $data as $key => $value )
			{
				$data[$key] = self::sanitize($value);
			}
			return $data;
		}
		
		// Remove slashes added by PHP
		if ( get_magic_quotes_gpc() )
		{
			$data = stripslashes($data);
		}
		return mysql_real_escape_string($data);
	}
}
