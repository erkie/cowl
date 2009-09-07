<?php

class Test extends DomainObject
{
	protected $members = array(
		'value' => array('is_mandatory' => 'yes', 'max_length' => 50)
	);
}
