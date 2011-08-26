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
	
	public function prePathParse($controller)
	{
		try {
			$routes = Current::$config->get('plugins.routing.route');
			$replaces = Current::$config->get('plugins.routing.replace');
			
			if ( count($routes) )
			{
				$path = preg_replace($routes, $replaces, $controller->getPath());
				$controller->setPath($path);
			}
		} catch ( RegistryException $e ) {}
	}
}
