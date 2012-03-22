<?php

include('jsmin.php');

class JS extends Plugin
{
	// Property: JS::$force_update
	// Force update compilation and compression of JS files.
	private $force_update = false;
	
	// Property: JS::$files
	// Array of files for the request
	private $files = array();

	// Property: JS::$packaged_dir
	// The directory for which packaged JS requests go to. Used for production
	private $packaged_dir;
	
	// Property: JS::$is_package
	private $is_package = true;
	
	public function __construct()
	{
		// Just set some paths and config options
		
		list($packaged_dir, $cache, $release_tag, $force_update) = Current::$config->gets(
			'paths.urls.js_packaged', 'plugins.js.cache', 'release_tag',
			'plugins.js.force_update'
		);
		
		$this->packaged_dir = $packaged_dir;
		$this->cache = $release_tag . '.' . $cache;
		$this->force_update = $force_update;
	}
	
	/*
		Method:
			JS::commandRun
		
		This method is called after a command is run to determine which JS files to be included.
	*/

	public function commandRun(Command $command, $method, $request)
	{
		$mode = Current::$config->get('mode');
		$js_packages = $command->getJS();
		
		if ( $mode == 'production' )
		{
			$this->packageForProduction($js_packages);
		}
		else
		{
			$this->packageForDevelopment($js_packages, $command, $request);
		}
		
		// This cannot change with the new packaging
		// Unless we package all pages into one file... which would be nice. But cost in performance?
		// --
		
		// See if this command has page.commandname.js
		$page_name = 'app/' . $request->app_directory . 'page.' . end($request->pieces) . '.js';
		$page_name = $this->parsePath($page_name);
		
		if ( file_exists($page_name) )
		{
			$this->files[] = $page_name;
			Current::$request->setInfo('js_fire', substr(get_class($command), 0, -strlen('command')));
			Current::$request->setInfo('js_page_path', Cowl::url($page_name));
		}
		// --
		
		$this->setFiles();
	}
	
	/*
		Method:
			JS::packageForDevelopment
		
		Package files for development. This will just include each file seperately.
		
		Parameters:
			(array) $js_packages - Packages from assets.php
			(Command) $command - The command that was run
			(RequestData) $request - Request data for current request
	*/
	
	public function packageForDevelopment($js_packages, Command $command, RequestData $request)
	{
		$packages = Current::$config->get('js');
		
		// Just flatten the packages files to include them without magic
		foreach ( $js_packages as $package )
		{
			$this->files = array_merge($this->files, $packages[$package]);
		}
		
		// Parse JS paths
		$this->files = array_map(array($this, 'parsePath'), $this->files);
	}
	
	/*
		Method:
			JS::packageForProduction
		
		Package files for production. This method takes the package names and transforms
		them based on how the config file wants them to look. E.g:
		
			$ js/release_core.js
		
		Parameters:
			(array) $js_packages - All packages from assets.php
	*/
	
	public function packageForProduction($js_packages)
	{	
		$packages = Current::$config->get('js');
		$this->files = Current::$request->getInfo('js');
		
		foreach ( $js_packages as $package )
		{
			$this->files[] = $this->packaged_dir . $package . '.js';
		}
	}
	
	/*
		Method:
			JS::setFiles
		
		Set files from JS::$files to the request info.
	*/
	
	private function setFiles()
	{
		if ( ! is_array($this->files) )
			return;
		
		foreach ( $this->files as $file )
		{
			Current::$request->setInfo('js[]', $file);
		}
	}
	
	/*
		Method:
			JS::getFilesForPackage
		
		Get files for a certain package. Returns false if it doesn't exist.
		
		Parameters:
			$package - The package name, as defined in the 'js' => array() in your config file
		
		Returns:
			An array of files, or false if it doesn't exist
	*/
	
	private function getFilesForPackage($package)
	{
		$assets = Current::$config->get('js');
		if ( ! isset($assets[$package]) )
		{
			return false;
		}
		return $assets[$package];
	}
	
	/*
		Method:
			JS:prePathParse
		
		Checks if is a request to a release JS file and routes it accordingly.
		Packaging if necessesary.
	*/
	
	public function prePathParse(Controller $controller, StaticServer $server)
	{
		$path = strtolower($server->getPath());
		$url_path = COWL_BASE . $this->packaged_dir;
		
		// Path begins with release path, then we build it if need be
		if ( strncmp($path, $url_path, strlen($url_path)) !== 0 )
			return;
		
		// Get package name from request path
		$package = preg_replace('#\.js$#', '', substr($path, strlen($url_path)));
		$filepath = $this->buildPackage($package);
		
		// No package here
		if ( ! $filepath )
			return;
		
		$server->setPath($filepath);
		$controller->setPath($filepath);

		$server->lockPath();
		$this->is_package = true;
	}
	
	/*
		Method:
			JS::buildPackage
		
		Build package. Takes the package name, minifies and combines the related files to one file.
		
		Returns:
			The path to the file on disk
	*/

	private function buildPackage($package)
	{
		$files = $this->getFilesForPackage($package);
		
		// Package actually exists
		if ( ! $files )
			return;

		$cache_path = $this->cache . '.' . $package;
		
		$cache = new Cache($cache_path, 60*60*24*365);
		$cache->setExtension('js');
		
		if ( $cache->isOutDated() || $this->force_update )
		{
			// Make the compiler compile all the files together
			$contents = '';
			foreach ( $files as $file )
			{
				$contents .= file_get_contents($this->parsePath($file));
			}
			
			$updated = JSMin::minify($contents);
			$cache->update($updated);
		}
		
		return $cache->getFile();
	}
	
	/*
		Method:
			JS::buildAll
		
		Build all packages at once. Useful for automated building.
	*/
	
	public function buildAll($force = true)
	{
		$this->force_update = $force;
		
		$packages = array_keys(Current::$config->get('js'));
		
		$this->packageForProduction($packages);
		array_walk($packages, array($this, 'buildPackage'));
	}
	
	private function parsePath($path)
	{
		list($app_js, $system_js) = Current::$config->gets('paths.app_js', 'paths.system_js');
		$path = preg_replace('#^app/#', $app_js, $path);
		$path = preg_replace('#^cowl/#', $system_js, $path);
		return $path;
	}
}
