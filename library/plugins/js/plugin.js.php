<?php

include('jsmin.php');

class JS extends Plugin
{
	private $files = array();
	
	public function __construct()
	{
		
	}
	
	public function commandRun(Command $command, $method, RequestData $request)
	{
		// Get packages requested by command
		$js_packages = $command->getJS();
		$packages = Current::$config->get('js');
		
		foreach ( $js_packages as $package )
		{
			$this->files = array_merge($this->files, $packages[$package]);
		}
		
		$this->files = array_map(array($this, 'parsePath'), $this->files);
		
		// This cannot change with the new packaging
		// --
		
		// See if this command has page.commandname.js
		$page_name = 'app/' . $request->app_directory . 'page.' . end($request->pieces) . '.js';
		$page_name = $this->parsePath($page_name);
		
		if ( file_exists($page_name) )
		{
			$this->files[] = $page_name;
			Current::$request->setInfo('js_fire', substr(get_class($command), 0, -strlen('command')));
		}
		
		// --
		
		$this->setFiles();
	}
	
	private function setFiles()
	{
		foreach ( $this->files as $file )
		{
			Current::$request->setInfo('js[]', $file);
		}
	}
	
	private function parsePath($path)
	{
		list($app_js, $system_js) = Current::$config->gets('paths.app_js', 'paths.system_js');
		$path = preg_replace('#^app/#', $app_js, $path);
		$path = preg_replace('#^cowl/#', $system_js, $path);
		return $path;
	}
}
