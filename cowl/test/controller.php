<?php

class Controller
{
	private $path;
	private static $commands_dir = 'commands/';
	
	private $main = 'MainCommand';
	private $error = 'ErrorCommand';
	
	public function __construct($path)
	{
		// the path which to be parsed
		$this->path = $path;
	}
	
	public function parse()
	{
		$path = trim($this->path, '/');
		$pieces = explode('/', $path);
		
		$args = $this->resolve($pieces);
		
		return $args;// implode('/', $args);
	}
	
	private function resolve($pieces)
	{
		if ( ! count($pieces) || empty($pieces[0]) )
		{
			return array($this->main);
		}
		
		$directories = $pieces;
		while ( count($directories) && ! is_dir(self::$commands_dir . implode('/', $directories)) )
		{
			array_pop($directories);
		}
		
		if ( ! $directories || ! count($directories) )
		{
			$name = self::$commands_dir . '/command.' . $pieces[0] . '.php';
			if ( file_exists($name) )
			{
				require($name);
				
				$command = array_shift($pieces) . 'Command';
				return array_merge(array($command), $pieces);
			}
			else
			{
				return array($this->error);
			}
		}
		else
		{
			$command = implode('', $directories) . 'Command';
			$args = array_slice($pieces, count($directories));
			
			$name = self::$commands_dir . implode('/', $directories) . '/command.' . end($directories) . '.php';
			require($name);
			
			return array_merge(array($command), $args);
		}
	}
	
	public static function setDir($dir)
	{
		self::$commands_dir = $dir;
	}
}

Controller::setDir(dirname(__FILE__) . '/app/front/');

$tests = array(
	'' => array('MainCommand'),
	'path/to/nothing' => array('ErrorCommand'),
	'main' => array('mainCommand'),
	'package1' => array('package1Command'),
	'package1/foo/bar' => array('package1Command', 'foo', 'bar'),
	'section/package2/' => array('sectionpackage2Command'),
	'section/package2/foo/bar' => array('sectionpackage2Command', 'foo', 'bar'),
	'section/sub/package3/' => array('sectionsubpackage3Command'),
	'section/sub/package3/foo' => array('sectionsubpackage3Command', 'foo')
);

echo '<pre>';

$passed = '';
foreach ( $tests as $url => $test )
{
	$controller = new Controller($url);
	$result = $controller->parse();
	
	if ( $result != $test )
	{
		printf('"%s" failed.' . PHP_EOL . 'Expected: 	%s' . PHP_EOL . 'Found: 		%s' . PHP_EOL . PHP_EOL, $url, implode(', ', $test), implode(', ', $result));
	}
	else
		$passed .= '.';
}

echo $passed;
if ( strlen($passed) == count($tests) )
{
	echo PHP_EOL . 'ALL TESTS PASSED';
}

echo '</pre>';