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
	
	/*
		Property:
			<DomainObject::$expose>
		
		Data members to be considered public. A flat numeric array.
		
		An empty array means no public data, which is default. This is for security reasons. It
		can also include members added to the <DomainObject::$rest> and also getter-methods named
		"get<valueinarray>" for dynamic getters.
	*/
	
	protected $expose = array();
	
	// Property: <DomainObject::$values>
	// The values end up in this array efter they have been <DomainObject::set>
	private $values = array();
	
	// Property: <DomainObject::$rest>
	// Holds other values that a need a home, but don't have a place in <DomainObject::$members>. This is for queries that fetch more things than just this DomainCollections' values.
	private $rest = array();

	// Property: <DomainObject::$dirty>
	// An array of "dirty" values that need to be changed
	private $dirty = array();
	
	// Property: <DomainObject::$validator>
	// <Validator> for validating input.
	private $validator;
	
	// Property: <DomainObject::$id>
	// Every qualified <DomainObject> should have a $id-property.
	private $id = false;
	
	// Property: <DomainObject::$is_erroneous>
	// A flag set to true if the <DomainObject> did not exist.
	private $is_erroneous;
	
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
		$this->validator->setStoreErrors(true);
	}
	
	/*
		Method:
			DomainObject::set
		
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
		// Do not validate until ensure is called
		else
		{
			$old_value = isset($this->values[$name]) ? $this->values[$name] : null;
			$this->values[$name] = $value;
			
			if ( $old_value != $value )
				$this->dirty[$name] = true;
		}
	}
	
	/*
		Method:
			DomainObject::setRaw
		
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
			DomainObject::setFromDB
		
		Set a row with data from the database. 
	*/
	
	public function setFromDB($name, $value)
	{
		$this->setRaw($name, $value);
		
		// Mark as "un-dirty"
		if ( isset($this->members[$name]) )
		{
			unset($this->dirty[$name]);
		}
	}
	
	/*
		Method:
			DomainObject::get
		
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
		
		if ( array_key_exists($name, $this->rest) )
		{
			return $this->rest[$name];
		}
		
		if ( ! isset($this->members[$name]) )
		{
			throw new DOMemberNotFoundException($name);
		}
		
		return isset($this->values[$name]) ? $this->values[$name] : '';
	}
	
	/*
		Method:
			DomainObject::__get
		
		Alternative form of <DomainObject::get>, for a more intuitive API.
	*/
	
	public function __get($name)
	{
		return $this->get($name);
	}
	
	/*
		Method:
			DomainObject::__set
		
		Alternative form of <DomainObject::set>, for a more intuitive API.
	*/
	
	public function __set($name, $value)
	{
		if ( $name == 'id' )
		{
			return $this->setID($value);
		}
		return $this->set($name, $value);
	}
	
	/*
		Method:
			DomainObject::setID
		
		Set the ID of the Object. Must be an integer, else <DOFaultyIDException> is thrown.
		
		Parameters:
			integer $id - The new ID.
	*/
	
	public function setID($id)
	{
		if ( ! is_numeric($id) )
		{
			throw new DOFaultyIDException();
		}
		
		$this->id = (int)$id;
	}
	
	// Method: DomainObject::getID
	// Get <DomainObject::$id>
	public function getID()
	{
		return $this->id;
	}
	
	// Method: DomainObject::fetch
	// Fetch all values, in key => value form.
	public function fetch()
	{
		return $this->values;
	}
	
	// Method: DomainObject::fetchDirty
	// Fetch all dirty values as key => value
	public function fetchDirty()
	{
		$ret = array();
		foreach ( $this->dirty as $key => $is_dirty )
		{
			$ret[$key] = $this->values[$key];
		}
		return $ret;
	}
	
	// Method: DomainObject::markAsClean
	// Mark DomainObject as clean
	public function markAsClean()
	{
		$this->dirty = array();
	}
	
	/*
		Method:
			DomainObject::isErroneous
		
		Checks if this <DomainObject> has it's <DomainObject::is_erroneous>-flag set.
		
		Returns:
			<DomainObject::is_erroneous>
	*/
	
	public function isErroneous()
	{
		return $this->is_erroneous;
	}
	
	// Method: DomainObject::setError
	// Set this to an erronous object.
	public function setError()
	{
		$this->is_erroneous = true;
	}
	
	/*
		Method:
			DomainObject::validate
		
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
		
		// If it is specified as `is_mandatory` => false, then we should skip validation if it is
		// not present. So if is_mandatory is true, and when checking it as if it was mandatory,
		// if it returns true (not there), it shouldn't be validated
		if (
			isset($rules['is_mandatory']) &&
			! $rules['is_mandatory'] &&
			! $this->validator->checkValue($input, 'is_mandatory', true) )
		{
			return true;
		}
		
		foreach ( $rules as $rule => $arg )
		{
			// Ignore default rules, as this is not a validation clause
			if ( $rule == 'default' ) continue;
			$this->validator->validate($input, $rule, $arg, $name);
		}
		
		return true;
	}
	
	/*
		Method:
			DomainObject::ensure
		
		Ensure the integrity of the data by running everything through a validator
		and throwing a ValidatorFailException for all failed keys.
		
		If it comes across a bad value that value will be unset.
		
		Parameters:
			(optional) $key1 - A list of keys to check
			(optional) $keyN - ...
	*/
	
	public function ensure()
	{
		$check = func_get_args();
		$check = count($check) ? $check : false;
		
		foreach ( $this->members as $key => $value )
		{
			if ( isset($this->values[$key]) )
				$val = $this->values[$key];
			
			elseif ( isset($value['default']) )
			{
				$val = $value['default'];
				$this->values[$key] = $val;
			}
			else
				$val = null;
			
			// If specified by arguments not to check, skip it
			if ( $check && ! in_array($key, $check) )
				continue;
			
			$this->validate($key, $val);
		}
		
		if ( count($this->validator->getErrors()) )
		{
			throw new ValidatorFailException($this->validator);
		}
	}
	
	/*
		Method:
			DomainObject::getValidator
		
		Get the validator for the object.
		
		Returns:
			A <Validator>
	*/
	
	public function getValidator()
	{
		return $this->validator;
	}
	
	// Method: DomainObject::getData
	// Returns the data of the current object. That means a merge of <DomainObject::$values> and <DomainObject::$rest>
	public function getData()
	{
		return array_merge($this->values, $this->rest, array('id' => $this->getID()));
	}
	
	/*
		Method:
			DomainObject::getPublicData
		
		Same as <DomainObject::getData>, but only return data that is considered public. This is set via the
		<DomainObject::$expose>
	*/
	
	public function getPublicData()
	{
		$ret = array();
		foreach ( $this->expose as $key )
		{
			if ( isset($this->values[$key]) )
				$ret[$key] = $this->values[$key];
			elseif ( method_exists($this, 'get' . $key) )
				$ret[$key] = $this->{'get' . $key}();
			elseif ( isset($this->rest[$key]) )
				$ret[$key] = $this->rest[$key];
			elseif ( $key == 'id' )
				$ret['id'] = $this->getID();
		}
		return $ret;
	}
	
	/*
		Method:
			DomainObject::initialize
		
		A method that is called when the object is initialized. For example, will be called when populated from the database.
		
		Feel free to overwrite in your sub-class.
	*/
	
	public function initialize() {}
}
