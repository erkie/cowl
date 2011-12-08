<?php

function validate_must_match($input, $regex = '')
{
	return preg_match('~' . $regex . '~', $input);
}