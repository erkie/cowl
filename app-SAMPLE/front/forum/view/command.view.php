<?php

class ForumViewCommand extends Command
{
	public function index($name = '')
	{
		$this->template->add('thread', $name);
	}
}