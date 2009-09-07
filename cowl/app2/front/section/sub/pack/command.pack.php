<?php

class SectionSubPackCommand extends Command
{
	protected $aliases = array('run' => 'spring');
	
	public function index()
	{
		echo 'Hello world! ' . get_class($this);
	}
	
	public function spring()
	{
		echo 'I love running. :)';
	}
}