<?php

class AuthenticationFailException extends Exception {}
class AuthenticationFileNotFoundException extends AuthenticationFailException {}

class Authentication extends Plugin
{
	private $rights;
	private $rights_file = array();
	
	// Property: <Autentication::$args>
	// Contains the arguments passed to the active command.
	private $args;
	
	public function __construct()
	{
		$this->rights_file = Current::$config->get('plugins.authentication.rights');
		if ( ! file_exists($this->rights_file) )
		{
			throw new AuthenticationFileNotFoundException($this->rights_file);
		}
		$this->rights = parse_ini_file($this->rights_file);
	}
	
	public function hasAccessRights()
	{
		$key = 'access';
		
		foreach ( $this->args as $piece )
		{
			$key .= '.' . $piece;
			if ( isset($this->rights[$key]) )
			{
				$this->enforce($this->rights[$key]);
			}
		}
		
		return true;
	}
	
	public function hasRights()
	{
		return true;
	}
	
	public function enforce($rule)
	{
		$parser = new AuthenticationParser($rule);
		return $parser->run();
	}
	
	public function commandRun($method, $args)
	{
		$this->args = $args['pieces'];
		array_push($this->args, $method);
		
		if ( ! $this->hasAccessRights($args) )
		{
			throw new AuthenticationFailException();
		}
	}
	
	public function dbFind(DataMapper $mapper, $args)
	{
		$class = get_class($mapper);
		
		if ( ! $this->hasRights($class, 'find') )
		{
			throw new AuthenticationFailException();
		}
	}
	
	public function dbInsert(DataMapper $mapper, DomainObject $object)
	{
		
	}
	
	public function dbUpdate(DataMapper $mapper, DomainObject $object)
	{
		
	}
	
	public function dbRemove(DataMapper $mapper, $id)
	{
		
	}
}
