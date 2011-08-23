<?php

/*
	Abstract Class:
		<Command>
	
	Serves as a base class for commands.
*/

abstract class Command
{
	// Property: <Command::$aliases>
	// Contains aliases for actions.
	protected $aliases = array();
	
	// Property: <Command::$objects>
	// Object-dependencies used in the current commmand.
	protected $objects = array();
	
	// Property: <Command::$template>
	// Holds an instance to the Templater class, which takes care of all templateing needs.
	protected $template;
	
	// Property: <Command::$argv>
	// The argv passed to <Command::run>
	protected $argv;
	
	// Property: <Command::$js>
	// Holds an array of JS-files for the command.
	protected $js = array();
	
	// Property: <Command::$view>
	// The name of the view to include as template.
	private $view;
	
	/*
		Constructor:
			<Command::__construct>
		
		This method is declared final because it should not be overwritten. If you want to initialize objects or variables before an action is run, use the <Command::initialize> method instead.
	*/
	
	public final function __construct()
	{
		foreach ( $this->objects as $map )
		{
			$this->{strtolower($map . 'mapper')} = DataMapper::get($map);
		}
		
		$this->template = new Templater();
	}
	
	/*
		Method:
			<Command::run>
		
		Runs a method of the current class from the specified $args. The first element of the $args['argv'] should be the name of the class (just as in CLI).
		
		Examples:
			// TODO: This example is outdated.
			> // Argument array
			> $args[-3] - The path to the command
			> $args[-2] - The filename of the command
			> $args[-1] - The filetype requested
			> $args[0]  - CommandName
			> $args[1]  - (mixed) argument
			> $args[n]  - ...
			> $args[last] - (optional) method to fire
			
			> /forum/view/narwals-and-red-tits.json
			array(
				-3 => /path/to/cowl/app/front/forum/view/,
				-2 => command.view.php,
				-1 => json,
				 0 => forumviewCommand,
				 1 => "narwals-and-red-tits"
			)
		
		Parameters:
			$args - Contains information on which method to run. If none is provided index is called.
	*/
	
	public final function run($argv)
	{
		$this->argv = $argv;
		
		$view = explode('.', $argv['command']);
		$view = $view[1];
		
		$args = array_slice($argv['argv'], 1);
		$method = (count($args)) ? $args[count($args) - 1] : false;
		
		// Call initialize method, if one exists
		if ( method_exists($this, 'initialize') )
		{
			$redirect = call_user_func_array(array($this, 'initialize'), $args);
			if ( is_array($redirect) )
			{
				$url = Cowl::url($redirect);
				Cowl::redirect($url);
			}
		}
		
		// If aliases exists, "reroute" the method
		if ( isset($this->aliases[$method]) && method_exists($this, $this->aliases[$method]) )
		{
			$method = $this->aliases[$method];
		}
		elseif ( ! $method || $method == 'run' || ! method_exists($this, $method) )
		{
			$method = 'index';
		}
		$argv['method'] = $method;
		
		// Set view to either the base-name of the class, which is default or the name of the method
		if ( is_null($this->view) && $this->template->exists('view.' . $method . '.php') )
		{
			$this->setView($method);
		}
		else if ( is_null($this->view) )
		{
			$this->setView($view);
		}
		
		// Set the appropriate shell for the response type
		try {
			$this->template->setType($argv['response_type']);
		}
		catch ( TPLShellNotExistsException $e )
		{
			$this->template->setType('html');
			$argv['response_type'] = 'html';
		}
		
		// Set cache path, if the user wants to activate cacheing
		$this->template->setCachePath($this->getCachePath(), 600);
		
		$this->template->add('argv', $argv);
		Current::$request->setInfo('argv', $argv);
		
		Current::$plugins->hook('commandRun', $this, $method, $argv);
		
		// Prepare some stuff before firing the command
		$this->requestBegan();
		
		// _This_ is where all the magic happens
		$ret = call_user_func_array(array($this, $method), $args);
		
		// If an array is returned it is used as pieces for a <Cowl::url> redirect
		if ( is_array($ret) )
		{
			$this->requestEnded();
			
			$url = Cowl::url($ret);		
			Cowl::redirect($url);
		}
		
		// Render the template
		$this->template->render($this->view);
		
		$this->requestEnded();
	}
	
