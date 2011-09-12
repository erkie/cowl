<?php

/*
	Class:
		StaticServer
	
	Class for serving static files. Caches and compresses CSS-files, should be able
	to minify JS files and cache them, serve images as well.
	
	Well, most of the cacheing and minifying is done by Cowl plugins. See frontcontroller.php.
*/

class StaticServer
{
	// Property: StaticServer::$files_dir
	// The directory where files can be found
	static protected $files_dir;
	
	// Property: StaticServer::$VALID_TYPES
	// To keep the StaticServer from serving bad files (such as php files)
	// the allowed types are defined here. If the request type does not match
	// any types here it should not be served statically
	protected static $MIMES = array(
		'json' => 'text/json',
		'css' => 'text/css',
		'js' => 'text/x-javascript',
		'jpg' => 'image/jpeg',
		'jpeg' => 'images/jpeg',
		'gif' => 'image/gif',
		'png' => 'image/png',
		'bmp' => 'image/bmp',
		'html' => 'text/html',
		'rss' => 'application/rss+xml',
		'partial' => 'text/html',
		'otf' => 'font/opentype',
		'ttf' => 'font/ttf'
	);

	static protected $BAD = array('php', 'phtml', 'ini', 'sql');
	
	// Property: StaticServer::$path
	// The path to (possible) serve
	protected $path;
	
	// Property: StaticServer::$is_file
	// Is the path tied to a file?
	protected $is_file = false;
	
	// Property: StaticServer::$type
	// The type of the file. (based on the extension)
	protected $type;
	
	/*
		Constructor
		
		Parameters:
			(string) $path - The path to server
	*/
	
	public function __construct($path)
	{
		$this->setPath($path);
	}
	
	/*
		Method:
			parsePath
		
		Parse the path and check for a static file.
	*/
	
	private function parsePath()
	{
		if ( empty($this->path) || (! strstr($this->path, 'gfx') && ! strstr($this->path, 'css')) )
		{
			$this->is_file = false;
			return;
		}

		// Check to see if it really exists
		if ( file_exists($this->path) )
		{
			$this->is_file = true;
		}
		// Try to translate to the app-directory
		// If it is successful <StaticServer::$path> will be altered
		elseif ( file_exists(self::$files_dir . $this->path) )
		{
			$this->path = self::$files_dir . $this->path;
			$this->is_file = true;
		}
		
		// Get the extension
		$this->type = strtolower(end(explode('.', $this->path)));
		
		// Bad filetype!
		if ( in_array($this->type, self::$BAD) )
		{
			$this->is_file = false;
		}
	}
	
	/*
		Method:
			isFile
		
		Check if the current path maps to a static path on disk.
		
		Returns:
			true if it is, otherwise false
	*/
	
	public function isFile()
	{
		return $this->is_file;
	}
	
	/*
		Method:
			render
		
		Render the request. The next course of action is to abort the script.
		That is left for the API user to do, so to facilitate clean up and other
		scenarios.
	*/
	
	public function render()
	{
		// Sanity check to see that render hasn't been called when there was no file
		if ( ! $this->is_file )
			return;

		$mime = isset(self::$MIMES[$this->type]) ? self::$MIMES[$this->type] : 'text/html';
	
		header('Content-type: ' . $mime);
		readfile($this->path);
	}
	
	/*
		Method:
			setPath
		
		Set the path for the request. This will be modified to the actual path on disk, if
		it exists. Otherwise the <StaticServer::$is_file>-property will be set to false.
		
		Parameters:
			(string) $path - The path of the request
	*/
	
	public function setPath($path)
	{
		$this->path = $path;
		$this->parsePath();
	}
	
	/*
		Method:
			setDir
		
		Set the dir in which all static files are contained.
	*/
	
	public static function setDir($dir)
	{
		self::$files_dir = $dir;
	}
	
	// Method: <StaticServer::getPath>
	// Return <StaticServer::$path>
	public function getPath() { return $this->path; }
	
	// Method: <StaticServer::getType>
	// Return <StaticServer::$type>
	public function getType() { return $this->type; }
}
