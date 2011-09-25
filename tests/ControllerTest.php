<?php

require_once './includes.php';
require_once COWL_TEST_PATH . 'controller.php';
require_once COWL_TEST_PATH . 'command.php';

class ControllerTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		Controller::setDir(COWL_TEST_APP . 'commands/');
		Controller::useRequireOnce(true);
	}
	
	public function tearDown()
	{
		
	}
	
	public static function routeProvider()
	{
		return array(
			// array(route, command-file, arg array)
			array('', 'command.main.php', array('maincommand')),
			array('/', 'command.main.php', array('maincommand')),
			array('/main/1/2/3', 'command.main.php', array('maincommand', '1', '2', '3')),
			array('/nonexistant', 'command.error.php', array('errorcommand', 'nonexistant'))
		);
	}
	
	/**
	 * @dataProvider routeProvider
	 */
	
	public function testBasic($path, $command_path, $cmd_args)
	{
		$controller = new Controller($path);
		$argv = $controller->parse();
		
		$this->assertEquals($argv['command'], $command_path);
		$this->assertEquals($argv['argv'], $cmd_args);
	}
}
