<?php

include('csscompiler.php');

/*
	Class:
		CSS
	
	The goal of this plugin is to minimize the amount of needed requests for CSS-files. The goal is to keep the number of requests down to 2.
	
	This plugin uses the <CSSCompiler> to compile CSS files.
*/

class CSS extends Plugin
{
	// Property: CSS::$cache
	// The path to the cache, used in <Cache>-objects. Will always hold the value of <paths.css.cache>
	private $cache;
	
	// Property: CSS::$url_dir
	// The directory for which requests are pointed to. Will always hold the value of <paths.urls.css>
	private $url_dir;
	
	// Property: CSS::$packaged_dir
	// The directory for which packaged css requests go to. Used for production
	private $packaged_dir;
	
	// Property: CSS::$force_update
	// A boolean value set to true if CSS files should be updated on every request, nifty for development enivroments. Holds the value of <plugins.css.force_update>.
	// However, if set to false, the compiled CSS file will still be updated if the source CSS is updated, but if any dependency is updated, it will not get updated.
	private $force_update;
	
	/*
		Constructor
		
		Initialize everything. Will also call <CSS::loadSiteCSS>.
	*/
	
	public function __construct()
	{
		list($dir, $url_dir, $cache, $packaged_dir, $force_update, $release_tag)
			= Current::$config->gets('paths.top', 'paths.urls.css', 'plugins.css.cache',
			 			'paths.urls.css_packaged', 'plugins.css.force_update', 'release_tag');
		
		CSSCompiler::setDir($dir);
		
		$this->url_dir = COWL_BASE . $url_dir;
		$this->packaged_dir = COWL_BASE . $packaged_dir;
		$this->cache = $release_tag . '.' . $cache;
		$this->force_update = $force_update;
		
		Current::$request->setInfo('css', array());
	}
	
	/*
		Method:
			CSS::commandRun
		
		Get the commands specified and CSS packages
	*/
	
	public function commandRun(Command $command, $method, $args)
	{
		$mode = Current::$config->get('mode');
		
		$packages = $command->getCSS();
		if ( $mode == 'production' )
		{
			$this->packageForProduction($packages);
		}
		else
		{
			$this->packageForDevelopment($packages);
		}
	}
	
	private function packageForDevelopment($packages)
	{
		$assets = Current::$config->get('css');
		$files = Current::$request->getInfo('css');
		
		// Merge all files into an array
		foreach ( $packages as $package )
		{
			$files = array_merge($files, $assets[$package]);
		}
		
		list($app_css_path, $css_path) = Current::$config->gets('paths.app_css', 'paths.urls.css');
		
		// Transform into correct paths
		foreach ( $files as $key => $file )
		{
			$files[$key] = Cowl::url(str_replace($app_css_path, $css_path, $file));
		}
		
		Current::$request->setInfo('css', $files);
	}
	
	private function packageForProduction($packages)
	{
		$files = Current::$request->getInfo('css');
		
		foreach ( $packages as $package )
		{
			$files[] = $this->packaged_dir . $package . '.css';
		}
		
		Current::$request->setInfo('css', $files);
	}
	
	/*
		Method:
			CSS::getFilesForPackage
		
		Get files for a certain package. Returns false if it doesn't exist.
		
		Parameters:
			$package - The package name, as defined in the 'css' => array() in your config file
		
		Returns:
			An array of files, or false if it doesn't exist
	*/
	
	private function getFilesForPackage($package)
	{
		$assets = Current::$config->get('css');
		if ( ! isset($assets[$package]) )
		{
			return false;
		}
		return $assets[$package];
	}
	
	/*
		Method:
			CSS:prePathParse
		
		Checks if is a request to a release CSS file and routes it accordingly.
		Packaging if necessesary.
	*/
	
	public function prePathParse(Controller $controller, StaticServer $server)
	{
		$path = strtolower($server->getPath());
		
		// Path begins with release path
		if ( strncmp($path, $this->packaged_dir, strlen($this->packaged_dir)) !== 0 )
			return;
		
		// Get package name from request path
		$package = preg_replace('#\.css$#', '', substr($path, strlen($this->packaged_dir)));
		$files = $this->getFilesForPackage($package);
		
		// Package actually exists
		if ( ! $files )
			return;
		
		$cache_path = $this->cache . '.' . $package;
		
		$cache = new FileCache($cache_path, $files);
		$cache->setExtension('css');
		
		if ( $cache->isOutDated() || $this->force_update )
		{
			// Make the compiler compile all the files together
			$contents = '';
			foreach ( $files as $file )
			{
				$contents .= file_get_contents($file);
			}
			
			$compiler = new CSSCompiler($contents);
			$updated = $compiler->compile();
			
			$cache->update($updated);
		}
		
		$server->setPath($cache->getFile());
		$controller->setPath($cache->getFile());
	}

	/*
		Method:
			CSS::preStaticServe
		
		This hook will be called when a CSS file has been requested
		
		Parameters:
			$args - The $argv array of the request.
	*/

	public function preStaticServe(StaticServer $server)
	{
		// If the type isn't css we don't touch it
		if ( $server->getType() != 'css' )
			return;

		$path = $server->getPath();
		$cache_path = $this->cache . '.' . preg_replace('#\W#', '', $path);

		// Compile and cache CSS file.
		$cache = new FileCache($cache_path, $path);
		$cache->setExtension('css');

		if ( $cache->isOutDated() || $this->force_update )
		{
			$contents = file_get_contents($path);

			$compiler = new CSSCompiler($contents);
			$updated = $compiler->compile();

			$cache->update($updated);
		}

		// Change the path to be the cached file instead
		$server->setPath($cache->getFile());
	}
}
