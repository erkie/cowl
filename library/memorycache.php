<?php

abstract class MemoryCacheBase
{
	abstract public function set($key, $var, $expire);
	abstract public function get($key);
	abstract public function delete($key);
}

if ( ! class_exists('memcache') )
{
	// Memcache is not installed on this server, so we just create big noop cache class
	// this should work quite nicely since that's how memcache is supposed to work
	class MemoryCache extends MemoryCacheBase
	{
		public function set($key, $var, $expire) {}
		public function get($key) {}
		public function delete($key) {}
	}
}
else
{
	class MemoryCacheConnectException extends Exception {}

	/*
		Class:
			MemoryCache
	
		MemCache wrapper. NOT a subclass of <Cache>
	*/

	class MemoryCache
	{
		// Property: MemoryCache::$cache
		// Holds the instance to memcache
		private $cache;
	
		public function __construct($host = 'localhost', $port = 11211)
		{
			$this->cache = new MemCache();
			$this->cache->connect($host, $port);
			
			if ( ! $this->cache )
			{
				throw new MemoryCacheConnectException($host . ':' . $port);
			}
		}
		
		public function set($key, $var, $expire)
		{
			$this->cache->set($key, $var, 0, $expire);
		}
		
		public function get($key)
		{
			return $this->cache->get($key);
		}
		
		public function delete($key)
		{
			$this->cache->delete($key);
		}
	}
}