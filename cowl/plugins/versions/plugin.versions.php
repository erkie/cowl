<?php

require('revisions.php');

class Versions extends Plugin
{
	public function __construct()
	{
		$trigger = Current::$config->get('plugins.versions.trigger');
		
		if ( ($trigger && Current::$request->has('__run_versions') ) || ! $trigger )
		{
			$path = Current::$config->get('plugins.versions.revisions');
			
			$cache = new Cache('plugin.versions');
			$current = $cache->get();
			
			$rev = new Revisions();
			$rev->setDir($path);
			
			$updated = $rev->update($current);
			$cache->update($updated);
		}
	}
}