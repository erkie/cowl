<?php

/*
	Class:
		<Controller>
	
	Core routing.
*/

class Controller
{
	const SEPARATOR = '/';
	
	// Property: <Controller::$path>
	// The path to be parsed
	private $path;
	
	// Property: <Controller::$commands_dir>
	// The directory in where the commands lie
	private static $commands_dir = 'commands/';
	
	// Property: <Controller::$default_type>
	// The default response type for the argv-array
	private static $default_type = 'html';
	
	// Property: <Controller:$use_require_once>
	// Flag wether to use require or require_once when including files. Use only when testing
	private static $use_require_once = false;
	
	// Property: <Controller::$error>
	// The error-command. Must be in the <Controller::$dir>-directory
	private $error_command = 'ErrorCommand';
	
	// Property: <Controller::$default>
	// The default command. Used when, for example, <Controller::$path> is empty.
	private $default_command = 'MainCommand';
	
	// Property: <Controller::$current_dir>
	// The directory of the currently included <Command>.
	private $current_dir;	
	
	// Property: <Controller::$is_error>
	// A flag that is set to true if a command cannot be found, in which case the Error command used in <Controller::parse>
	private $is_error = false;
	
	/*
		Constructor:
			<Controller::__construct>
		
		Prepares the variables so that everything is in order for the <Controller::parse>-call.
		
		Parameters:
			$path - The path to be parsed
		
		Dependencies:
			- <Current>
	*/
	
	public function __construct($path)
	{
		$this->path = $path;
	}
	
	/*
		Method:
			<Controller::parse>
		
		Parses <Controller::$path> and determines an appropriate command. Following these conventions:
		
		1. Check for an empty URI. Use main-command in base of directory.
		2. Check for "namespaced" commands. Traverse to the innermost existing directory in the URI.
		3. Check for command in base of directory.
		4. No other matches at this point result in the Error-command.
		
		Returns:
			Returns an instant of <RequestData> with the correct fields filled in.
	*/
	
	public function parse()
	{
		$request_data = new RequestData;
		$request_data->base_directory = self::$commands_dir;
		
		$is_error = false;
		
		// If a file-ending is specified in the URL
		if ( $this->hasFileEnding() )
		{
			$request_data->response_type = $this->getFileEndingFromPath();
			
			// Every filetype is considered valid as far as <Controller> is concerned. This class
			// should not care about the validity of this, because you might want to add your own
			// and it is dependant on the type of shells in the app/-directory. So this for templater
			// to check itself.
		}
		else
		{
			$request_data->response_type = $this->getSuitableResponseType();
		}
		
		$pieces = $this->getPiecesFromPath();
		
		$this->pieces = $pieces;
		$original_pieces = $pieces;
		
		// No command? Go to main
		if ( $this->isRequestEmpty() )
		{
			$request_data->app_directory = '';
			$request_data->command = 'command.main.php';
			$request_data->pieces = array('main');
			$request_data->argv = array($this->default_command);
		}
		// Start searching for commands
		else
		{
			// Start by searching inwards
			$this->directories = $pieces;
			$glued = implode(DIRECTORY_SEPARATOR, $this->directories);
			
			// Traverse to the innermost directory, checking for packages
			while ( count($this->directories) && ! is_dir(self::$commands_dir . $glued) )
			{
				array_pop($this->directories);
				$glued = implode(DIRECTORY_SEPARATOR, $this->directories);
			}
			
			// Command in base directory
			if ( $this->isCommandInBaseDirectory() )
			{
				$request_data->directory = self::$commands_dir;
				$request_data->pieces = array($pieces[0]);
				
				$command_file = 'command.' . $pieces[0] . '.php';
				
				// Does it even exist in the base directory?
				if ( file_exists($request_data->directory . $command_file) )
				{
					$command_name = array_shift($pieces) . 'Command';
					
					$request_data->argv = array_merge(array($command_name), $pieces);
					$request_data->command = $command_file;
				}
				else
				{
					$is_error = true;
				}
			}
			// Command in sub-directory
			else
			{
				$command = implode('', $this->directories) . 'Command';
				$args = array_slice($pieces, count($this->directories));
				
				$request_data->app_directory = implode(DIRECTORY_SEPARATOR, $this->directories) . DIRECTORY_SEPARATOR;
				$request_data->command = 'command.' . end($this->directories) . '.php';
				$request_data->argv = array_merge(array($command), $args);
				$request_data->pieces = $this->directories;
			}
		}
		
		// No matching command found. 
		if ( $is_error )
		{
			$request_data->directory = self::$commands_dir;
			$request_data->command = 'command.error.php';
			$request_data->argv = array_merge(array($this->error_command), $pieces);
		}
		
		// require_once is used when testing only, because a test might include the same command several times
		// otherwise require should be used, because the performance penalty used is just unnecesary when you
		// know you are only going to include one command per request.
		$file_to_include = $request_data->base_directory . $request_data->app_directory . $request_data->command;
		
		if ( ! self::$use_require_once)
			require($file_to_include);
		else
			require_once($file_to_include);
		
		// lowercase the commandname because the camelcased:nes of that name is not reliable
		$request_data->argv[0] = strtolower($request_data->argv[0]);
		
		$request_data->original_request = $original_pieces;
		
		return $request_data;
	}
	
