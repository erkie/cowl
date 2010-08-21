<?php

function validate_is_mandatory($value, $arg)
{
	// if is_mandatory is false everything is A-OK
	if ( ! $arg ) return true;
	return ! empty($value);
}