<?php

class ValidatorException extends Exception {}
class ValidatorNotFoundException extends ValidatorException {}

class ValidatorFailException extends ValidatorException
{
	public $validator;
	
	public function __construct($validator, $message)
	{
		parent::__construct($message);
		$this->validator = $validator;
	}
	
	public function getValidator()
	{
		return $this->validator;
	}
}

/*
	Class:
		<Validator>
	
	A validator class capable of including validators and validating input.
*/

class Validator
{
	// Property: <Validator::$path>
	// Contains the pathname to where the validators are contained.
	private static $path = 'validators/';
	
	private static $error_messages = array();
	
	// Property: Validator::$store_errors
	// Flag wether to store errors or just throw a ValidatorFailException
	private $store_errors = false;
	
	// Property: Validator::$errors
	// If <Validator::$store_errors> is true it will store errors in this array as key->values
	private $errors = array();
		
	/*
		Method:
			<Validator::setPath>
		
		Set the <Validator::$path>. $path must point to an existing directory.
		
		Parameters:
			$path - The path to the directory.
	*/
	
	public static function setPath($path)
	{
		self::$path = $path;
	}
	
	/*
		Method:
			<Validator::loadStrings>
		
		Load error messages from a specified path.
		
		Parameters:
			The path to load error messages from.
	*/
	
	public static function loadStrings($path)
	{
		self::$error_messages = require($path);
		self::$error_messages = self::$error_messages['en'];
	}
	
	/*
		Method:
			Validator::validate
		
		Validate the input, throwing a <ValidatorFailException> on failure.
		
		Parameters:
			$input - The input to validate.
			$func - The validator function to call. If the function does not exist, it will be loaded by <Validator::loadValidator>. $func will be translated to <Validator::$path>/validator.$func.php.
			$arg - An optional argument to pass to the validator.
			$key - An optional key to remember this input by
		
		Returns:
			Returns true on success, throws a ValidatorException on failure.
	*/
	
	public function validate($input, $func, $arg = null, $key = '')
	{
		if ( ! $this->checkValue($input, $func, $arg) )
		{
			if ( $this->store_errors )
			{
				if ( ! isset($this->errors[$key]) )
					$this->errors[$key] = array();
				
				$this->addError($key, $this->makeErrorMessage($func, $arg));
			}
			else
			{
				throw new ValidatorFailException($this, $funcname . ': "' . $input . '"');
			}
		}
		
		return true;
	}
	
	/*
		Method:
			Validator::checkValue
		
		Validate an input with a validator, without throwing any errors or leaving any traces.
		A silent validation.
		
		Parameters:
			$input - The input to check
			$rule - The rule to check with
			(option) $arg - Argument input to function
		
		Returns:
			true or false if it succeeds
	*/
	
	public function checkValue($input, $rule, $arg = null)
	{
		$funcname = self::makeName($rule);
		
		if ( ! $this->hasValidator($funcname) )
		{
			$this->loadValidator($rule);
		}
		
		return call_user_func($funcname, $input, $arg);
	}
	
	/*
		Method:
			<Validator::hasValidator>
		
		Checks whether the passed validator exists, by checking if the function $name exists.
		
		Parameters:
			$name - The name, prefixed with validator_. E.g. validate_do_something.
		
		Returns:
			True if it exists, false if it does not exist.
	*/
	
	private function hasValidator($name)
	{
		return function_exists($name);
	}
	
	/*
		Method:
			<Validator::loadValidator>
		
		Loads the passed validator. The $name should be the name of the validator itself, not the filename nor the function name. Throws a ValidatorNotFoundException on failure.
		
		Parameters:
			$name - The name of the validator.
	*/
	
	private function loadValidator($name)
	{
		$filename = self::$path . 'validator.' . $name . '.php';
		
		if ( file_exists($filename) )
		{
			require($filename);
			
			// Still no validator...
			if ( ! $this->hasValidator(self::makeName($name)) )
			{
				throw new ValidatorNotFoundException($name);
			}
		}
		else
		{
			throw new ValidatorNotFoundException($name);
		}
	}
	
	/*
		Method:
			Validator::setStoreErrors
		
		Set wether to store errors or not. See the <Validator::$store_errors> property.
		
		Parameters:
			(bool) $store - Flag wether to store the errors or not
	*/
	
	public function setStoreErrors($store)
	{
		$this->store_errors = $store;
	}
	
	/*
		Method:
			Validator::addError
		
		Add an error message for a key.
		
		Parameters:
			$key - The key to add the error message to
			$message - The message in question
	*/
	
	public function addError($key, $message)
	{
		$this->errors[$key][] = $message;
	}
	
	/*
		Method:
			Validator::getErrors
		
		Get all errors or just errors for a specific key.
		
		Parameters:
			(optional) $key - The key to retrieve errors for
		
		Returns:
			<Validator::$errors>, i.e errors in key -> value format.
	*/
	
	public function getErrors($key = false)
	{
		if ( $key )
			return isset($this->errors[$key]) ? $this->errors[$key] : array();
		return $this->errors;
	}
	
	/*
		Method:
			Validator::getErrorMessages
		
		Get all errors as an array of strings, using a print friendly name, if present.
		
		Example:
			array(
				'display_type' => array('Must be either: list, grid')
			)
			
			Will return:
			
			array(
				'Display type must be either: list, grid'
			)
		
		Returns:
			An array of strings
	*/
	
	public function getErrorMessages()
	{
		$errors = $this->getErrors();
		$ret = array();
		
		foreach ( $errors as $key => $errs )
		{
			// Transform _ to spaces
			$print_name = str_replace(array('_'), array(' '), $key);
			
			// Transform all uppercase accronyms to all uppercase
			if ( in_array($print_name, array('url')) )
				$print_name = strtoupper($print_name);
			
			foreach ( $errs as $error )
			{
				$ret[] = sprintf('%s %s', ucfirst($print_name), lcfirst($error));
			}
		}
		
		return $ret;
	}
	
	/*
		Method:
			<Validator::makeErrorMessage>
		
		Make an error message from the specified func_name and arg, using the i18n error
		messages.
		
		Parameters:
			$name - The func name
			$arg - The argument for the func name
		
		Returns:
			The error message
	*/
	
	private function makeErrorMessage($name, $arg)
	{
		if ( is_array($arg) )
			$arg = implode(', ', $arg);
		return @sprintf(self::$error_messages[$name], $arg);
	}
	
	/*
		Method:
			<Validator::makeName>
		
		Makes a function name out of the passed $func. It does this by prefixing validate_ to $func.
		
		Parameters:
			$func - The name to transform.
		
		Returns:
			The transformed name.
	*/
	
	private static function makeName($func)
	{
		return 'validate_' . $func;
	}
}
