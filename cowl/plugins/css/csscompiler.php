<?php

/*
	Class:
		<CSSCompiler>
	
	Adds extra functionality to CSS. Adds constants and server-side includes and loads. Will also minify the resulting code.
	
	Examples:
		// Just your regular old CSS
		#posts .post {
			margin: 1em 2em;
		}
		
			#posts .post h2 {
				color: #444;
			}
	
		// constants example
		$posts .post {
			margin: 1em 2em;
		}
		
			$posts .post h2 {
				color: #444;
			}
		
		#forum-posts, #guestbook-posts = $posts;
		#comments = $posts;
		
		// Constants can be loaded using the @load(); function
		// Only constants will be loaded. Includes will be resolved, but any CSS is ignored
		// It really doesn't matter where you put your @loads, because they are handled before anything else
		@load(site/constants.css);
		
		// Entire files, including constants, and nested includes can be included using @include();
		@include(site/main.css);
*/

class CSSCompiler
{
	// Property: <CSSCompiler::$css_dir>
	// Is like the PHP include_path property.
	private static $css_dir;
	
	// Property: <CSSCompiler::$code>
	// Contains the code to be parsed.
	private $code;
	
	// Regular expressions used for parsing.
	private $regex_loads      = '/@load\(([^)]+)\);/';
	private $regex_includes   = '/(@include\(([^)]+)\);)/';
	private $regex_extraction = '/(\$[^{;]+)(\{[^}]+\})/';
	private $regex_assignment = '/([^\/;}]+)=\s*(\$[^;]+);/';
	private $regex_selector   = '/^\$[A-Za-z_-]+/';
	
	// Property: <CSSCompiler::$constants>
	// Contains the constants used. The key is the constant, and the value is the resulting CSS
	// Because of this there can only be one constant definition per constant.
	private $constants = array();
	
	// Property: <CSSCompiler::$users>
	// Contains selectors which use constants.
	private $users = array();
	
	/*
		Constructor:
			<CSSCompiler::__construct>
		
		Parameters:
			$code - The code to be parsed.
	*/
	
	public function __construct($code)
	{
		$this->code = $code;
	}
	
	/*
		Method:
			<CSSCompiler::setDir>
		
		Set the base directory for includes.
		
		Parameters:
			$dir - The directory.
	*/
	
	public static function setDir($dir)
	{
		self::$css_dir = $dir;
	}
	
	/*
		Method:
			<CSSCompiler::compile>
		
		Compile and return the compiled code. See <CSSCompiler::fetchIncludes>, <CSSCompiler::fetchLoads>, <CSSCompiler::fetchConstants>, <CSSCompiler::fetchAssignments>, <CSSCompiler::applyConstants>, <CSSConstants::compress> for further details.
		
		Returns:
			The compiled code, minified.
	*/
	
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
	
	/*
		Method:
			<CSSCompiler::fetchIncludes>
		
		Fetch includes. @include(path/to/my.css); Quotes are not necessary, but possible, as they are trim'ed off.
	*/
	
	public function fetchIncludes()
	{
		$this->code = preg_replace_callback($this->regex_includes, array($this, 'includeFound'), $this->code);
	}
	
	// Method: <CSSCompiler::includeFound>
	// Callback for preg_replace_callback.
	private function includeFound($match)
	{
		$path = trim($match[2], '"\'');
		return $this->includeFile(self::$css_dir . $path);
	}
	
	/*
		Method:
			<CSSCompiler::includeFile>
		
		Fetches contents from one file, creates a new instance of a <CSSCompiler> to handle the includes and returns the included code.
		
		Parameters:
			$filename - The filename of the file to include, _not_ including <CSSCompiler::$css_dir>.
		
		Returns:
			The code, with parsed includes, only. No constants or @loads loaded.
	*/
	
	public function includeFile($filename)
	{
		$contents = file_get_contents($filename);
		
		$compiler = new self($contents);
		$compiler->fetchIncludes();
		
		return $compiler->getCode();
	}
	
	/*
		Method:
			<CSSCompiler::fetchLoads>
		
		Fetch @load()'s from <CSSCompiler::$code>. Syntax is as follows (single-/double-quotes are optional):
		
		Examples:
			@load(path/to/my/file.css);
	*/
	
