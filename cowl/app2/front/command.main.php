<?php

class MainCommand extends Command
{
	public function index()
	{
		$this->template->add('title', 'Hello');
		$this->template->add('message', 'Welcome, to Cowl.');
	}
}