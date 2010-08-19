<?php

class AuthenticationFailException extends Exception
{
	private $keys = array();
	
	public function __construct($keys)
	{
		parent::__construct();
		$this->keys = $keys;
	}
	
	public function getKeys()
	{
		return $this->keys;	
	}
}

class Authentication extends Plugin
{
	private $failures = array();
	
	public function __construct()
	{
		Current::$auth = $this;
	}
	
	public function force($rule, $key)
	{
		$this->enforce($rule, $key);
		$this->runTests();
	}
	
	public function enforce($rule, $key)
	{
		if ( ! $rule )
		{
			$this->failures[] = $key;
		}
	}
	
	public function runTests()
	{
		if ( count($this->failures) )
		{
			throw new AuthenticationFailException($this->failures);
		}
		
		$this->failures = array();
	}
}
