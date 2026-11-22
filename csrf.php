<?php

/*
	Exception:
		CSRFValidationException

	Thrown by <Command::run> when a state-changing request fails CSRF token
	validation. It is caught internally and turned into a 403 response.
*/

class CSRFValidationException extends Exception {}

/*
	Class:
		CSRF

	Cross-Site Request Forgery protection for Cowl.

	A per-session, cryptographically-random token is generated and stored in
	the session. State-changing requests (POST, PUT, PATCH, DELETE) must echo
	that token back, either in the `csrf_token` POST field or in the
	`X-CSRF-Token` HTTP header. Safe requests (GET, HEAD, OPTIONS, TRACE) are
	never checked.

	Protection is enabled by default (opt-out). To disable it globally set
	`csrf.enabled => false` in your config. Individual commands can opt out by
	setting `protected $csrf_protection = false;` or exempt specific actions
	with `protected $csrf_exempt = array('webhook');`.

	The token is emitted automatically inside every non-GET form built with the
	form helper (<form_for>). For hand-written forms and AJAX use the <csrf_field>,
	<csrf_token> and <csrf_meta> helpers.
*/

class CSRF
{
	// The key under which the token is stored in $_SESSION.
	const SESSION_KEY = 'cowl_csrf_token';

	// The form field name that carries the token.
	const FIELD_NAME = 'csrf_token';

	// HTTP methods that never require a CSRF token.
	private static $safe_methods = array('GET', 'HEAD', 'OPTIONS', 'TRACE');

	/*
		Method:
			CSRF::token

		Return the current session's CSRF token, creating one on first use.
		Requires an active session (the FrontController starts one).

		Returns:
			The token string.
	*/

	public static function token()
	{
		if ( empty($_SESSION[self::SESSION_KEY]) )
		{
			$_SESSION[self::SESSION_KEY] = self::generateToken();
		}

		return $_SESSION[self::SESSION_KEY];
	}

	/*
		Method:
			CSRF::generateToken

		Generate a new random token. Does not store it.
	*/

	public static function generateToken()
	{
		if ( function_exists('random_bytes') )
			return bin2hex(random_bytes(32));

		// Extremely old PHP fallback. Should never be hit on supported versions.
		return bin2hex(openssl_random_pseudo_bytes(32));
	}

	/*
		Method:
			CSRF::field

		Return a hidden input element carrying the current token, safe to embed
		directly in a form.
	*/

	public static function field()
	{
		return sprintf(
			'<input type="hidden" name="%s" value="%s" />',
			self::FIELD_NAME,
			htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8')
		);
	}

	/*
		Method:
			CSRF::isSafeMethod

		Whether the given HTTP method is considered safe (never CSRF-checked).
	*/

	public static function isSafeMethod($method)
	{
		return in_array(strtoupper((string)$method), self::$safe_methods, true);
	}

	/*
		Method:
			CSRF::requestMethod

		The current HTTP request method, upper-cased. Defaults to GET.
	*/

	public static function requestMethod()
	{
		return isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
	}

	/*
		Method:
			CSRF::requestToken

		Extract the token supplied by the client, from the POST body or the
		X-CSRF-Token header. Query-string values are intentionally ignored.

		Returns:
			The supplied token, or an empty string.
	*/

	public static function requestToken()
	{
		if ( isset($_POST[self::FIELD_NAME]) && is_string($_POST[self::FIELD_NAME]) )
			return $_POST[self::FIELD_NAME];

		if ( isset($_SERVER['HTTP_X_CSRF_TOKEN']) && is_string($_SERVER['HTTP_X_CSRF_TOKEN']) )
			return $_SERVER['HTTP_X_CSRF_TOKEN'];

		if ( isset($_SERVER['HTTP_X_CSRFTOKEN']) && is_string($_SERVER['HTTP_X_CSRFTOKEN']) )
			return $_SERVER['HTTP_X_CSRFTOKEN'];

		return '';
	}

	/*
		Method:
			CSRF::validate

		Constant-time comparison of a supplied token against the session token.

		Parameters:
			$token - The token supplied by the client.

		Returns:
			True if the token is valid, else false.
	*/

	public static function validate($token)
	{
		if ( ! is_string($token) || $token === '' )
			return false;

		if ( empty($_SESSION[self::SESSION_KEY]) )
			return false;

		return hash_equals($_SESSION[self::SESSION_KEY], $token);
	}

	/*
		Method:
			CSRF::isValidRequest

		Validate the current request. Safe HTTP methods always pass; unsafe
		methods must carry a valid token.

		Returns:
			True if the request may proceed, else false.
	*/

	public static function isValidRequest()
	{
		if ( self::isSafeMethod(self::requestMethod()) )
			return true;

		return self::validate(self::requestToken());
	}
}
