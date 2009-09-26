<?php

/*
	Class:
		<PHPCache>
	
	Store PHP arrays in the cache.
*/

class PHPCache extends Cache
{
	/*
		Method:
			<PHPCache>
		
		Get the contents of the cache and unserialize it.
		
		Returns:
			The unserialized contents or false if the file needs updating.
	*/
	
	public function get()
	{
		$result = parent::get();
		
		if ( $result !== false )
		{
			$result = unserialize($result);
		}
		
		return $result;
	}
	
	/*
		Method:
			<PHPCache::update>
		
		Store the contents as serialized PHP.
		
		Parameters:
			mixed $contents - The contents to be stored
			integer $flags - See <Cache::update>
	*/
	
	public function update($contents, $flags = 0)
	{
		$contents = serialize($contents);
		
		parent::update($contents, $flags);
	}
}
