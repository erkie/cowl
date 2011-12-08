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
		@session_start(); // I know that the @-notation is frowned upon, but adding it to session_start saves us unnecessary warnings
		
		Cache::setDir(COWL_CACHE_DIR);
		Current::initialize(COWL_DIR);
		
		$this->path = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['PHP_SELF']);
		
		// Get and set all directories for various things.
		list(
			$commands_dir, $plugins_dir, $model_dir,
			$validators_dir, $library_dir, $view_dir,
			$helpers_dir, $drivers_dir, $app_dir,
			$view_layout_dir, $validator_error_messages)
		= 
			Current::$config->gets('paths.commands', 'paths.plugins', 'paths.model',
				'paths.validators', 'paths.library', 'paths.view',
				'paths.helpers', 'paths.drivers', 'paths.app',
				'paths.layouts', 'paths.validator_messages');
		
		Controller::setDir($commands_dir);	
		DataMapper::setMappersDir($model_dir);
		DataMapper::setObjectsDir($model_dir);
		Validator::setPath($validators_dir);
		Validator::loadStrings($validator_error_messages);
		Templater::setBaseDir($view_dir);
		Templater::setLayoutDir($view_layout_dir);
		Library::setPath($library_dir);	
		Helpers::setPath($helpers_dir);
		Database::setPath($drivers_dir);
		StaticServer::setDir($app_dir);
		
		Current::$plugins = new Plugins($plugins_dir);
		
		// Load default helper
		Helpers::load('standard', 'form');
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
		$request = $this->controller->parse();
		$command = new $request->argv[0];
		
		// Set template directory, which is the command directory mirrored
		$command->setTemplateDir(Current::$config->get('paths.view') . $request->app_directory);
		
		Current::$plugins->hook('postPathParse', $request);
		
		$command->run($request);
		
		Current::$plugins->hook('postRun');
	}
}
