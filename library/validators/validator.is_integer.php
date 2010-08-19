<?php

function validate_is_integer($input, $arg)
{
	return filter_var($input, FILTER_VALIDATE_INT) !== false;
}
