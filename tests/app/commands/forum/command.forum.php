<?php

class ForumCommand extends Command
{
	public function index()
	{
		$this->flash('List forum threads');
	}
	
	public function create()
	{
		$this->flash('Create forum thread');
	}
}
