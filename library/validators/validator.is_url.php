<?php

function validate_is_url($url, $arg)
{
	if ( ! $arg ) return true;
	
	return (bool)filter_var($url, FILTER_VALIDATE_URL);
}