	/*
		Method:
			Command::requestBegan
		
		Prepare some stuff before a command is fired.
	*/
	
	private function requestBegan()
	{
		if ( isset($_SESSION['__cowl'], $_SESSION['__cowl']['flash']) )
		{
			$info = $_SESSION['__cowl']['flash'];
			
			Current::$request->setInfo('flash', $info['flash']);
			Current::$request->setInfo('flashError', $info['error']);
			Current::$request->setInfo('flashSuccess', $info['success']);
			Current::$request->setInfo('flashNotice', $info['notice']);
		}
	}
	
	/*
		Method:
			Command::requestEnded
		
		Clean-up and other actions for when the request is finished. Be it redirect or after a template render.
	*/
	
	private function requestEnded()
	{
		if ( ! isset($_SESSION['__cowl']) )
			$_SESSION['__cowl'] = array();
		
		$flash = Current::$request->getInfo('flash');
		$error = Current::$request->getInfo('flashError');
		$notice = Current::$request->getInfo('flashNotice');
		$success = Current::$request->getInfo('flashSuccess');
		
		$_SESSION['__cowl']['flash'] = array(
			'flash' => $flash,
			'error' => $error,
			'notice' => $notice,
			'success' => $success
		);
	}
	
	/*
		Method:
			<Command::setTemplateDir>
		
		Sets the <Templater::$template_dir> using <Templater::setDir>
		
		Parameters:
			$dir - The directory in which the template resides.
	*/
	
	public function setTemplateDir($dir)
	{
		$this->template->setDir($dir);
	}
	
	/*
		Method:
			<Command::setView>
		
		Sets the view, and path to view, for <Templater::render>.
		
		Parameters:
			$path - Path to view, directory etc.
			$to - ...
			$view - The last argument is the name of the view, without view....php
	*/
	
	public function setView()
	{
		$args = func_get_args();
		$path = implode('/', array_slice($args, 0, -1));
		$path .= 'view.' . end($args) . '.php';
		$this->view = $path;
	}
	
	/*
		Method:
			<Command::getCachePath>
		
		Returns:
			A path that can be used for any <Cache> that is specific to the method run and arguments passed.
	*/
	
	public function getCachePath()
	{
		$argv = $this->argv['argv'];
		$pieces = $this->argv['pieces'];
		array_shift($argv);
		$pieces = array_merge($pieces, $argv);

		return 'command.' . implode('.', $pieces);
	}
	
	// Method: <Command::getJS>
	// Returns <Command::$js>
	public function getJS() { return $this->js; }
	
	/*
		Method:
			<Command::assign>
		
		Alias for <Command::$template::add>. See <Templater::add> for more details.
	*/
	
	public function assign()
	{
		$args = func_get_args();
		return call_user_func_array(array($this->template, 'add'), $args);
	}
	
	/*
		Method:
			Command::flash
		
		Flash a message on the next renderinf of a template. Used for messages on a successful POST request.
		Use the standard helper method <flash> to output these flashes. There are several types of flashes:
		
			- flash 
			- flashError
			- flashNotice
			- flashSuccess
		
		For information about them see each method in the <Command> class.
		
		Parameters:
			(string) $message - The message to flash
	*/
	
	public function flash($message)
	{
		Current::$request->setInfo('flash[]', $message);
	}
	
	/*
		Method:
			Command::flashError
		
		Flash a message with an error class. For more information see: <Command::flash>
	*/
	
	public function flashError($message)
	{
		Current::$request->setInfo('flashError[]', $message);
	}
	
	
	/*
		Method:
			Command::flashNotice
		
		Flash a message with a notice class. For more information see: <Command::flash>
	*/
	
	public function flashNotice($message)
	{
		Current::$request->setInfo('flashNotice[]', $message);
	}
	
	
	/*
		Method:
			Command::flashSuccess
		
		Flash a message with an success class. For more information see: <Command::flash>
	*/
	
	public function flashSuccess($message)
	{
		Current::$request->setInfo('flashSuccess[]', $message);
	}
	
	public abstract function index();
}

