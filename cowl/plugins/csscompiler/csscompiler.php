<?php

class CSSCompiler
{
	private static $css_dir;
	
	private $code;
	private $regex_loads      = '/@load\(([^)]+)\);/';
	private $regex_includes   = '/(@include\(([^)]+)\);)/';
	private $regex_extraction = '/(\$[^{;]+)(\{[^}]+\})/';
	private $regex_assignment = '/([^\/;}]+)=\s*(\$[^;]+);/';
	private $regex_selector   = '/^\$[A-Za-z_-]+/';
	
	private $constants = array();
	private $users = array();
	
	public function __construct($code)
	{
		$this->code = $code;
	}
	
	public static function setDir($dir)
	{
		self::$css_dir = $dir;
	}
	
	public function compile()
	{
		$this->fetchIncludes();
		$this->fetchLoads();
		$this->fetchConstants();
		$this->fetchAssignments();
		$this->applyConstants();
		$this->compress();
		
		return $this->code;
	}
	
	public function fetchIncludes()
	{
		$this->code = preg_replace_callback($this->regex_includes, array($this, 'includeFound'), $this->code);	}
	
	private function includeFound($match)
	{
		$path = trim($match[2], '"\'');
		return $this->includeFile(self::$css_dir . $path);
	}
	
	public function includeFile($filename)
	{
		$contents = file_get_contents($filename);
		
		$compiler = new self($contents);
		$compiler->fetchIncludes();
		
		return $compiler->getCode();
	}
	
	public function fetchLoads()
	{
		$this->code = preg_replace_callback($this->regex_loads, array($this, 'loadFound'), $this->code);
	}
	
	private function loadFound($match)
	{
		$path = trim($match[1], '"\'');
		$this->loadFile(self::$css_dir . $path);
	}
	
	public function loadFile($filename)
	{
		$contents = file_get_contents($filename);
		
		$compiler = new self($contents);
		$compiler->fetchLoads();
		$compiler->fetchConstants();
		
		$this->constants = array_merge($this->constants, $compiler->getConstants());
	}
	
	public function fetchConstants()
	{
		$this->code = preg_replace_callback($this->regex_extraction, array($this, 'constantFound'), $this->code);
	}
	
	private function constantFound($match)
	{
		$selector = trim($match[1]);
		$body = $match[2];
		
		$this->constants[$selector] = $body;
		
		// Remove constant definition
		return '';
	}
	
	private function fetchAssignments()
	{
		$this->code = preg_replace_callback($this->regex_assignment, array($this, 'constantAssigned'), $this->code);
	}
	
	private function constantAssigned($match)
	{
		$selectors = array_map('trim', explode(',', $match[1]));
		$constants = array_map('trim', explode(',', $match[2]));
		
		foreach ( $constants as $constant )
		{
			if ( ! isset($this->users[$constant]) )
			{
				$this->users[$constant] = array();
			}
			
			$this->users[$constant] = array_unique(array_merge($this->users[$constant], $selectors));
		}
			
		// Remove assignments
		return '';
	}
	
	private function applyConstants()
	{
		$constants = array_keys($this->constants);
		
		foreach ( $constants as $constant )
		{
			// If the constant has a selecter to it, extract it and apply that to the $users
			$selector_part = preg_replace($this->regex_selector, '$1', $constant);
			
			if ( strlen($selector_part) )
			{
				$name_part = substr($constant, 0, -strlen($selector_part));
				
				if ( isset($this->users[$name_part]) )
				{
					$users = $this->users[$name_part];
				
					foreach ( $users as $key => $user )
					{
						$users[$key] .= $selector_part;
					}
				}
				else
				{
					continue;
				}
			}
			elseif ( isset($this->users[$constant]) )
			{
				$users = $this->users[$constant];
			}
			else
			{
				continue;
			}
			
			$this->code .= implode(', ', $users) . ' ';
			$this->code .= $this->constants[$constant] . PHP_EOL . PHP_EOL;
		}
	}
	
	public function compress()
	{
		$this->code = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $this->code);
		$this->code = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $this->code);
		$this->code = str_replace('{ ', '{', $this->code);
		$this->code = str_replace(' }', '}', $this->code);
		$this->code = str_replace('; ', ';', $this->code);	
	}
	
	public function getCode()
	{
		return $this->code;
	}
	
	public function getConstants()
	{
		return $this->constants;
	}
	
	public function getUsers()
	{
		return $this->users;
	}
}

/*CSSCompiler::setDir(dirname(__FILE__) . '/testcss/');

$css = file_get_contents(dirname(__FILE__) . '/testcss/test.css');
$compiler = new CSSCompiler($css);

echo '<pre>', $compiler->compile();

echo '</pre><h1>' . xdebug_time_index() . '</h1>';
die;*/
