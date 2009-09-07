<?php

class TestCommand extends Command
{
	protected $objects = array('Test');
	
	public function index($orderby = '', $offset = null, $amount = null)
	{
		$objects = $this->testmapper->find(array('id' => '< 10'), array('value', 'id'), $offset, $amount);
		//$objects = $this->testmapper->by('id')->limit(20, 10)->find('all');
		
		foreach ( $objects as $object )
		{
			echo $object->getID() . ': ' . $object->value . '<br />';
		}
		
		printf('<p>%d objects found.</p>', $objects->count());
		
		$objects = $this->testmapper->find(array('value' => 'Foom'));
		printf('<p>%d values with Foom as  value.</p>', $objects->count());
		
		/*$tests = $this->testmapper->by('id')->find(array('value' => 'hello'));
		
		printf('<p>%d objects found in the new query.</p>', $tests->count());
		
		foreach ( $tests as $test ) echo $test->getID() . '<br />';*/
		
		$posts = $this->postmapper->by('id')->limit(10, 20)->find('all');
	}
	
	public function add($value = 'Hello world!')
	{
		$test = new Test();
		$test->set('value', $value);
		$this->testmapper->uptodate($test);
	}
	
	public function edit($id)
	{
		$test = new Test($id);
		$this->testmapper->populate($test);
		
		$test->set('value', $test->get('value') . ' Updated!');
		$this->testmapper->uptodate($test);
	}
	
	public function remove($id)
	{
		$test = new Test($id);
		$this->testmapper->remove($test);
	}
	
	public function test($id)
	{
		$test = $this->testmapper->populate(new Post($id));
	}
}