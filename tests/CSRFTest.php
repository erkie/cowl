<?php

require_once './includes.php';
require_once COWL_TEST_PATH . 'csrf.php';

class CSRFTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$_SESSION = array();
		$_POST = array();
		$_GET = array();
		unset($_SERVER['HTTP_X_CSRF_TOKEN'], $_SERVER['HTTP_X_CSRFTOKEN']);
		$_SERVER['REQUEST_METHOD'] = 'GET';
	}

	public function tearDown()
	{
		$_SESSION = array();
		$_POST = array();
	}

	public function testTokenIsStableHexString()
	{
		$token = CSRF::token();
		$this->assertEquals(1, preg_match('/^[0-9a-f]{64}$/', $token));
		$this->assertEquals($token, CSRF::token());
	}

	public function testFieldCarriesToken()
	{
		$token = CSRF::token();
		$field = CSRF::field();
		$this->assertTrue(strpos($field, 'name="csrf_token"') !== false);
		$this->assertTrue(strpos($field, $token) !== false);
	}

	public function testSafeMethodsNeverRequireToken()
	{
		foreach ( array('GET', 'HEAD', 'OPTIONS', 'TRACE') as $method )
		{
			$_SERVER['REQUEST_METHOD'] = $method;
			$this->assertTrue(CSRF::isValidRequest());
		}
	}

	public function testUnsafeMethodWithoutTokenIsRejected()
	{
		CSRF::token();
		foreach ( array('POST', 'PUT', 'PATCH', 'DELETE') as $method )
		{
			$_SERVER['REQUEST_METHOD'] = $method;
			$_POST = array();
			$this->assertFalse(CSRF::isValidRequest());
		}
	}

	public function testPostWithValidTokenPasses()
	{
		$token = CSRF::token();
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST = array('csrf_token' => $token);
		$this->assertTrue(CSRF::isValidRequest());
	}

	public function testPostWithInvalidTokenIsRejected()
	{
		CSRF::token();
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST = array('csrf_token' => 'not-the-token');
		$this->assertFalse(CSRF::isValidRequest());
	}

	public function testHeaderTokenIsAccepted()
	{
		$token = CSRF::token();
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
		$this->assertTrue(CSRF::isValidRequest());
	}

	public function testQueryStringTokenDoesNotSatisfy()
	{
		$token = CSRF::token();
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_GET = array('csrf_token' => $token);
		$_POST = array();
		$this->assertFalse(CSRF::isValidRequest());
	}

	public function testValidateFailsWithoutSessionToken()
	{
		$_SESSION = array();
		$this->assertFalse(CSRF::validate('anything'));
	}
}
