<?php

/*
	Class:
		<Controller>
	
	Core routing.
*/

class Controller
{
	private static $MIMES = array(
		'json' => 'text/json',
		'css' => 'text/css',
		'js' => 'text/x-javascript',
		'jpg' => 'image/jpeg',
		'gif' => 'image/gif',
		'png' => 'image/png',
		'bmp' => 'image/bmp',
		'html' => 'text/html'
	);
	
	// Property: <Controller::$path>
	// The path to be parsed
	private $path;
	
	// Property: <Controller::$commands_dir>
	// The directory in where the commands lie
	private static $commands_dir = 'commands/';
	
	// Property: <Controller::$default_type>
	// The default response type for the argv-array
	private static $default_type = 'html';
	
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
			Returns an array, much like the argv array in other programming languages. The command filepath is the first element, and arguments to the command are the rest.
	*/
	
	public function parse()
	{
		$return = array();
		$is_error = false;
		
		// If a file-ending is specified in the URL
		if ( preg_match('/\.[A-Za-z0-9]{2,4}$/', $this->path) )
		{
			$period = strrpos($this->path, '.');
			$path = substr($this->path, 0, $period);
			$response_type = substr($this->path, $period + 1);
			
			if ( ! isset(self::$MIMES[$response_type]) )
			{
				$is_error = true;
			}
			
			$pieces = explode('/', trim($path, '/'));
		}
		else
		{
			$response_type = self::$default_type;
			$pieces = explode('/', trim($this->path, '/'));
		}
		
		// No command? Go to main
		if ( ! count($pieces) || empty($pieces[0]) )
		{
			$directory = self::$commands_dir;
			$command_name = 'command.main.php';
			$dir_pieces = array('main');
			
			$return['argv'] = array($this->default_command);
		}
		// Start searching for commands
		else
		{
			$directories = $pieces;
			$glued = implode(DIRECTORY_SEPARATOR, $directories);
			
			// Traverse to the innermost directory, checking for packages
			while ( count($directories) && ! is_dir(self::$commands_dir . $glued) )
			{
				array_pop($directories);
				$glued = implode(DIRECTORY_SEPARATOR, $directories);
			}
			
			if ( is_dir(self::$commands_dir . $glued . DIRECTORY_SEPARATOR . 'main') )
			{
				$directories[] = 'main';
				// Add junk to beginning of pieces so the
				// "$args = array_slice($pieces, count($directories));"
				// Won't remove one to many
				array_unshift($pieces, 0);
			}
			
			// Command in base directory
			if ( ! $directories || ! count($directories) )
			{
				$directory = self::$commands_dir;
				$command_name = 'command.' . $pieces[0] . '.php';
				$dir_pieces = array();
				
				if ( file_exists($directory . $command_name) )
				{
					$command = array_shift($pieces) . 'Command';
					$return['argv'] = array_merge(array($command), $pieces);
				}
				else
				{
					$is_error = true;
				}
			}
			// Command in sub-directory
			else
			{
				$command = implode('', $directories) . 'Command';
				$args = array_slice($pieces, count($directories));
				$dir_pieces = $directories;
				
				$directory = self::$commands_dir . implode(DIRECTORY_SEPARATOR, $directories) . DIRECTORY_SEPARATOR;
				$command_name = 'command.' . end($directories) . '.php';
				
				$return['argv'] = array_merge(array($command), $args);
			}
		}
		
		if ( $is_error )
		{
			$directory = self::$commands_dir;
			$command_name = 'command.error.php';
			
			$return['argv'] = array_merge(array($this->error_command), $pieces);
		}
		
		require($directory . $command_name);
		$return['directory'] = $directory;
		$return['command'] = $command_name;
		$return['response_type'] = $response_type;
		$return['pieces'] = $dir_pieces;
		
		return $return;
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
