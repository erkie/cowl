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
		
		Runs a method of the current class from the specified $args. The first element of the $args should be the name of the class (just as in CLI).
		
		Examples:
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
		$view = explode('.', $argv['command']);
		$view = $view[1];
		
		$args = array_slice($argv['argv'], 1);
		$method = (count($args)) ? $args[count($args) - 1] : false;
		
		
		// Call initialize method, if one exists
		if ( method_exists($this, 'initialize') )
		{
			call_user_func_array(array($this, 'initialize'), $args);
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
		$this->template->setType($argv['response_type']);
		
		Current::$plugins->hook('commandRun', $method, $argv);
		
		// _This_ is where all the magic happens
		$ret = call_user_func_array(array($this, $method), $args);
		
		if ( is_array($ret) )
		{
			$url = call_user_func_array('Cowl::url', $ret);
			
			header('Location: ' . $url);
			exit($url);
		}
		
		// Render the template
		$this->template->render($this->view);
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
	
	public abstract function index();
}

/*
	To-be removed
*/

class ThreadCommand extends Command
{
	protected $objects = array('Thread');
	
	private $thread;
	
	public function initialize($slug)
	{
		$this->thread = new Thread();
		$thread->set('slug', $slug);
		$thread->fetch();
	}
	
	# www.example.com/forum/thread/my-name-is-earl
	public function index()
	{	
		$this->fetch();
	}
	
	# www.example.com/forum/thread/my-name-is-earl/fetch
	public function fetch()
	{
		$this->template->add($thread);
	}
	
	# www.example.com/forum/thread/my-name-is-earl/delete
	public function delete()
	{
		$key = Current::$user->getAuthKey();
		$this->thread->delete($key);
	}
	
	# www.example.com/forum/thread/my-name-is-earl/reply
	public function post()
	{
		$post = new Post();
		$post->set('thread_id', $this->thread->getID());
		$post->set('message', Request::get('message'));
		$post->update();
	}
}
