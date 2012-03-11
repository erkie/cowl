<?php

class ErrorCommand extends Command
{
	public function index()
	{	
		header('HTTP/1.0 404 Not Found');
		
		$args = func_get_args();
		$this->template->add('trace', "Error(" . implode(', ', $args) . ")");
	}
}