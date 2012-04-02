<?php

class TPLNotAllowedHereException extends Exception {}

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
	
	// Property: <Command::$request>
	// The request object passed to <Command::run>
	protected $request;
	
	// Property: <Command::$js>
	// Holds an array of JS-packages for the command.
	protected $js = array('core');
	
	// Property: <Command::$css>
	// Holds an array of CSS-packages for the command
	protected $css = array('core');
	
	// Property: <Command::$view>
	// The name of the view to include as template.
	private $view;
	
	// Property: <Command::$layouts>
	// Translation table for layout types. Add a * (start) key for it to be used for every request type
	protected $layouts = array();
	
	/*
		Constructor:
			<Command::__construct>
		
		This method should ONLY be overwritten by abstract base classes.
		
		If you want to initialize objects or variables before an action is run, use the <Command::initialize> method instead.
	*/
	
	public function __construct()
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
		
		Runs a method of the current class from the <RequestData>-object passed. To see how the data is formatted
		please have a look at <Controller> and <RequestData>.
		
		Parameters:
			(RequestData) $request - Information about the request
	*/
	
	public final function run(RequestData $request)
	{
		$this->request = $request;
		
		$view = explode('.', $request->command);
		$view = $view[1];
		
		$args = array_slice($request->argv, 1);
		$method = (count($args)) ? $args[count($args) - 1] : false;
		
		// Call initialize method, if one exists
		if ( method_exists($this, 'initialize') )
		{
			$redirect = call_user_func_array(array($this, 'initialize'), $args);
			if ( is_array($redirect) || is_string($redirect) )
			{
				$url = is_array($redirect) ? Cowl::url($redirect) : $redirect;
				return $url;
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
		
		// Ensure that method is public
		$reflection = new ReflectionMethod(get_class($this), $method);
		if ( ! $reflection->isPublic() )
		{
			$method = 'index';
		}
		
		$request->method = $method;
		
		// Set view to either the base-name of the class, which is default or the name of the method
		if ( is_null($this->view) && $this->template->exists('view.' . $method . '.php') )
		{
			$this->setView($method);
		}
		else if ( is_null($this->view) )
		{
			$this->setView($view);
		}
		
		// If a global layout type has defined use that
		
		// Set the appropriate layout for the response type
		try {
			$type = isset($this->layouts['*']) ? $this->layouts['*'] : $request->response_type;
			$type = isset($this->layouts[$type]) ? $this->layouts[$type] : $type;
			
			// Check if the user has restricted which layouts can automatically be set
			$allowed_types = Current::$config->getOr('allowed_layouts', true);
			
			if ( is_array($allowed_types) && ! in_array($type, $allowed_types) )
			{
				throw new TPLNotAllowedHereException($type);
			}
			$this->template->setType($type);
		}
		catch ( Exception $e )
		{
			$html_type = isset($this->layouts['html']) ? $this->layouts['html'] : 'html';
			$this->template->setType($html_type);
			
			$request->response_type = 'html';
		}
		
		// Set cache path, if the user wants to activate cacheing
		$this->template->setCachePath($this->getCachePath(), 600);
		
		$this->template->add('request', $request);
		Current::$request->setInfo('request', $request);
		
		Current::$plugins->hook('commandRun', $this, $method, $request);
		
		// Prepare some stuff before firing the command
		$this->requestBegan();
		
		// _This_ is where all the magic happens
		$ret = call_user_func_array(array($this, $method), $args);
		
		// If an array is returned it is used as pieces for a <Cowl::url> redirect
		if ( is_array($ret) || (is_string($ret) && strlen($ret)) )
		{
			$this->requestEnded();
			return is_array($ret) ? Cowl::url($ret) : $ret;
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
		try {
			$info = Current::$store->get('cowl.flash');
		
			Current::$request->setInfo('flash', $info['flash']);
			Current::$request->setInfo('flashError', $info['error']);
			Current::$request->setInfo('flashSuccess', $info['success']);
			Current::$request->setInfo('flashNotice', $info['notice']);
		}
		catch ( RegistryMemberNotFoundException $e ) {}
	}
	
	/*
		Method:
			Command::requestEnded
		
		Clean-up and other actions for when the request is finished. Be it redirect or after a template render.
	*/
	
	private function requestEnded()
	{
		$flash = Current::$request->getInfo('flash');
		$error = Current::$request->getInfo('flashError');
		$notice = Current::$request->getInfo('flashNotice');
		$success = Current::$request->getInfo('flashSuccess');
		
		Current::$store->set('cowl.flash', array(
			'flash' => $flash,
			'error' => $error,
			'notice' => $notice,
			'success' => $success
		));
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
		$argv = $this->request->argv;
		$pieces = $this->request->pieces;
		array_shift($argv);
		$pieces = array_merge($pieces, $argv);

		return 'command.' . implode('.', $pieces);
	}
	
	// Method: <Command::getJS>
	// Returns <Command::$js>
	public function getJS() { return $this->js; }
	
	// Method: <Command::getCSS>
	// Returns <Command::$css>
	public function getCSS() { return $this->css; }
	
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
			(string or array) $message - The message to flash
	*/
	
	public function flash($message)
	{
		$this->setFlashMessage('flash', $message);
	}
	
	/*
		Method:
			Command::flashError
		
		Flash a message with an error class. For more information see: <Command::flash>
	*/
	
	public function flashError($message)
	{
		$this->setFlashMessage('flashError', $message);
	}
	
	
	/*
		Method:
			Command::flashNotice
		
		Flash a message with a notice class. For more information see: <Command::flash>
	*/
	
	public function flashNotice($message)
	{
		$this->setFlashMessage('flashNotice', $message);
	}
	
	
	/*
		Method:
			Command::flashSuccess
		
		Flash a message with an success class. For more information see: <Command::flash>
	*/
	
	public function flashSuccess($message)
	{
		$this->setFlashMessage('flashSuccess', $message);
	}
	
	/*
		Method:
			Command::setFlashMessage
		
		Set a flash message using specified type.
		
		Parameters:
			$type - The type of flash. It's basically just a key that is stored in the current request
			$message - Either array or string, if array it is recursively added
	*/
	
	private function setFlashMessage($type, $message)
	{
		if ( is_array($message) )
		{
			foreach ( $message as $mess )
			{
				$this->setFlashMessage($type, $mess);
			}
		}
		else
		{
			Current::$request->setInfo($type . '[]', $message);
		}
	}
	
	/*
		Method:
			debug
		
		Set a debug environment
	*/
	
	protected function debug()
	{
		header('Content-type: text/plain');
		
		// This is hard-coded as mysql, because the only supported DB is mysql.
		// Change this if that ever changes.
		Current::db('mysql')->output_query = true;
	}
	
	public abstract function index();
}

