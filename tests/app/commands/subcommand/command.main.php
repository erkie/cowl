<?php

/*
	This is wrong!
	
	command.subcommand.php is right.
*/

class SubcommandMainCommand extends Command
{
	public function index()
	{
		$this->flashError('In main command of subcommand sub-directory!');
	}
}
