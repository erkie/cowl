<?php

/*
	Class:
		Routing
	
	Simple routing class, fetching regular expressions from the config.ini and apply them to <Controller>.
*/

class Routing extends Plugin
{
	protected $path;
	
	/*
		Method:
			Routing::pathParse
		
		Parse routes from config.ini and reroute <Controller::$path> if necessary.
	*/
	
	public function prePathParse(Controller $controller, StaticServer $server)
	{
		try {
			$this->path = $controller->getPath();
			
			// Transform routes base on host preferences
			$host_routes = Current::$config->get('plugins.routing.host_routes');
			
			if ( count($host_routes) )
			{
				$host = $_SERVER['HTTP_HOST'];
				
				foreach ( $host_routes as $search => $replace )
					if ( preg_match($search, $host) )
						$this->applyTransform($replace);
			}
			
			// Transform normal routes
			$routes = Current::$config->get('plugins.routing.routes');
			
			if ( count($routes) )
			{
				$this->applyTransform($routes);
			}
			
			$controller->setPath($this->path);
			$server->setPath($this->path);
		} catch ( RegistryException $e ) {}
	}
	
	/*
		Method:
			applyTransform
		
		Apply a tranform to the path.
		
		Parameters:
			$routes - An array of key-value routes/replaces
	*/
	
	private function applyTransform($routes)
	{
		$searches = array_keys($routes);
		$replaces = array_values($routes);
		
		$this->path = preg_replace($searches, $replaces, $this->path);
	}
}
