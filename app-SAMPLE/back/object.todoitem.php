<?php

class TodoItem extends DomainObject
{
	protected $members = array(
		'list_id' => array('is_mandatory' => true, 'is_id' => true),
		'todo' => array('is_mandatory' => true, 'max_length' => 100),
		'is_done' => array('default' => false)
	);
}
