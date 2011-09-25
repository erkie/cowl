<?php

/*
	This might look like a correct structure. And it is, but not good practice.
	Before you could access this type of structure with:
	
		/badsub/ -> commands/badsub/main/command.main.php
	
	But that has changed. Now you access this command with:
	
		/badsub/main/ -> commands/badsub/main/command.main.php
	
	Which is unnecessary and silly. Just add the maincommand in the base subdirectory:
	
		/badsub/ -> commands/badsub/command.main.php
*/

class BadSubMainCommand extends Command
{
	public function index()
	{
		$this->flashError('Bad structure');
	}
}
