<?php

define('COWL_BASE', '/');

require_once './includes.php';
require_once COWL_TEST_PATH . 'cowl.php';

class CowlTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		
	}
	
	public function tearDown()
	{
		
	}
	
	public function testURLBasic()
	{
		$this->assertEquals(Cowl::url(), '/');
		$this->assertEquals(Cowl::url(''), '/');
		$this->assertEquals(Cowl::url('command'), '/command');
		$this->assertEquals(Cowl::url('command.html'), '/command.html');
		$this->assertEquals(Cowl::url('command', 'sub', 'foo'), '/command/sub/foo');
	}
	
	public function testURLQueryString()
	{
		$this->assertEquals(Cowl::url('?foo=bar'), '/?foo=bar');
		$this->assertEquals(Cowl::url('sub', 'command?foo=bar&moo'), '/sub/command?foo=bar&moo');
		$this->assertEquals(Cowl::url('a', 'b.html?c=d&e=f'), '/a/b.html?c=d&e=f');
	}
}