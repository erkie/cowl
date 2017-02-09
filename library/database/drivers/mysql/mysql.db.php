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
	// Holds the connection instance.
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
			$this->conn = new mysqli($server, $user, $password, $database);
			if ( $this->conn->connect_errno > 0 )
			{
				throw new MySQLDBConnectionException($this->conn->connect_error);
			}
		}
	}

	/*
		Method:
			<MySQLDB::execute>

		Execute a query.

		Parameters:
			string $query - The query to execute.

		Returns:
			An instance to <DBResult> with the insert ID and affected rows.
	*/

	public function execute($query)
	{
		$this->startTimer();
		$statement = $this->query($query);
		$this->endTimer();

		$result = new MySQLDBResult($statement);
		$result->setID($this->conn->insert_id);
		$result->setAffected($this->conn->affected_rows);

		return $result;
	}

	/*
		Method:
			<MySQLDB::query>

		Execute a query, throwing a DBQueryExecption on failure. Sanitizes input.

		Parameters:
			string $query - The query to execute.
	*/

	private function query($query_object)
	{
    $query = $query_object->getQuery();
    $args = $query_object->getArgs();

		if ( $this->output_query )
		{
			var_dump($query);
			$this->output_query = false;
		}

    $statement = $this->conn->prepare($query);
    if ( ! $statement )
		{
			throw new MySQLDBQueryException($this->conn->error . ': ' . $query);
		}

    if ( count($args) > 0 )
    {
      $bind_args = array('');

      foreach ($args as $arg)
      {
        $bind_args[0] .= $arg[0];
        $bind_args[] = &$arg[1];
      }

      call_user_func_array(array($statement, 'bind_param'), $bind_args);
    }

		$success = $statement->execute();
		if ( ! $success )
		{
			throw new MySQLDBQueryException($this->conn->error . ': ' . $query);
		}
		return $statement;
	}
}
