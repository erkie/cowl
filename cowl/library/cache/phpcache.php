<?php

class PHPCache extends Cache
{
	public function get()
	{
		$result = parent::get();
		
		if ( $result !== false )
		{
			$result = unserialize($result);
		}
		
		return $result;
	}
	
	public function update($contents, $flags = 0)
	{
		$contents = serialize($contents);
		
		parent::update($contents, $flags);
	}
}
