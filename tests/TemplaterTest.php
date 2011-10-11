<?php

require_once './includes.php';
require_once COWL_TEST_PATH . 'templater.php';

class TemplaterTest extends PHPUnit_Framework_TestCase
{
	private $template;
	
	public function setUp()
	{
		$this->template = new Templater();
		$this->template->setDir(COWL_TEST_APP . 'views/');
		$this->template->setLayoutDir(COWL_TEST_APP . 'views/layouts/');
	}
	
	public function tearDown()
	{
		
	}
	
	public static function layoutProvider()
	{
		return array(
			array('html', 'view.main.php', "HTML MAIN HELLO"),
			array('app', 'view.main.php', "APP MAIN HELLO"),
			array('html', 'sub/view.foo.php', "HTML FOO HELLO")
		);
	}
	
	/**
	 *	@dataProvider layoutProvider
	 */
	
	public function testLayouts($type, $view, $output)
	{
		ob_start();
		
		$this->template->setType($type);
		$this->template->render($view);
		
		$result = ob_get_clean();
		$this->assertEquals($output, $result);
	}
	
	/**
	 * @expectedException TPLLayoutNotExistsException
	 */
	
	public function testNonexistantLayout()
	{
		$this->template->setType('nonexistant');
	}
	
	/**
	 * @expectedException TPLTemplateNotExistsException
	 */
	
	public function testNonexistantView()
	{
		$this->template->render('i-dont-exist');
	}
}
