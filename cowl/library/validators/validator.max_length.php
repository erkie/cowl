<?php

function validate_max_length($value, $length)
{
	return strlen($value) <= $length;
}