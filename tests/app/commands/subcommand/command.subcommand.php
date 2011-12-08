<?php

class SubcommandCommand extends Command
{
	public function index()
	{
		$this->flash('In subcommand');
	}
}
