<?php

require('cowl.php');

set_include_path(COWL_DIR . PATH_SEPARATOR . get_include_path());

require('controller.php');
require('staticserver.php');
require('command.php');
require('current.php');
require('plugins.php');
require('validator.php');
require('templater.php');
require('library.php');
require('helpers.php');

require('library/cache/cache.php');
require('library/database/database.php');

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
		session_start();
		
		Cache::setDir(COWL_CACHE_DIR);
		Current::initialize(COWL_DIR);
		
		$this->path = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['PHP_SELF']);
		
		// Get and set all directories for various things.
		list(
			$commands_dir, $plugins_dir, $model_dir,
			$validators_dir, $library_dir, $view_dir,
			$helpers_dir, $drivers_dir, $app_dir)
		= 
			Current::$config->gets('paths.commands', 'paths.plugins', 'paths.model',
				'paths.validators', 'paths.library', 'paths.view',
				'paths.helpers', 'paths.drivers', 'paths.app');
		
		Controller::setDir($commands_dir);	
		DataMapper::setMappersDir($model_dir);
		DataMapper::setObjectsDir($model_dir);
		Validator::setPath($validators_dir);
		Templater::setBaseDir($view_dir);
		Library::setPath($library_dir);	
		Helpers::setPath($helpers_dir);
		Database::setPath($drivers_dir);
		StaticServer::setDir($app_dir);
		
		Current::$plugins = new Plugins($plugins_dir);
		
		// Load default helper
		Helpers::load('standard');
	}
	
	/*
		Method:
			<FrontController::execute>
		
		Use <Controller::parse> to parse the path, and call some plugin hooks on the way.
	*/
	
	public function execute()
	{	
		$this->controller = new Controller($this->path);
		$this->static_server = new StaticServer($this->path);
		
		Current::$plugins->hook('prePathParse', $this->controller, $this->static_server);
		
		if ( $this->static_server->isFile() )
		{
			Current::$plugins->hook('preStaticServe', $this->static_server);
			
			// Output the file
			$this->static_server->render();
			
			Current::$plugins->hook('postStaticServe', $this->static_server);
			
			exit;
		}
		
		// Parse arguments from path and call appropriate command
		$args = $this->controller->parse();
		$command = new $args['argv'][0];
		
		// Set template directory, which is the same directory as the command directory
		$view_dir = Current::$config->get('paths.view') . str_replace(Current::$config->get('paths.commands'), '', $args['directory']);
		$command->setTemplateDir($view_dir);
		
		Current::$plugins->hook('postPathParse', $args);
		
		$command->run($args);
		
		Current::$plugins->hook('postRun');
	}
}
