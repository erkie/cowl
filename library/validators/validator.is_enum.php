<?php

function validate_is_enum($value, $arg)
{
	return in_array($value, $arg);
}