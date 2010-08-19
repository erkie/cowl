<?php

require('revisions.php');

/*
	Class:
		<Versions>
	
	Wrapper for the <Revisions> class.
*/

class Versions extends Plugin
{
	/*
		Constructor:
			<Versions::__construct>
		
		Does all the updating. If a trigger is required (<plugin.versions.trigger> is set to a truthy value) in the configuration file, it will only be run if __run_versions is set in $_REQUEST.
	*/
	
	public function __construct()
	{
		$trigger = Current::$config->get('plugins.versions.trigger');
		
		// Run only if forced, or trigger is not needed
		if ( ($trigger && Current::$request->has('__run_versions') ) || ! $trigger )
		{
			$path = Current::$config->get('plugins.versions.revisions');
			
			// Get latest revision run
			$cache = new Cache('plugin.versions');
			$current = $cache->get();
			
			$rev = new Revisions();
			$rev->setDir($path);
			
			// Update all and set new current revision
			$updated = $rev->update($current);
			$cache->update($updated);
		}
	}
}