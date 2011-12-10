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
			// Transform routes base on host preferences
			$host_routes = Current::$config->get('plugins.routing.host_routes');
			
			if ( count($host_routes) )
			{
				$host = $_SERVER['HTTP_HOST'];
				
				foreach ( $host_routes as $search => $replace )
				{
					if ( preg_match($search, $host) )
					{
						$searches = array_keys($replace);
						$replaces = array_values($replace);
						$path = preg_replace($searches, $replaces, $controller->getPath());
						$controller->setPath($path);
						$server->setPath($path);
					}
				}
			}
			
			// Transform normal routes
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
