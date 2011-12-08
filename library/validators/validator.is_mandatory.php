<?php

function validate_is_mandatory($value, $arg)
{
	// if is_mandatory is false everything is valid
	if ( ! $arg ) return true;
	if ( $value === "0" || $value === 0 )
		return true;
	return ! empty($value);
}