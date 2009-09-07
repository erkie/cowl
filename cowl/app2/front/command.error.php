<?php

class ErrorCommand extends Command
{
	public function index()
	{
		$args = func_get_args();
		$this->template->add('trace', "Error(" . implode(', ', $args) . ")");
	}
}