<?php

include('csscompiler.php');

/*
	Class:
		<CSS>
	
	The goal of this plugin is to minimize the amount of needed requests for CSS-files. The goal is to keep the number of requests down to 2.
	
	This plugin uses the <CSSCompiler> to compile CSS files.
*/

class CSS extends Plugin
{
	// Property: <CSS::$cache>
	// The path to the cache, used in <Cache>-objects. Will always hold the value of <paths.css.cache>
	private $cache;
	
	// Property: <CSS::$url_dir>
	// The directory for which requests are pointed to. Will always hold the value of <paths.urls.css>
	private $url_dir;
	
	// Property: <CSS::$force_update>
	// A boolean value set to true if CSS files should be updated on every request, nifty for development enivroments. Holds the value of <plugins.css.force_update>.
	// However, if set to false, the compiled CSS file will still be updated if the source CSS is updated, but if any dependency is updated, it will not get updated.
	private $force_update;
	
	/*
		Constructor:
			<CSS::__construct>
		
		Initialize everything. Will also call <CSS::loadSiteCSS>.
	*/
	
	public function __construct()
	{
		list($dir, $url_dir, $cache, $force_update)
			= Current::$config->gets('paths.app', 'paths.urls.css', 'plugins.css.cache', 'plugins.css.force_update');
		
		CSSCompiler::setDir($dir);
		
		$this->url_dir = COWL_BASE . $url_dir;
		$this->cache = $cache;
		$this->force_update = $force_update;
		
		$this->loadSiteCSS();
	}
	
	/*
		Method:
			<CSS::loadSiteCSS>
		
		Create one of the two compiled CSS files. The site.css is the core CSS file, containing rules for the site, which are used on every page.
	*/
	
	public function loadSiteCSS()
	{
		$filename = Current::$config->get('plugins.css.base_css');
		
		// Add .css extension to cached file, so it can be requested by a browser
		$cache = new FileCache($this->cache . '.site', $filename);
		$cache->setExtension('css');
		
		// Update cache
		if ( $cache->isOutDated() || $this->force_update )
		{
			$contents = file_get_contents($filename);
			
			$compiler = new CSSCompiler($contents);
			$updated = $compiler->compile();
			
			$cache->update($updated);
		}
		
		// Append the URL for the site-specific CSS-file to the <Request> registry object.
		Current::$request->setInfo('css', array($this->url_dir . 'site.css'));
	}
	
	/*
		Method:
			<CSS::postPathParse>
		
		This is a hook, meant to be called when the requested command has been determined. If the package directory contains a commandname.css-file, it will be compiled and added to this request.
		
		Parameters:
			$args - The $argv array of the request.
	*/
	
	public function postPathParse($args)
	{
		// Get supposed style file
		$name = explode('.', $args['command']);
		$name = $name[1];
		$file = $args['directory'] . $name . '.css';
		
		if ( file_exists($file) )
		{
			// Make cache name, which is dependent on the command
			$cache_dir = str_replace(Current::$config->get('paths.commands'), '', $args['directory']);
			$cache_name = $this->cache . '.' . $cache_dir . $name;
			
			$cache = new Cache($cache_name, $_SERVER['REQUEST_TIME'] - filemtime($file));
			$cache->setExtension('css');
			
			if ( $cache->isOutDated() || $this->force_update )
			{
				$contents = file_get_contents($file);
				$compiler = new CSSCompiler($contents);
				$updated = $compiler->compile();
				
				$cache->update($updated);
			}
			
			// Add to current request
			Current::$request->setInfo('css', array($this->url_dir . $cache_dir . $name . '.css'));
		}
	}
}
