<?php

class NameTools
{
	public static function toSlug($name)
	{
		$name = strtolower($name);
		$name = str_replace(array('å', 'ä', 'ö', 'Å', 'Ä', 'Ö'), array('a', 'a', 'o', 'a', 'a', 'o'), $name);
		$name = preg_replace('/\W|\s/', '-', $name);
		$name = preg_replace('/\-\-{1,}/', '-', $name);
		$name = trim($name, '-');
		return $name;
	}
}
