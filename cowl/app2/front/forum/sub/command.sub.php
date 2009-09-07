<?php

class ForumSubCommand extends Command
{
	public function index($name = '')
	{
		$this->template->add('name', $name);
		$this->template->add('categories', array('foo', 'bar', 'baz'));
	}
}
