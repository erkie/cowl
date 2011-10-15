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
	
	public function prePathParse($controller, $server)
	{
		try {
			$routes = Current::$config->get('plugins.routing.routes');
			
			if ( count($routes) )
			{
				$searches = array_keys($routes);
				$replaces = array_values($routes);
				
				$path = preg_replace($searches, $replaces, $controller->getPath());
				$controller->setPath($path);
				$server->setPath($path);
			}
		} catch ( RegistryException $e ) {}
	}
}
