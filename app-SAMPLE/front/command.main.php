<?php

class MainCommand extends Command
{
	public function index()
	{
		$this->template->add('title', 'Hello');
		
		$this->flash('Welcome to the wonderful world of Cowl.');
	}
}