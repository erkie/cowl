<?php

class TodoItem extends DomainObject
{
	protected $members = array(
		'todo' => array('is_mandatory' => true, 'max_length' => 100),
		'is_done' => array('default' => false)
	);
}