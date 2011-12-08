<?php

include('jsmin.php');

class JS extends Plugin
{
	private $files = array();
	
	public function __construct()
	{
		$default_js = Current::$config->get('plugins.js.standard');
		$this->files = array_map(array($this, 'parsePath'), $default_js);
	}
	
	public function commandRun(Command $command, $method, RequestData $request)
	{
		// Get files requested by command
		$js = $command->getJS();
		
		$files = array();
		foreach ( $js as $key => $file )
		{
			// Entries with a numeric key are files used for all methods of the command
			if ( is_numeric($key) )
			{
				$files[] = $file;
			}
		}
		
		// Entries specific to that method
		if ( isset($js[$method]) && is_array($js[$method]) )
		{
			$files = array_merge($files, $js[$method]);
		}
		
		$files = array_map(array($this, 'parsePath'), $files);
		$this->files = array_merge($this->files, $files);
		
		// See if this command has page.commandname.js
		$page_name = 'app/' . $request->app_directory . 'page.' . end($request->pieces) . '.js';
		$page_name = $this->parsePath($page_name);
		
		if ( file_exists($page_name) )
		{
			$this->files[] = $page_name;
			Current::$request->setInfo('js_fire', substr(get_class($command), 0, -strlen('command')));
		}
		
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
