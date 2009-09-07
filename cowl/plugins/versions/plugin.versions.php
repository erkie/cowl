<?php

require('revisions.php');

class Versions extends Plugin
{
	public function __construct()
	{
		return; // not done yet
		list($path, $cache) = Current::$config->gets('plugins.versions.revisions', 'plugins.versions.cache');
		
		$rev = new Revisions();
		$rev->setDir($path);
		$rev->setCache($cache);
		
		$rev->uptodate();
		
		//echo 'Loading new database revisons<br />';
	}
}