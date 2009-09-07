<?php

if ( ! defined('COWL_CACHED') )
{
	require('dbresult.php');
	require('datamapper.php');
	require('domainobject.php');
	require('domaincollection.php');
	require('querybuilder.php');
}

class DBConnectionException extends Exception {}
class DBDatabaseSelectException extends Exception {}
class DBQueryException extends Exception {}

class DB
{
	private $conn;

	public function __construct($server, $user, $password, $database)
	{
		$this->connect($server, $user, $password, $database);
	}
	
	public function connect($server, $user, $password, $database)
	{
		if ( ! $this->conn )
		{
			$this->conn = @mysql_connect($server, $user, $password);
			
			if ( ! $this->conn )
			{
				throw new DBConnectionException(mysql_error());
			}
			
			if ( ! mysql_select_db($database) )
			{
				throw new DBDatabaseSelectException(mysql_error());
			}
		}
	}
	
	public function execute($query)
	{
		$args = func_get_args();
		$args = array_slice($args, 1);
		
		$res = $this->query($query, $args);
		
		$result = new DBResult($res);
		$result->setID(mysql_insert_id());
		$result->setAffected(mysql_affected_rows());
		
		return $result;
	}
	
	private function query($query, $args)
	{
		$ret = mysql_query(vsprintf($query, self::sanitize($args)));
		if ( ! $ret )
		{
			throw new DBQueryException(mysql_error());
		}
		return $ret;
	}
	
	private static function sanitize($data)
	{
		if ( is_array($data) )
		{
			foreach ( $data as $key => $value )
			{
				$data[$key] = self::sanitize($value);
			}
			return $data;
		}
		
		if ( get_magic_quotes_gpc() )
		{
			$data = stripslashes($data);
		}
		return mysql_real_escape_string($data);
	}
}
