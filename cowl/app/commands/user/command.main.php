<?php

class UserMainCommand extends Command
{
	protected $mappers = array('UserMapper');
	
	protected $aliases = array(
		'press' => 'profile',
		'redigera' => 'edit'
	);
	
	public function initialize($username = false)
	{
		if ( ! $username )
		{
			throw new Exception('No username passed');
		}
		
		$this->user = new User();
		$this->user->set('username', $username);
		$this->usermapper->populate($this->user);
		
		$this->profile_log = new ProfileLog($this->user);
	}
	
	public function index()
	{
		$this->template->set('user', $this->user);
		$this->profile_log->logVisit(Current::$user);
	}
	
	public function profile()
	{		
		$this->index();
	}
	
	public function edit()
	{
		$this->user->auth(Current::$user->getKey());
		
		$data = Current::$request->fetch('name', 'city', 'birthdate');
		$this->user->set($data);
		
		$this->usermapper->save($this->user);
	}
}
