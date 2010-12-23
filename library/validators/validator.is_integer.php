<?php

function validate_is_integer($input, $arg)
{
	if ( ! $arg ) return true;
	return filter_var($input, FILTER_VALIDATE_INT) !== false;
}
