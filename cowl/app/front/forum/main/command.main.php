<?php

class ForumMainCommand extends Command
{
	private $user = array(
		'id' => 10,
		'user' => 'Test',
		'auth_level' => 2
	);
	
	public function initialize()
	{
		try {
			Current::$auth->force($this->user['auth_level'] > 3, 'You don\'t have the proper rights to access this page.');
		}
		catch (AuthenticationFailException $e)
		{
			$this->setView('error');
			$this->template->add('message', end($e->getKeys()));
		}
	}
	
	public function index()
	{
		$this->template->add('categories', array('foo', 'bar', 'baz'));
	}
	
	public function add() {}
	public function edit() {}
}
