<?php

/*
	Class:
		DBDriver
	
	Base class for all database drivers. Provides a common interface for simple functions
*/

class DBDriver
{
	protected $timer;
	protected $last_time;
	
	protected function startTimer()
	{
		$this->timer = (float)microtime(true);
	}
	
	protected function endTimer()
	{
		$this->last_time = (float)microtime(true) - $this->timer;
	}
	
	public function getQueryTime()
	{
		return $this->last_time;
	}
}