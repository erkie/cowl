<?php

class DOMemberNotFoundException extends Exception {}
class DOValidationException extends Exception {}
class DOFaultyIDException extends Exception {}

/*
	Class:
		<DomainObject>
	
	A <DomainObject> represents an object of the real world. A Post, a User or a Cat.
*/

abstract class DomainObject
{
	/*
		Property:
			<DomainObject::$members>
		
		Contains the members of the object. The name of the member should be a key of an entry in the array, and the value should be an array of validator functions which should help validate the input when setting values. The string 'yes' corresponds to true, and 'no' is the same as false. A default value can also be defined in this validator array.
	*/
	
	protected $members = array();
	
	// Property: <DomainObject::$values>
	// The values end up in this array efter they have been <DomainObject::set>
	private $values = array();
	
	// Property: <DomainObject::$rest>
	// Holds other values that a need a home, but don't have a place in <DomainObject::$members>. This is for queries that fetch more things than just this DomainCollections' values.
	private $rest = array();
	
	// Property: <DomainObject::$validator>
	// <Validator> for validating input.
	private $validator;
	
	// Property: <DomainObject::$id>
	// Every qualified <DomainObject> should have a $id-property.
	private $id;
	
	// Property: <DomainObject::$is_erronous>
	// A flag set to true if the <DomainObject> did not exist.
	private $is_erronous;
	
	/*
		Constructor:
			<DomainObject::__construct>
		
		Inserts default values in the <DomainObject::$values>-array.
		
		Parameters:
			$id - (optional) Short-hand for setting an objects ID.
	*/
	
	public function __construct($id = null)
	{
		if ( ! is_null($id) )
		{
			$this->setID($id);
		}
		
		$this->validator = new Validator();
	}
	
	/*
		Method:
			<DomainObject::set>
		
		Set a member. If $raw is true, no form of validation will take place. Otherwise validation of input will occur, and on failure a <ValidatorFailException> will be thrown.
		
		Note: Do not set ID using this method. For that purpose <DomainObject::setID> exists.
		
		Parameters:
			string $name - The name of the member to set
			mixed $value - The value
			boolean $raw - If set to true no validation will take place.
	*/
	
	public function set($name, $value, $raw = false)
	{
		if ( ! isset($this->members[$name]) )
		{
			if ( $raw === false )
			{
				throw new DOMemberNotFoundException($name);
			}
			else
			{
				$this->rest[$name] = $value;
			}
		}
		elseif ( $raw || (! $raw && $this->validate($name, $value)) )
		{
			$this->values[$name] = $value;
		}
	}
	
	/*
		Method:
			<DomainObject::setRaw>
		
		Set a member without validating the new value.
		
		Parameters:
			string $name - The name to set.
			mixed $value - The new value.
	*/
	
	public function setRaw($name, $value)
	{
		$this->set($name, $value, true);
	}
	
	/*
		Method:
			<DomainObject::get>
		
		Return the value of a member. If $name is "id", use <DomainObject::getID> to fetch the ID (this is so <DomainObject::__get> will behave correctly).
		
		This method will try to fetch from <DomainObject::$rest> first, before searching in it's values.
		
		Parameters:
			string $name - The name of the member to fetch.
		
		Returns:
			The value.
	*/
	
	public function get($name)
	{
		if ( $name === 'id' )
		{
			return $this->getID();
		}
		
		if ( isset($this->rest[$name]) )
		{
			return $this->rest[$name];
		}
		
		if ( ! isset($this->members[$name]) )
		{
			throw new DOMemberNotFoundException($name);
		}
		
		return $this->values[$name];
	}
	
	/*
		Method:
			<DomainObject::__get>
		
		Alternative form of <DomainObject::get>, for a more intuitive API.
	*/
	
	public function __get($name)
	{
		return $this->get($name);
	}
	
	/*
		Method:
			<DomainObject::__set>
		
		Alternative form of <DomainObject::set>, for a more intuitive API.
	*/
	
	public function __set($name, $value)
	{
		if ( $name == 'id' )
		{
			return $this->setID($id);
		}
		return $this->set($name, $value);
	}
	
	/*
		Method:
			<DomainObject::setID>
		
		Set the ID of the Object. Must be an integer, else <DOFaultyIDException> is thrown.
		
		Parameters:
			integer $id - The new ID.
	*/
	
	public function setID($id)
	{
		if ( ! is_numeric($id) )
		{
			throw new DOFaultyIDException($id);
		}
		
		$this->id = $id;
	}
	
	// Method: <DomainObject::getID>
	// Get <DomainObject::$id>
	public function getID()
	{
		return $this->id;
	}
	
	// Method: <DomainObject::fetch>
	// Fetch all values, in key => value form.
	public function fetch()
	{
		return $this->values;
	}
	
	/*
		Method:
			<DomainObject::isErronous>
		
		Checks if this <DomainObject> has it's <DomainObject::is_erronous>-flag set.
		
		Returns:
			<DomainObject::is_erronous>
	*/
	
	public function isErronous()
	{
		return $this->is_erronous;
	}
	
	// Method: <DomainObject::setError>
	// Set this to an erronous object.
	public function setError()
	{
		$this->is_erronous = true;
	}
	
	/*
		Method:
			<DomainObject::validate>
		
		Try to validate a member against an input, using <Validator::validate>.
		
		Parameters:
			string $name - The name of the member.
			mixed $input - The input to validate.
		
		Returns:
			True if $input is valid for $name. Otherwise a <ValidatorFailException> is thrown.
	*/
	
	private function validate($name, $input)
	{
		$rules = $this->members[$name];
		
		foreach ( $rules as $rule => $arg )
		{
			// Ignore default rules, as this is not a validation clause
			if ( $rule == 'default' ) continue;
			$this->validator->validate($input, $rule, $arg);
		}
		
		return true;
	}
	
	// Method: <DomainObject::getData>
	// Returns the data of the current object. That means a merge of <DomainObject::$values> and <DomainObject::$rest>
	public function getData()
	{
		return array_merge($this->values, $this->rest);
	}
	
	/*
		Method:
			<DomainObject::initialize>
		
		A method that is called when the object is initialized. For example, will be called when populated from the database.
		
		Feel free to overwrite in your sub-class.
	*/
	
	public function initialize() {}
}
