<?php

class TestMainCommand extends Command
{
	protected $objects = array('Test');
	
	public function index()
	{
		$tests = $this->testmapper->limit(0, 10)->find('all');
		echo '<ol>';
		foreach ( $tests as $test )
		{
			echo '<li>' . $test->value . '</li>';
		}
		echo '</ol>';
	}
}
