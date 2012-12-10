<?php

/*
	Class:
		Logging
	
	Plugin for logging everything related to your site. Command-related, database queries,
	exceptions, etc.
*/

class Logging extends Plugin
{
	private $tmp = '';
	private $log_file;
	private $error_file;
	
	private $messages = array();
	private $errors = array();
	
	public function __construct()
	{
		$date = date("Ymd",  $_SERVER['REQUEST_TIME']);
		$this->log_file = COWL_TOP . sprintf(Current::$config->get("plugins.logging.log_file"), $date);
		$this->error_file = COWL_TOP . sprintf(Current::$config->get("plugins.logging.error_file"), $date);
		
		// Set global accessor as Current::$log
		Current::$log = $this;
	}
	
	/*
		Method:
			Logging::formatMessage
		
		Format a message for logging.
	*/
	
	private function formatMessage($type, $message)
	{
		return sprintf("(%s) %-12s = %s (%s)", date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']), $type, $message, $_SERVER['REMOTE_ADDR']);
	}
	
	/*
		Method:
			Log::log
		
		Log a generic message. Specified by a message and a type.
	*/
	
	public function log($type, $message = '--')
	{
		$this->messages[] = $this->formatMessage($type, $message);;
	}
	
	/*
		Method:
			Log::error
		
		Log an error. Specified by a type and a message.
	*/
	
	public function error($type, $message)
	{
		$this->errors[] = $this->formatMessage($type, $message);
	}
	
	public function save()
	{
		// Do not log anything if we do not have anything to log
		if ( count($this->messages) )
		{
			file_put_contents($this->log_file, implode(PHP_EOL, $this->messages) . PHP_EOL, FILE_APPEND);
			$this->messages = array();
		}

		if ( count($this->errors) )
		{
			file_put_contents($this->error_file, implode(PHP_EOL, $this->errors) . PHP_EOL, FILE_APPEND);
			$this->errors = array();
		}
	}
	
	// Plugin hooks
	
	public function prePathParse(Controller $controller, StaticServer $server)
	{
//		$this->log("request", isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'No URI');
	}
		
	// FrontController-related hooks
	public function postRun()
	{
		$request_time = microtime(true) - COWL_START_TIME;
				
		$this->log("request_done", sprintf("took %01.6f s. %s", $request_time, ! COWL_CLI ? $_SERVER['REQUEST_URI'] : $_SERVER['argv'][1]));
		$this->save();
	}
	
	public function preStaticServe(StaticServer $server) {}
	
	public function postStaticServe(StaticServer $server)
	{
		$request_time = microtime(true) - COWL_START_TIME;
		$this->log("static_serve", sprintf("took %01.6f s. %s", $request_time, $server->getPath()));
		
		// Logging static file requests is optional since it floods the logs
		if ( Current::$config->get('plugins.logging.log_static_files') )
		{
			$this->save();
		}
	}
	
	// Command-related hooks
	public function commandRun(Command $command, $method, $args) {}
	
	// ORM-related hooks
	public function dbPopulate(DataMapper $mapper, DomainObject $object)
	{
		$this->tmp = 'db_populate';
	}
	
	public function dbFind(DataMapper $mapper, $args)
	{
		$this->tmp = 'db_find';
	}
	
	public function dbInsert(DataMapper $mapper, DomainObject $object)
	{
		$this->tmp = 'db_insert';
	}
	
	public function dbUpdate(DataMapper $mapper, DomainObject $object)
	{
		$this->tmp = 'db_update';
	}
	
	public function dbRemove(DataMapper $mapper, $id)
	{
		$this->tmp = 'db_remove';
	}
	
	public function postDBQuery(DataMapper $mapper, $query, DBDriver $db)
	{
		$time = $db->getQueryTime();
		$query = str_replace(array("\n", "\t"), array(" ", ""), $query);
		
		// Only log all queries in non-production
		if ( Current::$config->get('mode') !== 'production' )
		{
			$this->log($this->tmp, sprintf("%01.6f s. %s", $time, $query));
		}
		
		if ( $time > 0.25 )
		{
			$this->log('SLOW_QUERY', sprintf("took %01.6f s. %s", $time, $query));
		}
	}
}
