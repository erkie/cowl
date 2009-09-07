<?php

include('../../frontcontroller.php');

$includes_file = COWL_DIR . '/cache/cowl/includes.php';
$includes_cache = new Cache($includes_file);

if ( true || $includes_cache->isOutDated() )
{
	$files = get_included_files();
	array_splice($files, 0, 2);
	
	$includes_cache->update('<?php' . PHP_EOL . 'define(\'COWL_CACHED\', true);' . PHP_EOL);
	foreach ( $files as $file )
	{
		$contents = preg_replace(array('/^<\?php/', '/\?>$/'), '', file_get_contents($file));
		$includes_cache->update($contents, FILE_APPEND);
	}
}
