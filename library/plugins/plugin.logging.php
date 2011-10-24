<?php

/*
	Class:
		Logging
	
	Plugin for logging everything related to your site. Command-related, database queries,
	exceptions, etc.
*/

class Logging extends Plugin
{
	public function __construct()
	{
		$this->messages = array();
		$this->file = COWL_TOP . sprintf(Current::$config->get("plugins.logging.log_file"), date("Ymd"));
	}
	
	private function log($type, $message = '--')
	{
		$message = sprintf("%-12s = %s", $type, $message);
		$this->messages[] = $message;
	}
	
	
	public function postPathParse($args)
	{
		$this->log("request", $_SERVER['REQUEST_URI']);
	}
		
	// FrontController-related hooks
	public function postRun()
	{
		file_put_contents($this->file, implode(PHP_EOL, $this->messages) . PHP_EOL, FILE_APPEND);
	}
	
	public function preStaticServe(StaticServer $server) {}
	public function postStaticServe(StaticServer $server) {}
	
	// Command-related hooks
	public function commandRun(Command $command, $method, $args) {}
	
	// ORM-related hooks
	public function dbPopulate(DataMapper $mapper, DomainObject $object)
	{
		$this->log("db_populate");
	}
	
	public function dbFind(DataMapper $mapper, $args)
	{
		$this->log("db_find");
	}
	
	public function dbInsert(DataMapper $mapper, DomainObject $object)
	{
		$this->log("db_insert");
	}
	
	public function dbUpdate(DataMapper $mapper, DomainObject $object)
	{
		$this->log("db_update");
	}
	
	public function dbRemove(DataMapper $mapper, $id)
	{
		$this->log("db_remove");
	}
	
	public function postDBQuery(DataMapper $mapper, $query, DBDriver $db)
	{
		$this->log('query', sprintf("%01.6f ms. %s", $db->getQueryTime(), str_replace(array("\n", "\t"), array(" ", ""), $query)));
	}
}
