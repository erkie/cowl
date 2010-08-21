<?php

function validator_is_boolean($subject, $arg)
{
	if ( ! $arg ) return true;
	return $subject == false || $subject == true;
}
