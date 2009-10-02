<?php

class DirectoryDBQueryException extends Exception {}
class DirectoryDBQueryNoTypeException extends DBQueryException {}

class DirectoryDB
{
	public function __construct() {}
	
	public function execute($parameters)
	{
		if ( ! isset($parameters['type']) )
		{
			throw new DirectoryDBQueryNoTypeException();
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
