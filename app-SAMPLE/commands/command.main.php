<?php

class MainCommand extends Command
{
	public function index()
	{
		$this->flash('Welcome to the wonderful world of Cowl.');
	}
}