<?php

class TestCommand extends Command
{
	protected $objects = array('Test');
	
	public function index()
	{
		$tests = $this->testmapper->limit(0, 10)->find('all');
		$this->template->
	}
}