	// Parsing related methods
	
	public function isRequestEmpty()
	{
		return ! count($this->pieces) || empty($this->pieces[0]);
	}
	
	public function hasFileEnding()
	{
		return preg_match('/\.[A-Za-z0-9]+$/', $this->path);
	}
	
	public function getFileEndingFromPath()
	{
		$period = strrpos($this->path, '.');
		
		// FIXME: Refactor this. The $path-variable should not be set here
		$path = substr($this->path, 0, $period);
		$ret = strtolower(substr($this->path, $period + 1));
		$this->path = $path;
		
		return $ret;
	}
	
	public function getSuitableResponseType()
	{
		// Return JSON of is an ajax-request otherwise <Controller::$default_type>
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_REQUEST['COWL_was_requested_with']) ? 'json' : self::$default_type;
	}
	
	public function getPiecesFromPath()
	{
		return explode('/', trim($this->path, '/'));
	}
	
	public function isCommandInBaseDirectory()
	{
		return ! $this->directories || ! count($this->directories);
	}
	
	/*
		Method:
			<Controller::isPackage>
		
		Will check the specified directory for a "package", a directory, with the name of the command it contains, which also contains views, styles and scripts for this command.
		
		Parameters:
			$dir - The directory in which the package should lie in.
			$name - The name of the command/package
	*/
	
	public function isPackage($dir, $name)
	{
		return file_exists($this->makePath($dir . $name . DIRECTORY_SEPARATOR, $name));
	}
	
	/*
		Method:
			<Controller::setDir>
		
		Sets the <Controller::$dir>.
		
		Parameters:
			$dir - An existing directory of which the commands are contained.
	*/
	
	public static function setDir($dir)
	{
		self::$commands_dir = $dir;
	}
	
	/*
		Method:
			<Controller::useRequireOnce>
		
		Set wether to use require_once or require when including commands. See <Controller::$use_requice_once>.
		
		Parameters:
			(boolean) $use - True if should use require_once else false.
	*/
	
	public static function useRequireOnce($use)
	{
		self::$use_require_once = $use;
	}

	/*
		Method:
			<Controller::setCurrent>
		
		Sets the <Controller::$current_dir>, replaces the <Controller::$dir> with nothing.
		
		Parameters:
			$dir - The directory in which the currently included <Command> resided.
	*/
	
	private function setCurrent($dir)
	{
		$this->current_dir = str_replace(self::$dir, '', $dir);
	}
	
	/*
		Method:
			<Controller::getCurrent>
		
		Returns the <Controller::$current_dir>
		
		Returns:
			<Controller::$current_dir>
	*/
	
	public function getCurrent()
	{
		return $this->current_dir;
	}
	
	/*
		Method:
			<Controller::getPath>
		
		Returns the <Controller::$path> variable.
		
		Returns:
			<Controller::$path>
	*/
	
	public function getPath()
	{
		return $this->path;
	}
	
	/*
		Method:
			<Controller::setPath>
		
		Set <Controller::$path>. No validation of any sort is performed.
		
		Parameters:
			$path - The new path
	*/
	
	public function setPath($path)
	{
		$this->path = $path;
	}
	
	/*
		Method:
			<Controller::makePath>
		
		Makes a commandpath from a directory and command name.
		
		Parameters:
			$dir - The dir containing the command. _With_ a trailing DIRECTORY_SEPARATOR
			$command - The name of the command.
		
		Returns:
			The pathname.
	*/
	
	private function makePath($dir, $command)
	{
		return $dir . 'command.' . strtolower($command) . '.php';
	}
}

/*
	Class:
		RequestData
	
	Contains request data for a request. Like command, command path, arguments, etc
*/

class RequestData
{
	// Response type of the request, for example: html, json, xml, png, jpg
	public $response_type;
	
	// Arguments passed to the controller. First element is always the classname.
	public $argv = array();
	
	// The base directory for the commands in general
	public $base_directory;
	
	// The path to the current command. Excluding the base directory
	public $app_directory = '';
	
	// Filename of the command
	public $command;
	
	// Pieces based for the decision
	public $pieces;
	
	// Original request sent to the server, exploded by directory_separator
	public $original_request;
	
	// The method of the request. Not determined by <Controller>, but by <Command> (FIXME: should it be, though?)
	public $method;
}