	public function fetchLoads()
	{
		$this->code = preg_replace_callback($this->regex_loads, array($this, 'loadFound'), $this->code);
	}
	
	// Method: <CSSCompiler::loadFound>
	// Callback for preg_replace_callback, called in <CSSCompiler::fetchLoads>.
	private function loadFound($match)
	{
		$path = trim($match[1], '"\'');
		$this->loadFile(self::$css_dir . $path);
	}
	
	/*
		Method:
			<CSSCompiler::loadFile>
		
		Works like <CSSCompiler::includeFile>, but instead of just resolving includes, also fetches loads. The difference from <CSSCompiler::includeFile> is that any non-constants CSS is ignored.
	
		Parameters:
			$filename - The filename, including <CSSCompiler::$css_dir>
	*/
	
	public function loadFile($filename)
	{
		$contents = file_get_contents($filename);
		
		$compiler = new self($contents);
		$compiler->fetchLoads();
		$compiler->fetchConstants();
		
		$this->constants = array_merge($this->constants, $compiler->getConstants());
	}
	
	/*
		Method:
			<CSSCompiler::fetchConstants>
		
		Fetch constants from the code.
		
		Examples:
			$foo {
				/* ... rules... * /
			}
	*/
	
	public function fetchConstants()
	{
		$this->code = preg_replace_callback($this->regex_extraction, array($this, 'constantFound'), $this->code);
	}
	
	// Method: <CSSCompiler::constantFound>
	// Callback function for preg_replace_callback called by <CSSCompiler::fetchConstants>.
	private function constantFound($match)
	{
		$selector = trim($match[1]);
		$body = $match[2];
		
		$this->constants[$selector] = $body;
		
		// Remove constant definition
		return '';
	}
	
	/*
		Method:
			<CSSCompiler::fetchAssignments>
		
		Fetches constant assignments.
		
		Examples:
			$foo {
				color: #333;
			}
			
			.class, .other-class = $foo;
	*/
	
	private function fetchAssignments()
	{
		$this->code = preg_replace_callback($this->regex_assignment, array($this, 'constantAssigned'), $this->code);
	}
	
	// Method: <CSSCompiler::constantAssigned>
	// Callback function for preg_match_callback called in <CSSCompiler::fetchAssignments>.	
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
	
	/*
		Method:
			<CSSCompiler::applyConstants>
		
		Applies constants to assignments.
	*/
	
	private function applyConstants()
	{
		$constants = array_keys($this->constants);
		
		foreach ( $constants as $constant )
		{
			// If the constant has a selecter to it, extract it and apply that to the $users
			$selector_part = preg_replace($this->regex_selector, '$1', $constant);
			
			// Was there a selector?
			if ( strlen($selector_part) )
			{
				// Fetch the name of the constant
				$name_part = substr($constant, 0, -strlen($selector_part));
				
				// Apply to all users
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
			// Just you're regular old constant
			elseif ( isset($this->users[$constant]) )
			{
				$users = $this->users[$constant];
			}
			// No users, skip it!
			else
			{
				continue;
			}
			
			$this->code .= implode(', ', $users) . ' ';
			$this->code .= $this->constants[$constant] . PHP_EOL . PHP_EOL;
		}
	}
	
	/*
		Method:
			<CSSCompiler::compress>
		
		Compresses the code by removing whitespace appropriately.
		
		Author:
			Andreas Lagerkvist at andreaslagerkvist.com
	*/
	
	public function compress()
	{
		$this->code = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $this->code);
		$this->code = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $this->code);
		$this->code = str_replace('{ ', '{', $this->code);
		$this->code = str_replace(' }', '}', $this->code);
		$this->code = str_replace('; ', ';', $this->code);	
	}
	
	// Method: <CSSCompiler::getCode>
	// Returns: <CSSCompiler::$code>
	public function getCode()
	{
		return $this->code;
	}
	
	// Method: <CSSCompiler::getConstants>
	// Returns: <CSSCompiler::$constants>
	public function getConstants()
	{
		return $this->constants;
	}
	
	// Method: <CSSCompiler::getUsers>
	// Returns: <CSSCompiler::$users>	
	public function getUsers()
	{
		return $this->users;
	}
}
