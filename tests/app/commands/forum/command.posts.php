<?php

class ForumPostsCommand extends Command
{
	public function index($id = null)
	{
		$this->flash('Forum thread: ' . $id);
	}
	
	public function add()
	{
		$this->flash('Create post in thread');
	}
}
