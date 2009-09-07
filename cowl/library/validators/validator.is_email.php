<?php

function validator_is_email($input, $arg)
{
	return filter_var($input, FILTER_VALIDATE_EMAIL);
}