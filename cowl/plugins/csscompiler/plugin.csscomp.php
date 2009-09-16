<?php

include('csscompiler.php');

class CSS extends Plugin
{
	private $cache;
	private $url_dir;
	private $force_update;
	
	public function __construct()
	{
		list($dir, $url_dir, $cache, $force_update)
			= Current::$config->gets('paths.app', 'paths.urls.css', 'plugins.css.cache', 'plugins.css.force_update');
		
		CSSCompiler::setDir($dir);
		
		$this->url_dir = $url_dir;
		$this->cache = $cache;
		$this->force_update = $force_update;
		
		$this->loadSiteCSS();
	}
	
	public function loadSiteCSS()
	{
		$filename = Current::$config->get('plugins.css.base_css');
		
		$cache = new FileCache($this->cache . '.site', $filename);
		$cache->setExtension('css');
		
		if ( $cache->isOutDated() || $this->force_update )
		{
			$contents = file_get_contents($filename);
			
			$compiler = new CSSCompiler($contents);
			$updated = $compiler->compile();
			
			$cache->update($updated);
		}
		
		Current::$request->setInfo('css', array($this->url_dir . 'site.css'));
	}
	
	public function postPathParse($args)
	{
		$name = explode('.', $args['command']);
		$name = $name[1];
		$file = $args['directory'] . 'style.' . $name . '.css';
		
		if ( file_exists($file) )
		{
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
			
			Current::$request->setInfo('css', array($this->url_dir . $cache_dir . $name . '.css'));
		}
	}
}
