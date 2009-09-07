<?php

/*
	Class:
		<Routing>
	
	Simple routing class, fetching regular expressions from the config.ini and apply them to <Controller>.
*/

class Routing extends Plugin
{
	/*
		Hook:
			<Routing::pathParse>
		
		Parse routes from config.ini and reroute <Controller::$path> if necessary.
	*/
	
	public function pathParse($controller)
	{
		$routes = Current::$config->get('plugins.routing');
		
		if ( count($routes['routes']) )
		{
			$path = preg_replace($routes['routes'], $routes['replaces'], $controller->getPath());
			$controller->setPath($path);
		}
	}
}