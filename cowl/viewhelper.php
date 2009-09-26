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
		$stylesheets = Current::$request->getInfo('css');
		
		if ( is_array($stylesheets) )
		{
			foreach ( $stylesheets as $stylesheet )
			{
				printf('<link rel="stylesheet" type="text/css" media="all" href="%s" />', $stylesheet);
			}
		}
	}
	
	public static function js()
	{
		$scripts = Current::$request->getInfo('js');
		
		if ( is_array($scripts) )
		{
			foreach ( $scripts as $script )
			{
				printf('<script type="text/javascript" href="%s"></script>', $script);
			}
		}
	}
	
	public static function onlyIf($test, $if_true, $if_false = '')
	{
		if ( $test )
		{
			echo $if_true;
		}
		else
		{
			echo $if_false;
		}
	}
	
	public static function toOptions(DomainCollection $collection, $key_value, $key_text, $selected = null)
	{
		$html = '';
		
		foreach ( $collection as $object )
		{
			$value = $object->get($key_value);
			$is_selected = ($value == $selected) ? 'selected="selected" ' : '';
			
			$html .= sprintf('<option %svalue="%s">%s</option>', $is_selected, $value, $object->get($key_text));
		}
		echo $html;
	}
}
