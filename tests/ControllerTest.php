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
			
			// Empty route points to maincommand
			array('', 'command.main.php', array('maincommand')),
			
			// Route containing only a slash is maincommand
			array('/', 'command.main.php', array('maincommand')),
			
			// Pass parameters to the maincommand by calling the maincommand directly
			array('/main/1/2/3', 'command.main.php', array('maincommand', '1', '2', '3')),
			
			// Nonexistant commands invoke the errorcommand
			array('/nonexistant', 'command.error.php', array('errorcommand', 'nonexistant')),
			
			// Basic subcommand. Command is named the same as the subcommand directory
			array('/subcommand/', 'subcommand/command.subcommand.php', array('subcommandcommand')),
				
			// Subcommand with arguments
			array('/emptysub/foo/bar', 'emptysub/command.emptysub.php', array('emptysubcommand', 'foo', 'bar')),
			
			// Passing what looks like another command to a subcommand, is turned into an argument
			array('/subcommand/main', 'subcommand/command.subcommand.php', array('subcommandcommand', 'main')),
			
			// Subcommand in subcommand. This command structure is actually really bad form, but it should work
			array('/badsub/main/', 'badsub/main/command.main.php', array('badsubmaincommand'))
		);
	}
	
	/**
	 * @dataProvider routeProvider
	 */
	
	public function testBasic($path, $command_path, $cmd_args)
	{
		$controller = new Controller($path);
		$request = $controller->parse();
		
		$this->assertEquals($request->app_directory . $request->command, $command_path);
		$this->assertEquals($request->argv, $cmd_args);
	}
	
	public function responseTypeProvider()
	{
		return array(
			// array(route, response type)
			
			// Default response type is html
			array('', 'html'),
			array('/', 'html'),
			array('/main/', 'html'),
			array('main/1/2/3.html', 'html'),
			
			// Other accepted response types are:
			// - json
			// - xml
			
			array('main.xml', 'xml'),
			array('main/1/2/4.xml', 'xml'),
			
			// It should handle response types that do not exist, either,
			// because this is up to the user how to handle this.
			
			array('main.php', 'php'),
			array('main/foo/bar.baz', 'baz')
		);
	}
	
	/**
	 * @dataProvider responseTypeProvider
	 */

	public function testResponseType($path, $type)
	{
		$controller = new Controller($path);
		$request = $controller->parse();
		
		$this->assertEquals($request->response_type, $type);
	}
}
