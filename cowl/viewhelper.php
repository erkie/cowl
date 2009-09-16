<?php

class VH
{
	public static function url()
	{
		$args = func_get_args();
		echo COWL_BASE . implode('/', $args);
	}
	
	public static function css()
	{
		try {
			$stylesheets = Current::$request->getInfo('css');
			
			foreach ( $stylesheets as $stylesheet )
			{
				printf('<link rel="stylesheet" type="text/css" media="all" href="%s" />', $stylesheet);
			}
		}
		catch ( RequestInfoNotFoundException $e ) {}
	}
}
