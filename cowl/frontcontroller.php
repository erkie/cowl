<?php

require('cowl.php');

set_include_path(COWL_DIR . PATH_SEPARATOR . get_include_path());

require('controller.php');
require('command.php');
require('current.php');
require('plugins.php');
require('validator.php');
require('templater.php');
require('viewhelper.php');
require('library.php');

require('library/cache/cache.php');
require('library/orm/db.php');

/*
	Class:
		<FrontController>
	
	The glue binding Cowl together.
*/

class FrontController
{
	// Property: <FrontController::$path>
	// Original URL.
	protected $path;
	
	/*
		Constructor:
			<FrontController::__construct>
		
		Initialize _everything_.
	*/
	
	public function __construct()
	{
		Cache::setDir(COWL_CACHE_DIR);
		Current::initialize(COWL_DIR);
		
		$this->path = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['PHP_SELF']);
		
		list($commands_dir, $plugins_dir, $model_dir, $validators_dir, $library_dir, $tplcache_dir, $view_dir) = 
			Current::$config->gets('paths.commands', 'paths.plugins', 'paths.model',
				'paths.validators', 'paths.library','paths.caches.template',
				'paths.view');
		
		Controller::setDir($commands_dir);	
		$this->controller = new Controller($this->path);
		
		DataMapper::setMappersDir($model_dir);
		DataMapper::setObjectsDir($model_dir);
		Validator::setPath($validators_dir);
		Library::setPath($library_dir);
		Templater::setCacheDir($tplcache_dir);
		Templater::setBaseDir($view_dir);
		
		Current::$plugins = new Plugins($plugins_dir);
	}
	
	/*
		Method:
			<FrontController::execute>
		
		Use <Controller::parse> to parse the path, and call some plugin hooks on the way.
	*/
	
	public function execute()
	{
		Current::$plugins->hook('prePathParse', $this->controller);
		
		// Parse arguments from path and call appropriate command
		$args = $this->controller->parse();
		$instance = new $args['argv'][0];
		
		$view_dir = Current::$config->get('paths.view') . str_replace(Current::$config->get('paths.commands'), '', $args['directory']);
		$instance->setTemplateDir($view_dir);
		
		Current::$plugins->hook('postPathParse', $args);
		
		$instance->run($args);
		
		Current::$plugins->hook('postRun');
	}
}
