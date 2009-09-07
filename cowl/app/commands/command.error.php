<?php

class ErrorCommand extends Command
{
	public function index($error_code = '')
	{
		$args = func_get_args();
		$args = array_slice($args, 1);
		echo 'Error::index(', implode(', ', $args), ')';
	}
}