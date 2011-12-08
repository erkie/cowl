<?php

function validate_is_float($val, $arg)
{
	if ( ! $arg ) return true;
	return filter_var($val, FILTER_VALIDATE_FLOAT) !== false;
}