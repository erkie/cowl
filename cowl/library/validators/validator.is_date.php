<?php

function validate_is_date($date)
{
	$time = $date;
	
	if ( ! is_numeric($time) )
	{
		$time = strtotime($time);
		if ( ! is_numeric($time) )
		{
			return false;
		}
	}
	
	$month = date('m', $time);
	$day = date('d', $time);
	$year = date('Y', $time);
	
	if ( checkdate($month, $day, $year) )
	{
		return true;
	}
	
	return false;
}