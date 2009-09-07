<?php
define('COWL_CACHED', true);


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
	private $view = 'view.main.php';
	
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
			> $args[0] - CommandName
			> $args[1] - (mixed) argument
			> $args[n] - ...
			> $args[last] - (optional) method to fire
		
		Parameters:
			$args - Contains information on which method to run. If none is provided index is called.
	*/
	
	public final function run($args)
	{
		$view = explode('.', $args[-1]);
		$view = $view[1];
		
		$args = array_slice($args, 3);
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
		if ( $this->template->exists('view.' . $method . '.php') )
		{
			$this->setView($method);
		}
		else
		{
			$this->setView($view);
		}
		
		// THIS is where all the magic happens
		call_user_func_array(array($this, $method), $args);
		
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


if ( ! defined('COWL_CACHED') )
{
	require('library/registries/registry.php');
	require('library/registries/request.php');
	require('library/registries/config.php');
}

/*
	Class:
		<Current>
	
	A container class for registries and other site-wide classes. It is not meant to be instantiated, and therefor has no non-static methods.
*/

class Current
{
	/*
		Method:
			<Current::initialize>
		
		Add all registries to self, _except_ those that are extra special. Can be called as many times as seen fit, but seeing as every call is to a registry, nothing new will happen the second time around.
	*/
	
	public static function initialize($path)
	{
		Config::setPath($path);
		
		self::$request = Request::instance();
		self::$config = Config::instance();
	}
	
	public static function db()
	{
		if ( is_null(self::$db) )
		{
			list($server, $user, $pass, $database) = self::$config->gets('db.server', 'db.user', 'db.password', 'db.database');
			self::$db = new DB($server, $user, $pass, $database);
		}
		return self::$db;
	}
	
	// Property: <Current::$user>
	// User object, should really be removed.
	public static $user;
	
	// Property: <Current::$request>
	// $_POST, $_GET data
	public static $request;
	
	// Property: <Current::$db>
	// Database object
	public static $db;
	
	// Property: <Current::$store>
	// User session registry
	public static $store;
	
	// Property: <Current::$registry>
	// Site-wide registry
	public static $registry;
	
	// Property: <Current::$config>
	// Global config object
	public static $config;
}


abstract class Registry
{
	/*
		Property:
			<Registry::$data>
		
		Contains the data for the store.
	*/
	
	protected $data = array();
	
	/*
		Property:
			<Registry::$is_dirty>
		
		Contains a flag to indicate whether a change has been made to the data. This flag can be used by sub-classes to store new data when the registry is __destructed.
	*/
	
	protected $is_dirty = false;
	
	/*
		Property:
			<Registry::$instance>
		
		Singletone instance.
	*/
	
	protected static $instance;
	
	/*
		Method:
			<Registry::__construct>
		
		Calls the user-defined $this->initialize. Cannot, and should not, be overwritten.
	*/
	
	final protected function __construct()
	{
		$this->initialize();
	}
	
	/*
		Method:
			<Registry::initialize>
		
		Because of all the problems caused by PHP's lack of features a custom initialize method is used to initialize a registry.
	*/
	
	protected function initialize() {}
	
	/*
		Method:
			<Registry::instance>
		
		This method has to be redefined by all subclasses. Check the code below for suggestions.
		
		(begin code)

		public static function instance()
		{
			return parent::getInstance(__CLASS__, self::$instance);
		}
		
		(end code)
	*/
	
	abstract public static function instance();
	
	/*
		Method:
			<Registry::getInstance>
		
		getInstance creates a new instance and returns it, in a singletonish way. This method should _only_ be called from instance();
	
		Parameters:
			$name - The name of the class to initialized.
			&$instance - A reference to the instance variable. This will be changed or left unmodified and returned.
		
		Returns:
			The newly created instance or $instance.
	*/
	
	protected static function getInstance($name, &$instance)
	{
		if ( ! $instance )
		{
			$instance = new $name();
		}
		return $instance;
	}
	
	/*
		Method:
			<Registry::get>
		
		Fetches the $value from the registry's store. Namespacing can be applied.
		
		(start code)
		
		> $inst->get('version')
		'1.2.3'
		> $inst->get('paths.cache')
		'cowl/cache/'
		> $inst->get('plugins.app.version');
		'0.9.3'
		
		(end)
		
		Parameters:
			$value - The key to be fetched
		
		Returns:
			The value, or false if none was found.
	*/
	
	public function get($value)
	{
		$namespaces = explode('.', $value);
		
		$current = &$this->data;
		foreach ( $namespaces as $space )
		{
			if ( ! isset($current[$space]) )
			{
				return false;
			}
			
			$current = &$current[$space];
		}
		
		return $current;
	}
	
	/*
		Method:
			<Registry::gets>
		
		Works like <Registry::get> accept it can take several values to be found, returning them in the order of appearence in the argument list.
		
		Examples:
			> list($path, $cache) = $inst->gets('plugins.versions.path', 'plugins.versions.cache');
		
		Parameters:
			$value1 - The key to fetch.
			$valuen - ...
		
		Returns:
			An array of the values fetched.
	*/
	
	public function gets()
	{
		$args = func_get_args();
		return array_map(array($this, 'get'), $args);
	}
	
	/*
		Method:
			<Registry::fetch>
		
		Fetches a piece of the data store.
		
		Parameters:
			$value1 - The key to fetch.
			$valuen - ...
		
		Returns:
			An associative array of the values fetched.
	*/
	
	public function fetch()
	{
		$args = func_get_args();
		$values = array_map(array($this, 'get'), $args);
		return array_combine($args, $values);
	}
	
	/*
		Method:
			<Registry::set>
		
		Sets the corresponding $key, $value in the data store, overwriting existing values. This method will also set the is_dirty flag to true. Namespacing is allowed, just as in <Registry::get>.
		
		Parameters:
			$name - The key of the...
			$value - Value to be entered into the store.
		
		Returns:
			Null, always, because it should _always_ succeed.
	*/
	
	public function set($name, $value)
	{
		$this->is_dirty = true;
		
		$namespaces = explode('.', $name);
		
		$current = &$this->data;
		foreach ( $namespaces as $space )
		{
			$current = &$current[$space];
		}
		$current = $value;
	}
}


/*
	Class:
		<Request>
	
	A registry containing the $_REQUEST-data from the current request.
*/

class Request extends Registry
{
	// Property: <Request::$instance>
	// See <Registry::$instance>
	protected static $instance;
	
	// Method: <Request::instance>
	// See <Registry::instance>
	public static function instance()
	{
		return parent::getInstance(__CLASS__, self::$instance);
	}
	
	/*
		Method:
			<Request::initialize>
		
		Adds $_REQUEST-data to store.
	*/
	
	protected function initialize()
	{
		$this->data = $_REQUEST;
	}
}


class ConfigKeyNotFoundException extends Exception {}

/*
	Class:
		<Config>
	
	Global config registry. Parses config.ini according to parse_ini_file with a few exceptions:
	
		- A tilde (~) in strings is replaced with the value of paths.base
		- Periods (.) in names is used to namespace values.
	
	The <Config::$path> must be set if the config.ini-file lies in another directory than this class.
*/

class Config extends Registry
{
	// Property: <Config::$instance>
	// See <Registry::$instance>
	protected static $instance;
	
	// Property: <Config::$path>
	// Points to the directory in which the config.ini file lies.
	private static $path = '';
	
	// Property: <Config::$base>
	// The directory for which the tilde in names is replaced with.
	private $base = '';
	
	// Property: <Config::instance>
	// See <Registry::instance>
	public static function instance()
	{
		return parent::getInstance(__CLASS__, self::$instance);	
	}
	
	/*
		Method:
			<Config::initialize>
		
		Parse ini-file and add variables to store.
	*/
	
	protected function initialize()
	{
		$this->parseIniFile(self::$path . 'config.ini');
	}
	
	/*
		Method:
			<Config::parseIniFile>
		
		<Config>'s version of parse_ini_file. Uses the <Config::set>-method to add values to store.
	*/
	
	private function parseIniFile($filename)
	{
		$arr = parse_ini_file($filename);
		$this->base = $arr['paths.base'];
		
		foreach ( $arr as $key => $value )
		{
			$arr[$key] = str_replace('~', $this->base, $value);
		}
		$this->data = $arr;
	}
	
	/*
		Method:
			<Config::get>
		
		Works almost the same as <Registry::get>, but with a much faster and simpler model for fetching values.
		
		Parameters:
			$key - The key to find.
	*/
	
	public function get($key)
	{
		if ( ! isset($this->data[$key]) )
		{
			throw new ConfigKeyNotFoundException($key);
		}
		
		return $this->data[$key];
	}
	
	/*
		Method:
			<Config::setPath>
		
		Sets the path variable.
		
		Parameters:
			$path - The directory in which the config.ini-file lies.
	*/
	
	public static function setPath($path)
	{
		self::$path = $path;
	}
}


/*
	Class:
		Plugins

	Sucks up all available classes in the <Plugins::$plugins_dir> and calls them when the appropriate hooks are called in client code.
*/

class Plugins
{
	// Property: <Plugins::$plugins_dir>
	// The directory to be searched for plugins.
	private static $plugins_dir = 'plugins/';
	
	// Property: <Plugins::$plugins>
	// The plugin instances.
	private $plugins = array();
	
	/*
		Constructor:
			<Plugins::__construct>
		
		Calling the constructor loads and instansiates every plugin.
	*/
	
	public function __construct()
	{
		$this->loadPlugins(self::$plugins_dir);
	}
	
	/*
		Method:
			<Plugins::setDir>
		
		Set the <Plugins::$plugins_dir>
		
		Parameters:
			$dir - An existing directory containing the plugins.
	*/
	
	public static function setDir($dir)
	{
		self::$plugins_dir = $dir;
	}
	
	/*
		Method:
			<Plugns::loadPlugins>
		
		Load plugins from a $dir, will instansiate them too.
		
		Parameters:
			$dir - Directory to scan
	*/
	
	private function loadPlugins($dir)
	{
		$plugins = Current::$config->get('plugins.load');
		
		foreach ( $plugins as $plugin )
		{
			$path = Current::$config->get('plugins.' . $plugin . '.path');
			require($path);
			
			$name = Plugins::makeName($path);
			$this->plugins[] = new $name();
		}
	}
	
	/*
		Method:
			<Request::hook>
		
		Call the plugins' hooks.
		
		Parameters:
			$method - The "name" of the hook.
			$arg1 - Argument to be passed to the hook-method.
			$argN - ...
	*/
	
	public function hook($method)
	{
		$args = func_get_args();
		$args = array_slice($args, 1);
		
		foreach ( $this->plugins as $plugin )
		{
			if ( method_exists($plugin, $method) )
			{
				call_user_func_array(array($plugin, $method), $args);
			}
		}
	}
	
	/*
		Method:
			<Plugins::makeName>
		
		Makes a plugin name from a corresponding filename. Following these conventions:
		
			1. Replace _ with a space ( )
			2. Uppercase the first letter in every word
			3. Remove spaces
		
		Parameters:
			$filename - The filename of the plugin
		
		Returns:
			The Pluginname
	*/
	
	public static function makeName($filename)
	{
		$name = preg_replace('/plugin\.(.*?)\.php/', '$1', end(explode('/', $filename)));
		return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
	}
}

/*
	Class:
		<Plugin>
	
	Abstract base class for all plugins.
*/

abstract class Plugin
{
	
}


class ValidatorNotFoundException extends Exception {}
class ValidatorFailException extends Exception {}

/*
	Class:
		<Validator>
	
	A validator class capable of including validators, validating input.
*/

class Validator
{
	// Property: <Validator::$path>
	// Contains the pathname to where the validators are contained.
	private static $path = 'validators/';
	
	public function __construct() {}
	
	/*
		Method:
			<Validator::setPath>
		
		Set the <Validator::$path>. $path must point to an existing directory.
		
		Parameters:
			$path - The path to the directory.
	*/
	
	public static function setPath($path)
	{
		self::$path = $path;
	}
	
	/*
		Method:
			<Validator::validate>
		
		Validate the input, throwing a <ValidatorFailException> on failure.
		
		Parameters:
			$input - The input to validate.
			$func - The validator function to call. If the function does not exist, it will be loaded by <Validator::loadValidator>. $func will be translated to <Validator::$path>/validator.$func.php.
			$arg - An optional argument to pass to the validator.
		
		Returns:
			Returns true on success, throws a ValidatorException on failure.
	*/
	
	public function validate($input, $func, $arg = null)
	{
		$funcname = self::makeName($func);
		
		if ( ! $this->hasValidator($funcname) )
		{
			$this->loadValidator($func);
		}
		
		if ( ! call_user_func($funcname, $input, $arg) )
		{
			throw new ValidatorFailException($input);
		}
		
		return true;
	}
	
	/*
		Method:
			<Validator::hasValidator>
		
		Checks whether the passed validator exists, by checking if the function $name exists.
		
		Parameters:
			$name - The name, prefixed with validator_. E.g. validate_do_something.
		
		Returns:
			True if it exists, false if it does not exist.
	*/
	
	private function hasValidator($name)
	{
		return function_exists($name);
	}
	
	/*
		Method:
			<Validator::loadValidator>
		
		Loads the passed validator. The $name should be the name of the validator itself, not the filename nor the function name. Throws a ValidatorNotFoundException on failure.
		
		Parameters:
			$name - The name of the validator.
	*/
	
	private function loadValidator($name)
	{
		$filename = self::$path . 'validator.' . $name . '.php';
		
		if ( file_exists($filename) )
		{
			require($filename);
		}
		else
		{
			throw new ValidatorNotFoundException($name);
		}
	}
	
	/*
		Method:
			<Validator::makeName>
		
		Makes a function name out of the passed $func. It does this by prefixing validate_ to $func.
		
		Parameters:
			$func - The name to transform.
		
		Returns:
			The transformed name.
	*/
	
	private static function makeName($func)
	{
		return 'validate_' . $func;
	}
}


class TPLTemplateNotExistsException extends Exception {}

class Templater
{
	protected $vars = array();
	protected $dir = 'templates/';
	protected $template;
	protected static $shell;
	protected static $cache_dir = 'tplcache/';
	
	public function __construct()
	{
		
	}
	
	public function add($key, $value = null)
	{
		if ( is_array($key) )
		{
			$this->vars = array_merge($this->vars, $key);
		}
		else
		{
			$this->vars[$key] = $value;
		}
	}
	
	public function render($filename)
	{
		if ( ! $this->exists($filename) )
		{
			throw new TPLTemplateNotExistsException($this->dir . $filename);
		}
		
		$this->template = $this->dir . $filename;
		if ( ! $this->reloadCache($this->template) )
		{
			extract($this->vars);
			include(self::$shell);
		}
		else
		{
			die('reloading cache');
		}
	}
	
	private function reloadCache($filename)
	{
		return false;
	}
	
	public function exists($filename)
	{
		return file_exists($this->dir . $filename);
	}
	
	public static function setShell($name)
	{
		self::$shell = $name;
	}
	
	public function setDir($dir)
	{
		$this->dir = $dir;
	}
	
	public static function setCacheDir($dir)
	{
		self::$cache_dir = $dir;
	}
}


class VH
{
	public static function url()
	{
		$args = func_get_args();
		echo implode('/', $args);
	}
}


class Cache
{
	private $file;
	private $cache_time;
	
	public function __construct($filename, $time = 600)
	{
		$this->file = $filename;
		$this->cache_time = $time;
		
		$this->exists = file_exists($this->file);
	}
	
	public function getContents()
	{
		return file_get_contents($this->file);
	}
	
	public function update($contents, $flags = 0)
	{
		if ( ! $this->exists )
		{
			fclose(fopen($this->file, 'w'));
			$this->exists = true;
		}
		
		file_put_contents($this->file, $contents, $flags);
	}
	
	public function isOutDated()
	{
		return !($this->exists && filemtime($this->file) > ($_SERVER['REQUEST_TIME'] - $this->cache_time));
	}
}


if ( ! defined('COWL_CACHED') )
{
	require('dbresult.php');
	require('datamapper.php');
	require('domainobject.php');
	require('domaincollection.php');
	require('querybuilder.php');
}

class DBConnectionException extends Exception {}
class DBDatabaseSelectException extends Exception {}
class DBQueryException extends Exception {}

class DB
{
	private $conn;

	public function __construct($server, $user, $password, $database)
	{
		$this->connect($server, $user, $password, $database);
	}
	
	public function connect($server, $user, $password, $database)
	{
		if ( ! $this->conn )
		{
			$this->conn = @mysql_connect($server, $user, $password);
			
			if ( ! $this->conn )
			{
				throw new DBConnectionException(mysql_error());
			}
			
			if ( ! mysql_select_db($database) )
			{
				throw new DBDatabaseSelectException(mysql_error());
			}
		}
	}
	
	public function execute($query)
	{
		$args = func_get_args();
		$args = array_slice($args, 1);
		
		$res = $this->query($query, $args);
		
		$result = new DBResult($res);
		$result->setID(mysql_insert_id());
		$result->setAffected(mysql_affected_rows());
		
		return $result;
	}
	
	private function query($query, $args)
	{
		$ret = mysql_query(vsprintf($query, self::sanitize($args)));
		if ( ! $ret )
		{
			throw new DBQueryException(mysql_error());
		}
		return $ret;
	}
	
	private static function sanitize($data)
	{
		if ( is_array($data) )
		{
			foreach ( $data as $key => $value )
			{
				$data[$key] = self::sanitize($value);
			}
			return $data;
		}
		
		if ( get_magic_quotes_gpc() )
		{
			$data = stripslashes($data);
		}
		return mysql_real_escape_string($data);
	}
}


class DBResult implements Iterator
{
	private $result;
	private $id;
	private $num_rows;
	private $affected;
	private $rows;
	private $position = 0;
	
	public function __construct($result)
	{
		$this->result = $result;
	}
	
	public function fetch()
	{
		if ( ! $this->rows )
		{
			$rows = array();
			while ( $row = mysql_fetch_assoc($this->result) )
			{
				$rows[] = $row;
			}
			$this->rows = $rows;
		}
		return $this->rows;
	}
	
	public function get($index)
	{
		$this->fetch();
		return isset($this->rows[$index]) ? $this->rows[$index] : false;
	}
	
	public function fetchRow()
	{
		$this->fetch();
		return $this->rows[$this->position++];
	}
	
	public function row()
	{
		return mysql_fetch_assoc($this->result);
	}
	
	public function rewind()
	{
		$this->position = 0;
	}
	
	public function current()
	{
		return $this->get($this->position);
	}
	
	public function key()
	{
		return $this->position;
	}
 	
 	public function next()
 	{
 		return $this->fetchRow();
 	}
 	
 	public function valid()
 	{
 		return (bool)$this->current();
 	}
 	
 	public function setPosition($position)
 	{
 		$this->position = $position;
 		return $this;
 	}
 	
	public function setID($id)
	{
		$this->id = $id;
	}
	
	public function setAffected($rows)
	{
		$this->affected = $rows;
	}
	
	public function getID()
	{
		return $this->id;
	}
	
	public function getNumRows()
	{
		if ( is_null($this->num_rows) )
		{
			$this->num_rows = mysql_num_rows($this->result);
		}
		
		return $this->num_rows;
	}
	
	public function getAffected()
	{
		return $this->affected;
	}
}


class MapperNotFoundException extends Exception {}
class MapperObjectNotFoundException extends Exception {}
class MapperNoTableException extends Exception {}

/*
	Abstract Class:
		<DataMapper>
	
	A base-class for mappers. Responsible for abstracting database logic from <DomainObjects>. 
*/

abstract class DataMapper
{
	/*
		Property: <DataMapper::$table>
		
		This property _must be_ overwritten in base classes. It should contain the name of the table for which the mapper maps.
	*/
	
	protected $table;
	
	/*
		Property: <DataMapper::$primary_key>
		
		Contains the name of the primary key for the table. The default value is "id". The <DataMapper::$table> and <DataMapper::$primary_key> are simply passed on to the <QueryBuilder>.
	*/
	
	protected $primary_key = 'id';
	
	/*
		Property:
			<DataMapper::$state>
		
		Stores states for a query that is built in <DataMapper::find>.
	*/
	
	protected $state = array('order' => null, 'offset' => null, 'amount' => null);
	
	// Property: <DataMapper::$state_dirty>
	// A bool value set to true if the <DataMapper::$state>-array has been changed.
	
	protected $state_dirty = false;
	
	// Property: <DataMapper::$builder>
	// An instance to a <QueryBuilder> pointed to this mapper.
	protected $builder;
	
	// Property: <DataMapper::$instances>
	// A simple registry that contains intances of mappers.
	private static $instances = array();
	
	// Property: <DataMapper::$mappers_dir>
	// The directory in which mappers are contained. Note that the filename of a mapper must follow the pattern "mapper.NAME.php"
	private static $mappers_dir = 'mappers/';
	
	// Property: <DataMapper::$objects_dir>
	// The directory in which objects are contained. The filename must follow the pattern "object.NAME.php". An object MUST have a corresponding mapper. The directory of the mappers and objects can be the same.
	private static $objects_dir = 'objects/';
	
	// Property: <DataMapper::$object_name>
	// Holds the the name of the DomainObject for which the current mapper maps.
	protected $object_name;
	
	/*
		Constructor:
			<DataMapper::__construct>
		
		Enforces the rule that a mapper must define a <DataMapper::$table>.
	*/
	
	public function __construct()
	{
		if ( is_null($this->table) || empty($this->table) )
		{
			throw new MapperNoTableException(get_class($this));
		}
		
		$this->object_name = substr(get_class($this), 0, -6);
		
		$this->builder = new QueryBuilder($this->table, $this->primary_key);
	}
	
	/*
		Method:
			<DataMapper::setMappersDir>
		
		Sets the <DataMapper::$mappers_dir>.
		
		Parameters:
			$dir - A directory in which the mappers lay.
	*/
	
	public static function setMappersDir($dir)
	{
		self::$mappers_dir = $dir;
	}
	
	/*
		Method:
			<DataMapper::setObjectsDir>
		
		Sets the <DataMapper::$objects_dir>.
		
		Parameters:
			$dir - A directory in which the objects lay.
	*/
	
	public static function setObjectsDir($dir)
	{
		self::$objects_dir = $dir;
	}
	
	/*
		Method:
			<DataMapper::populate>
		
		Populates a <DomainObject> based upon defined fields in the $object.
		
		(begin code)
		
		// Using a field for population
		$post = new Post();
		$post->slug = $_GET['slug'];
		$mapper->populate($post);
		
		// $post corresponds to the database entry with the slug of $_GET['slug']
		
		// Short hand form if the $id is known
		$post = $mapper->populate(new Post($id));
		
		// $post now contains the fields of a database entry with id $id
		
		(end code)
		
		Parameters:
			DomainObject $object - The <DomainObject> to populate.
		
		Returns:
			The <DomainObject> $object passed originally.
	*/
	
	public function populate(DomainObject $object)
	{
		$query = QueryBuilder::buildSelect($object);
		
		$result = Current::db()->execute($query);
		$this->populateFromDBResult($object, $result);

		echo '<pre>', $query, '</pre>';

		return $object;
	}
	
	/*
		Method:
			<DataMapper::find>
		
		Executes a SELECT-statement after building the query with <QueryBuilder>. Will flush the <DataMapper::$state>.
		
		(begin code)
		
		// Fetch all posts with thread_id 10, order by id and limit 0, 10
		$posts = $postmapper->find(array('thread_id' => 10), 'id', 0, 10);
		
		// Same query as about, more intuitive code
		$posts = $postmapper->by('id')->limit(0, 10)->find(array('thread_id' => 10));
		
		// Fetch all posts
		$posts = $postmapper->find('all');
		
		// Fetch all posts limited to 0, 10
		$posts = $postmapper->limit(0, 10)->find('*');
		
		// Fetch all posts sorted by id
		$posts = $postmapper->by('time_created')->find('all');
		
		(end code)
		
		Parameters:
			$args - An array of args to filter the statement with. To find all records without filtering, the string 'all', or '*' _must_ be passed.
			$order - A string, or an array, of fields to sort the query by. Can also be set by <DataMapper::by>.
			$offset - The offset of the LIMIT. Can also be set by <DataMapper::limit>
			$amount - The amount of the LIMIT. Can also be set by <DataMapper::limit>
		
		Returns:
			A <DomainCollection> instance.
	*/
	
	public function find($args, $order = '', $offset = null, $amount = null)
	{
		if ( $this->state_dirty )
		{
			$order = $this->state['order'];
			$offset = $this->state['offset'];
			$amount = $this->state['amount'];
			
			$this->state_dirty = false;
			$this->state = array('order' => null, 'offset' => null, 'amount' => null);
		}
		
		$query = $this->builder->buildFind($args, $order, $offset, $amount);
		$result = Current::db()->execute($query);
		
		echo '<pre>', $query, '</pre>';
		
		return new DomainCollection($result, $this);
	}
	
	/*
		Method:
			<DataMapper::by>
		
		Set the current state of the querybuilder to ORDER BY $by. Used to chain mapper-calls for a more intuituve API. This method has to be called before <DataMapper::find>
		
		(begin code)
		
		$mapper = DataMapper::get('post');
		$posts = $mapper->by('id')->find('all');
		
		foreach ( $posts as $post )
		{
			echo $post->header . PHP_EOL;
			echo $post->message . PHP_EOL;
		}
		
		(end code)
		
		Parameters:
			$by - The string (or array) to build the values from.
		
		Returns:
			$this for chainability.
	*/
	
	public function by($by)
	{
		$this->state_dirty = true;
				
		$this->state['by'] = $by;
		return $this;
	}
	
	/*
		Method:
			<DataMapper::limit>
		
		Sets the limit state to the passed $limit. Used to chain mapper calls for a more intuitive API. This method has to be called before <DataMapper::find>.
		
		(begin code)
		
		// SELECT  * FROM table ORDER BY id LIMIT 10, 10
		$objects = $mapper->by('id')->limit(10, 10)->find('all');
		
		// SELECT * FROM table LIMIT 10
		$objects = $mapper->limit(10, 10)->find('all');
		
		(end code)
		
		Parameters:
			$offset - The offset, or if $amount is left null, how many to limit.
			$amount - How many to limit.
		
		Returns:
			$this for chainability.
	*/
	
	public function limit($offset, $amount = null)
	{
		$this->state_dirty = true;
		
		$this->state['offset'] = $offset;
		$this->state['amount'] = $amount;		
		return $this;
	}
	
	/*
		Method:
			<DataMapper::uptodate>
		
		Keeps a <DomainObject> up-to date by either inserting or updating it, depedning on whether it has an ID. See <DataMapper::insert> or <DataMapper::update> for more details about the operations.
		
		(begin code)
		
		$post = $map->populate(new Post($id));
		$post->header = $post->header . ' updated!';
		$post->message = 'The message has been lost due to an example. Please direct your criticism to a brick wall.';
		$map->uptodate($post);
		
		// Updates the entry in the database
		
		$post = new Post();
		$post->set("header", "Posts keep disappearing");
		$post->set("message", "Why do my posts disappearing?! Rabble rabble rabble peas and carrots!");
		$map->uptodate($post);
		
		// Inserts the post into the database.
		
		(end code)
		
		Parameters:
			DomainObject $object - The <DomainObject> to update.
		
		Returns:
			The <DomainObject> $object originally passed.
	*/
	
	public function uptodate(DomainObject $object)
	{
		return ( ! $object->getID() ) ? $this->insert($object) : $this->update($object);
	}
	
	/*
		Method:
			<DataMapper::insert>
		
		Inserts the $object into the database. It uses the fields with a default value and values present to build the query, so you must properly populate the object before inserting.
		
		Parameters:
			DomainObject $object - The object to insert into the database.
		
		Returns:
			The passed <DomainObject> $object.
	*/
	
	public function insert(DomainObject $object)
	{
		$query = $this->builder->buildInsert($object);
		$result = Current::db()->execute($query);
		
		echo '<pre>', $query, '</pre>';
		
		return $object;
	}
	
	/*
		Method:
			<DataMapper::update>
		
		Updates the $object. Sets new values for all present values in the $object. Be sure to set the ID property of the $object before updating.
		
		Parameters:
			DomainObject $object - The $object to update.
		
		Returns:
			The <DomainObject> to update.
	*/
	
	public function update(DomainObject $object)
	{
		$query = $this->builder->buildUpdate($object);
		$result = Current::db()->execute($query);
		
		echo '<pre>', $query, '</pre>';
		
		return $object;
	}
	
	/*
		Method:
			<DataMapper::remove>
		
		Removes an entry from the table.
		
		(begin code)
		
		// With just an ID
		$map->remove($id);
		
		// With an object
		$post = new Post($id);
		$map->populate($post);
		
		$map->remove($post);
		
		(end code)
		
		Parameters:
			$id - The ID to remove. Can also be a <DomainObject>-instance with an <DomainObject::$id>.
	*/
	
	public function remove($id)
	{
		$query = $this->builder->buildDelete($id);
		$result = Current::db()->execute($query);
		
		echo '<pre>', $query, '</pre>';
	}
	
	/*
		Method:
			<DataMapper::populateFromDBResult>
		
		Loops through $result's first row and inserts every field into the passed <DomainObject>.
		
		Parameters:
			DomainObject $object - The object to populate.
			DBResult $result - The DBResult with a row for the $object.
			
		Returns:
			Returns the passed $object if there was a row to populate.
	*/

	public function populateFromDBResult(DomainObject $object, DBResult $result)
	{
		return $this->populateFromRow($ojbect, $result->row());
	}
	
	/*
		Method:
			<DataMapper::populateFromRow>
		
		Loops through the $row's fields and populates them to a <DomainObject>-instance.
		
		Parameters:
			Domainobject $object - The object to populate.
			array $row - The row fetched from the database whos values will be added the the $object.
		
		Returns:
			The passed $object.
	*/
	
	public function populateFromRow(DomainObject $object, array $row)
	{
		foreach ( $row as $field => $value )
		{
			if ( $field == 'id' ) $object->setID($value);
			else $object->set($field, $value);
		}
		return $object;
	}
	
	/*
		Method:
			<DataMapper::createObject>
		
		Create a <DomainObject>-object that maps to the current mapper.
		
		Returns:
			The new instance.
	*/
	
	public function createObject()
	{
		return new $this->object_name;
	}
	
	/*
		Method:
			<DataMapper::get>
		
		Returns an instance to the passed $mapper. If the class of the passed name it will be included from the <DataMapper::$mappers_dir>. Corresponding <DomainObject>s will also be included.
		
		Parameters:
			$mapper - The name of the mapper (_without_ the Mapper-postfix).
		
		Returns:
			An instance of the $mapper.
	*/
	
	public static function get($mapper)
	{
		$key = strtolower($mapper);
		
		// Include corresponding mapper-class
		if ( ! isset($instances[$key]) )
		{
			$mappername = $mapper . 'Mapper';
			if ( ! class_exists($mappername) )
			{
				self::includeMapper($mapper);
			}
			self::$instances[$key] = new $mappername;
		}
		
		// Include DomainObject-class
		if ( ! class_exists($mapper) )
		{
			self::includeObject($mapper);
		}
		
		return self::$instances[$key];
	}
	
	/*
		Method:
			<DataMapper::includeMapper>
		
		Includes the $mapper from the <DataMapper::$mappers_dir>. Will throw a <MapperNotFoundException> if it is not found.
		
		Parameters:
			$mapper - The name of the mapper.
	*/
	
	private static function includeMapper($mapper)
	{
		$filename = self::$mappers_dir . sprintf('mapper.%s.php', strtolower($mapper));
		if ( file_exists($filename) )
		{
			require($filename);
		}
		else
		{
			throw new MapperNotFoundException($mapper);
		}
	}
	
	/*
		Method:
			<DataMapper::includeObject>
		
		Includes the $object from the <DataMapper::$objects_dir>. Will throw a <MapperObjectNotFoundException> if it is not found.
		
		Parameters:
			$object - The name of the object.
	*/
	
	private static function includeObject($object)
	{
		$filename = self::$objects_dir . sprintf('object.%s.php', strtolower($object));
		if ( file_exists($filename) )
		{
			require($filename);
		}
		else
		{
			throw new MapperObjectNotFoundException($object);
		}
	}
}

// REMOVE THIS
class PostMapper extends DataMapper
{
	protected $table = 'forum_posts';
	protected $primary_key = 'post_id';
}


class DOMemberNotFoundException extends Exception {}
class DOValidationException extends Exception {}
class DOFaultyIDException extends Exception {}

/*
	Class:
		<DomainObject>
	
	A DomainObject represents an object of the real world. A Post, a User or a Cat.
*/

abstract class DomainObject
{
	/*
		Property:
			<DomainObject::$members>
		
		Contains the members of the object. The name of the member should be a key of an entry in the array, and the value should be an array of validator functions which should help validate the input when setting values. The string 'yes' corresponds to true, and 'no' is the same as false. A default value can also be defined in this validator array.
	*/
	
	protected $members = array();
	
	// Property: <DomainObject::$values>
	// The values end up in this array efter they have been <DomainObject::set>
	private $values = array();
	
	// Property: <DomainObject::$validator>
	// <Validator> for validating input.
	private $validator;
	
	// Property: <DomainObject::$id>
	// Every qualified <DomainObject> should have a $id-property.
	private $id;
	
	/*
		Constructor:
			<DomainObject::__construct>
		
		Inserts default values in the <DomainObject::$values>-array.
		
		Parameters:
			$id - (optional) Short-hand for setting an objects ID.
	*/
	
	public function __construct($id = null)
	{
		if ( ! is_null($id) )
		{
			$this->setID($id);
		}
		
		foreach ( $this->members as $name => $rules )
		{
			if ( isset($rules['default']) )
			{
				$this->values[$name] = $rules['default'];
				unset($rules['default']);
			}
		}
		
		$this->validator = new Validator();
	}
	
	public function set($name, $value)
	{
		if ( ! isset($this->members[$name]) )
		{
			throw new DOMemberNotFoundException($name);
		}
		
		if ( $this->validate($name, $value) )
		{
			$this->values[$name] = $value;
		}
	}
	
	public function get($name)
	{
		if ( ! isset($this->members[$name]) )
		{
			throw new DOMemberNotFoundException($name);
		}
		
		return $this->values[$name];
	}
	
	public function __get($name)
	{
		return $this->get($name);
	}
	
	public function __set($name, $value)
	{
		return $this->set($name, $value);
	}
	
	public function setID($id)
	{
		if ( ! is_numeric($id) )
		{
			throw new DOFaultyIDException($id);
		}
		
		$this->id = $id;
	}
	
	public function getID()
	{
		return $this->id;
	}
	
	public function fetch()
	{
		return $this->values;
	}
	
	private function validate($name, $input)
	{
		$rules = $this->members[$name];
		
		foreach ( $rules as $rule => $arg )
		{
			$this->validator->validate($input, $rule, $arg);
		}
		
		return true;
	}
}

//Current::initialize();
//Validator::setPath(Current::$config->get('paths.validators'));

class Post extends DomainObject
{
	protected $members = array(
		'header' => array('is_mandatory' => 'yes', 'max_length' => 50),
		'message' => array('is_mandatory' => 'yes'),
		'added' => array('is_date' => 'yes'),
		'edited' => array('is_date' => 'yes')
	);
}

/*printf("<h3>Testing DomainObject</h3>");

$post = new Post();
$post->set("header", "Testing this baby out! " . str_repeat("*", 2));
$post->set("body", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc in purus est, quis semper dolor. Morbi malesuada scelerisque lectus ut fermentum. Integer congue consectetur erat, tristique ultricies dolor eleifend vel. Phasellus mattis feugiat tincidunt. Donec eu ante leo. In ultrices urna leo. Nam lacinia, felis non eleifend rhoncus, mauris risus bibendum ante, id vulputate libero odio ut risus. Cras viverra, leo ut hendrerit bibendum, quam neque mattis purus, vitae porttitor turpis mauris vel risus. Nullam eget urna est, ut fermentum leo. Mauris nec sapien urna. Cras venenatis tempor tellus vel laoreet. Nulla malesuada placerat convallis. Quisque ac accumsan dolor. Suspendisse odio magna, bibendum quis varius in, aliquet non justo. Sed auctor turpis suscipit lectus semper sollicitudin. Suspendisse in turpis nec nulla condimentum tristique vitae eget dolor.");

printf("<p>Passed.</p>");*/


/*
	Class:
		<DomainCollection>
	
	General purpose class for a database rowset and lazy instantiaton of <DomainObject>-objects.
*/

class DomainCollection implements Iterator
{
	private $result;
	private $instances = array();
	private $mapper;
	
	public function __construct(DBResult $result, DataMapper $mapper)
	{
		$this->result = $result;
		$this->mapper = $mapper;
	}
	
	public function rewind()
	{
		$this->result->rewind();
	}
	
	public function current()
	{
		return $this->get($this->result->key());
	}
	
	public function key()
	{
		return $this->result->key();
	}
	
	public function next()
	{
		$this->result->next();
		return $this->get($this->result->key());
	}
	
	public function valid()
	{
		return $this->result->valid();
	}
	
	public function count()
	{
		return $this->result->getNumRows();
	}
	
	public function get($index)
	{		
		if ( ! isset($this->instances[$index]) )
		{
			if ( ! $this->result->get($index) )
			{
				return false;
			}
			
			$instance = $this->mapper->createObject();
			$this->mapper->populateFromRow($instance, $this->result->get($index));
			$this->instances[$index] = $instance;
		}
		return $this->instances[$index];
	}
}


class QBInvalidArgumentException extends Exception {}

/*
	Class:
		<QueryBuilder>
	
	Takes a table and primary_key and is used by <DataMapper> to build querys.
*/

class QueryBuilder
{
	// Property: <QueryBuilder::$table>
	// Contains the table for which the particular <QueryBuilder> is pointed to.
	private $table;
	
	// Property: <QueryBuilder::$primary_key>
	// Contains the name of the primary key for the table.
	private $primary_key;
	
	public function __construct($table, $primary_key)
	{
		$this->table = $table;
		$this->primary_key = $primary_key;
	}
	
	public function buildFind($args, $orderby, $offset, $amount)
	{
		$query = sprintf('SELECT * FROM `%s`', $this->table) . PHP_EOL;
		
		// Where
		if ( is_array($args) && count($args) )
		{
			$query .= 'WHERE ';
			$args = array_map(array('QueryBuilder', 'quoteValue'), $args);
			foreach ( $args as $key => $val )
			{
				if ( is_array($val) )
				{
					$args[$key] = $key . implode(' AND ' . $key, $val);
				}
				else
				{
					$args[$key] = sprintf('`%s`%s', $key, $val);
				}
			}
			$query .= implode(' AND ', $args) . PHP_EOL;
		}
		elseif ( $args != '*' && $args != 'all' )
		{
			throw new QBInvalidArgumentException($args);
		}
		
		// Order by
		if ( is_array($orderby) )
		{
			$query .= 'ORDER BY ' . implode(', ', array_map(array('QueryBuilder', 'quoteField'), $orderby)) . PHP_EOL;
		}
		elseif (! empty($orderby) )
		{
			$query .= 'ORDER BY ' . $orderby . PHP_EOL;
		}
		
		// Limit
		if ( is_null($amount) && ! is_null($offset) )
		{
			$query .= 'LIMIT ' . $offset . PHP_EOL;
		}
		elseif ( ! is_null($offset) && ! is_null($amount) )
		{
			$query .= 'LIMIT ' . $offset . ', ' . $amount . PHP_EOL;
		}
		
		return $query;
	}
	
	public function query($query, DomainObject $object)
	{
		
	}
	
	public  function buildSelect(DomainObject $object)
	{
		$query = sprintf('SELECT * FROM `%s`', $this->table) . PHP_EOL;
		$query .= 'WHERE ';
		
		if ( ! $object->getID() )
		{
			foreach ( $object->fetch() as $key => $value )
			{
				$query .= sprintf('`%s` = %s AND', $key, self::quote($value)) . PHP_EOL;
			}
			$query = substr($query, 0, -strlen(' AND' . PHP_EOL));
		}
		else
		{
			$query .= sprintf(' `%s` = %d', $this->primary_key, $object->getID()) . PHP_EOL;
			$query .= ' LIMIT 1' . PHP_EOL;
		}
		
		return $query;
	}
	
	public  function buildInsert(DomainObject $object)
	{
		$query = sprintf('INSERT INTO `%s`', $this->table) . PHP_EOL;
		
		$fields = '(';
		$values = 'VALUES(';
		
		$values_arr = $object->fetch();
		foreach ( $values_arr as $key => $value )
		{
			$fields .= '`' . $key . '`, ';
			$values .= self::quote($value) . ', ';
		}
		
		$fields = substr($fields, 0, -2);
		$values = substr($values, 0, -2);
		
		$fields .= ')';
		$values .= ')';
		
		$query .= $fields . PHP_EOL . $values . PHP_EOL;
		return $query;
	}
	
	public  function buildUpdate(DomainObject $object)
	{
		$query = sprintf('UPDATE `%s`', $this->table) . PHP_EOL;
		$query .= 'SET ';
		foreach ( $object->fetch() as $key => $value )
		{
			$query .= PHP_EOL . sprintf('`%s` = %s, ', $key, self::quote($value));
		}
		$query = substr($query, 0, -2);
		$query .= PHP_EOL . sprintf('WHERE `%s` = %s LIMIT 1', $this->primary_key, self::quote($object->getID()));
		return $query;
	}
	
	public  function buildDelete($id)
	{
		if ( $id instanceof DomainObject )
		{
			$id = $id->getID();
		}
		
		$query = sprintf('DELETE FROM `%s` WHERE `%s` = %d LIMIT 1', $this->table, $this->primary_key, $id);
		
		return $query;
	}
	
	public static function quoteValue($value)
	{
		if ( is_array($value) )
		{
			return array_map(array('QueryBuilder', 'quoteValue'), $value);
		}
		
		if ( ! in_array($value[0], array('=', '<', '>')) )
		{
			return ' = ' . self::quote($value);
		}
		else
		{
			$operator = substr($value, 0, strpos($value, ' '));
			$value = substr($value, strpos($value, ' ') + 1);
			return ' ' . $operator . ' ' . self::quote($value);
		}
	}
	
	public static function quoteField($field)
	{
		return '`' . $field . '`';
	}
	
	public static function quote($value)
	{		
		return (is_numeric($value)) ? $value : sprintf('"%s"', $value);
	}
}
