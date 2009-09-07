<?php

class VH
{
	public static function url()
	{
		$args = func_get_args();
		echo COWL_BASE . implode('/', $args);
	}
}
