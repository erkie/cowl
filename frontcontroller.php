<?php

require('cowl.php');

set_include_path(COWL_DIR . PATH_SEPARATOR . get_include_path());

Cowl::timer('cowl require system');

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

Cowl::timerEnd('cowl require system');

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
		Cowl::timer('cowl init');
		
		@session_start(); // I know that the @-notation is frowned upon, but adding it to session_start saves us unnecessary warnings
		
		Cache::setDir(COWL_CACHE_DIR);
		Current::initialize(COWL_DIR);

		if ( COWL_CLI )
			$this->parseCLIPath();
		else
			$this->parseRequestPath();
		
		Cowl::timer('cowl set defaults');
		
		// Get and set all directories for various things.
		list(
			$commands_dir, $model_dir, $validators_dir,
			$library_dir, $view_dir, $helpers_dir,
			$helpers_app_dir, $drivers_dir, $app_dir,
			$view_layout_dir, $validator_error_messages, $lang)
		= 
			Current::$config->gets('paths.commands', 'paths.model',
				'paths.validators', 'paths.library', 'paths.view',
				'paths.helpers', 'paths.helpers_app', 'paths.drivers', 'paths.app',
				'paths.layouts', 'paths.validator_messages', 'lang');
		
		Controller::setDir($commands_dir);	
		DataMapper::setMappersDir($model_dir);
		DataMapper::setObjectsDir($model_dir);
		Validator::setPath($validators_dir);
		Validator::loadStrings($validator_error_messages, $lang);
		Templater::setBaseDir($view_dir);
		Templater::setLayoutDir($view_layout_dir);
		Library::setPath($library_dir);	
		Helpers::setPath($helpers_dir);
		Helpers::setAppPath($helpers_app_dir);
		Database::setPath($drivers_dir);
		StaticServer::setDir($app_dir);
		
		Cowl::timerEnd('cowl set defaults');
		
		Cowl::timer('cowl plugins load');
		Current::$plugins = new Plugins();
		Cowl::timerEnd('cowl plugins load');
		
		// Load default helper
		Helpers::load('standard', 'form');
		
		Cowl::timerEnd('cowl init');
	}
	
	private function parseCLIPath()
	{
		$this->path = $_SERVER['argv'][1];
		
		Controller::$SEPARATOR = ':';
	}
	
	private function parseRequestPath()
	{
		$uri = explode('?', $_SERVER['REQUEST_URI']);
		$uri = preg_replace('#^/index.php#', '', $uri[0]);
		$uri = substr($uri, strlen(COWL_BASE)-1);
		$this->path = $uri;
	}
	
	/*
		Method:
			fixRequestForCLI
	
	 	When running through CLI the script is called as follows:
			
			$ php index.php forum:post about-bacon
			... 
			$ <cowl> subcommand:command:action param1 param2 ...
		
		So instead of the conventional URLs that are /subcommand/command/param1/param2/action
		
		Thusly the arguments for the method has to be interlaced correctly.
	*/
	
	private function fixRequestForCLI($request)
	{		
		$args = array_slice($_SERVER['argv'], 2);
		$argv = $request->argv;
		$request->argv = array_merge(array_slice($argv, 0, count($argv)-1 ?: 1), $args);
		
		if ( count($argv) > 1 )
		 	$request->argv[] = $argv[count($argv)-1];
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
		Cowl::timer('cowl parse');
		$request = $this->controller->parse();
		Cowl::timerEnd('cowl parse');
	
		if ( COWL_CLI )
		{
			$this->fixRequestForCLI($request);
		}
		
		$command = new $request->argv[0];
		
		// Set template directory, which is the command directory mirrored
		$command->setTemplateDir(Current::$config->get('paths.view') . $request->app_directory);
		
		Current::$plugins->hook('postPathParse', $request);
		
		Cowl::timer('cowl command run');
		$ret = $command->run($request);
		Cowl::timerEnd('cowl command run');
		
		Current::$plugins->hook('postRun');
		
		if ( is_string($ret) )
		{
			Cowl::redirect($ret);
		}
	}
}
