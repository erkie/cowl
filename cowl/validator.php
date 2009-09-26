<?php

class ValidatorException extends Exception {}
class ValidatorNotFoundException extends ValidatorException {}
class ValidatorFailException extends ValidatorException {}

/*
	Class:
		<Validator>
	
	A validator class capable of including validators, validating input.
*/

class Validator
{
	// Property: <Validator::$path>
	// Contains the pathname to where the validators are contained.
	private static $path = 'validators/';
	
	public function __construct() {}
	
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
			<Validator::validate>
		
		Validate the input, throwing a <ValidatorFailException> on failure.
		
		Parameters:
			$input - The input to validate.
			$func - The validator function to call. If the function does not exist, it will be loaded by <Validator::loadValidator>. $func will be translated to <Validator::$path>/validator.$func.php.
			$arg - An optional argument to pass to the validator.
		
		Returns:
			Returns true on success, throws a ValidatorException on failure.
	*/
	
	public function validate($input, $func, $arg = null)
	{
		$funcname = self::makeName($func);
		
		if ( ! $this->hasValidator($funcname) )
		{
			$this->loadValidator($func);
		}
		
		if ( ! call_user_func($funcname, $input, $arg) )
		{
			throw new ValidatorFailException($input);
		}
		
		return true;
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
