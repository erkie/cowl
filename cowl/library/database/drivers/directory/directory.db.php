<?php

class DBQueryException extends Exception {}
class DBQueryNoTypeException extends DBQueryException {}

class DB
{
	public function __construct() {}
	
	public function execute($parameters)
	{
		if ( ! isset($parameters['type']) )
		{
			throw new DBQueryNoTypeException();
		}
		
		switch ( $parameters )
		{
			case 'find':
				
			break;
			
			case 'insert':
			
			break;
			
			case 'update':
			
			break;
			
			case 'delete':
			
			break;
		}
	}
}
