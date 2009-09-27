<?php

class TodoList extends DomainObject
{
	protected $members = array(
		'name' => array('is_mandatory' => true, 'max_length' => 75)
	);
}
