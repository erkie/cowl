<?php

class DOMemberNotFoundException extends Exception {}
class DOValidationException extends Exception {}
class DOFaultyIDException extends Exception {}

/*
	Class:
		<DomainObject>
	
	A DomainObject represents an object of the real world. A Post, a User or a Cat.
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
	
	// Property: <DomainObject::$validator>
	// <Validator> for validating input.
	private $validator;
	
	// Property: <DomainObject::$id>
	// Every qualified <DomainObject> should have a $id-property.
	private $id;
	
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
		
		foreach ( $this->members as $name => $rules )
		{
			if ( isset($rules['default']) )
			{
				$this->values[$name] = $rules['default'];
				unset($rules['default']);
				$this->members[$name] = $rules;
			}
		}
		
		$this->validator = new Validator();
	}
	
	public function set($name, $value)
	{
		if ( ! isset($this->members[$name]) )
		{
			throw new DOMemberNotFoundException($name);
		}
		
		if ( $this->validate($name, $value) )
		{
			$this->values[$name] = $value;
		}
	}
	
	public function get($name)
	{
		if ( ! isset($this->members[$name]) )
		{
			throw new DOMemberNotFoundException($name);
		}
		
		return $this->values[$name];
	}
	
	public function __get($name)
	{
		if ( $name == 'id' )
		{
			return $this->getID();
		}
		return $this->get($name);
	}
	
	public function __set($name, $value)
	{
		if ( $name == 'id' )
		{
			return $this->setID($id);
		}
		return $this->set($name, $value);
	}
	
	public function setID($id)
	{
		if ( ! is_numeric($id) )
		{
			throw new DOFaultyIDException($id);
		}
		
		$this->id = $id;
	}
	
	public function getID()
	{
		return $this->id;
	}
	
	public function fetch()
	{
		return $this->values;
	}
	
	private function validate($name, $input)
	{
		$rules = $this->members[$name];
		
		foreach ( $rules as $rule => $arg )
		{
			$this->validator->validate($input, $rule, $arg);
		}
		
		return true;
	}
}

//Current::initialize();
//Validator::setPath(Current::$config->get('paths.validators'));

class Post extends DomainObject
{
	protected $members = array(
		'header' => array('is_mandatory' => 'yes', 'max_length' => 50),
		'message' => array('is_mandatory' => 'yes'),
		'added' => array('is_date' => 'yes'),
		'edited' => array('is_date' => 'yes')
	);
}

/*printf("<h3>Testing DomainObject</h3>");

$post = new Post();
$post->set("header", "Testing this baby out! " . str_repeat("*", 2));
$post->set("body", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc in purus est, quis semper dolor. Morbi malesuada scelerisque lectus ut fermentum. Integer congue consectetur erat, tristique ultricies dolor eleifend vel. Phasellus mattis feugiat tincidunt. Donec eu ante leo. In ultrices urna leo. Nam lacinia, felis non eleifend rhoncus, mauris risus bibendum ante, id vulputate libero odio ut risus. Cras viverra, leo ut hendrerit bibendum, quam neque mattis purus, vitae porttitor turpis mauris vel risus. Nullam eget urna est, ut fermentum leo. Mauris nec sapien urna. Cras venenatis tempor tellus vel laoreet. Nulla malesuada placerat convallis. Quisque ac accumsan dolor. Suspendisse odio magna, bibendum quis varius in, aliquet non justo. Sed auctor turpis suscipit lectus semper sollicitudin. Suspendisse in turpis nec nulla condimentum tristique vitae eget dolor.");

printf("<p>Passed.</p>");*/
