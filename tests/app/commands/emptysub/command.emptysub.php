<?php

/*
	This subcommand SHOULD be accessible. Subcommands should be in the format:
	
		/sub/ -> /commands/sub/command.sub.php
	
	It is NOT
	
		/sub/ -> /commands/sub/command.main.php
*/

class EmptySubCommand extends Command
{
	public function index()
	{
		$this->flash("This should be good.");
	}
}
