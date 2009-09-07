<?php

class UserBlogCommand extends Command
{
	protected $dependencies = array('UserMain');
	
	public function initialize($year = '2008', $month = '10', $day = '09')
	{
		echo $year . '/' . $month . '/' . $day, '<br />';
	}
	
	public function index()
	{
		echo 'listing posts', '<br />';
	}
	
	public function create()
	{
		echo 'create new post', '<br />';
	}
}