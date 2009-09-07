<?php

class ForumMainCommand extends Command
{
	public function index()
	{
		$this->template->add('categories', array('foo', 'bar', 'baz'));
	}
	
	public function add() {}
	public function edit() {}
}
