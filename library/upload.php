<?php

class UploadException extends Exception {}

/*
	Class:
		Upload
	
	Nice and simple interface to file uploads.
	
	Example:
	
		$upload = new Upload('image');
		$upload->setDestinationDirectory('static/images/');
		$upload->setMaxSize(8, 'mb');
		$upload->setAllowedExtensions(array('png', 'gif', 'jpg', 'jpeg'));
	
		if ( ! $upload->upload() )
		{
			print "Upload error: " . $upload->getError();
		}
		else
		{
			printf("Uploaded to: %s", $upload->getPath());
		}
	
	Works great with ImageManip class too. Just create one, set the settings you want, and tell 
	the upload instance to use that ImageManip:
	
	Example:
	
		$upload = new Upload('image');
	
		$manip = new ImageManip();
		$manip->setCanvasSize(100, 100);
	
		$upload->setImageManip($manip);
		$upload->upload();
*/

class Upload
{
	private static $exponents = array(
		'b' => 1,
		'kb' => 1024, 
		'mb' => 1048576,
		'gb' => 1073741824
	);
	
	const OVERWRITE = 1;
	const NUMBER = 2;
	const RANDOM = 3;
	
	private $dir;
	private $name;
	
	private $imanip = false;
	
	private $max_size = 10485760; // 10 mb
	private $allowed_extensions = array('png', 'gif', 'jpg', 'jpeg');
	private $name_collision_strategy = Upload::NUMBER;
	private $error = false;
	
	public function __construct($file_key)
	{
		$this->data = isset($_FILES[$file_key]) ? $_FILES[$file_key] : false;
		
		if ( $this->data )
		{
			$this->name = $this->data['name'];
		}
	}
	
	public function setMaxSize($size, $exp = 'mb')
	{
		if ( ! isset(self::$exponents[$exp]) )
			throw new UploadException("Max size exponent not found: " . $exp);
		
		$this->max_size = $size * self::$exponents[$exp];
	}
	
	public function setDestinationDirectory($dir)
	{
		if ( ! is_dir($dir) )
			throw new UploadException("Destination directory didn't exist: " . $dir);
		
		$this->dir = rtrim($dir, '/') . '/';
	}
	
	public function setName($name, $collision_strategy = Upload::NUMBER)
	{
		$this->name = $name;
		$this->name_collision_strategy = Upload::NUMBER;
	}
	
	public function setAllowedExtensions($extensions)
	{
		$this->allowed_extensions = $extensions;
	}
	
	public function setImageManip(ImageManip $manip)
	{
		$this->imanip = $manip;
	}
	
	public function upload()
	{
		if ( ! $this->data )
			return $this->setError("no_data");
		
		if ( ! in_array($this->getExtension(), $this->allowed_extensions) )
			return $this->setError("bad_extension");
		
		if ( $this->data['error'] !== UPLOAD_ERR_OK )
			return $this->setError("bad_upload");
		
		if ( $this->data['size'] > $this->max_size )
			return $this->setError("too_big");
		
		if ( file_exists($this->getPath()) )
		{
			switch ($this->name_collision_strategy)
			{
				case UPLOAD::OVERWRITE: break;
				case UPLOAD::NUMBER: $this->resolveCollisionWithNumbering(); break;
				case UPLOAD::RANDOM: $this->resolveCollisionWithRandomName(); break;
			}
		}
		
		if ( ! is_uploaded_file($this->data['tmp_name']) )
			return $this->setError("not_uploaded_file");
		
		if ( ! move_uploaded_file($this->data['tmp_name'], $this->getPath()) )
			return $this->setError("could_not_move");
		
		if ( $this->imanip )
		{
			try {
				$this->imanip->setPath($this->getPath());
				
				if ( ! $this->imanip->save() )
				{
					unlink($this->path);
					return $this->setError("no_imagemanip");
				}
			}
			catch (ImageManipException $e)
			{
				unlink($this->path);
				return $this->setError("imagemanip_error");
			}
		}
		
		return true;
	}
	
	private function resolveCollisionWithRandomName()
	{
		$ext = $this->getExtension();
		do {
			$this->name = random(1111111, 9999999) . time() . uniqid() . '.' . $ext;
		} while ( file_exists($this->getPath()) );
	}
	
	private function resolveCollisionWithNumbering()
	{
		$ext = $this->getExtension();
		$number = $this->getNumberOnName();
		do {
			
			$this->name = $this->addNumberToName();
		} while ( file_exists($this->getPath()) );
	}
	
	public function getPath()
	{
		return $this->dir . $this->name;
	}
	
	public function getExtension()
	{
		return strtolower(array_last(explode('.', $this->name)));
	}
	
	public function getNumberOnName()
	{
		$pieces = explode('.', $this->name);
		$name = $pieces[0];
		
		$matches = array();
		if ( preg_match('#(\d+)$#', $name, $matches) )
		    return intval($matches[1]);
	
		return 0;
	}
	
	private function addNumberToName()
	{
		$number = $this->getNumberOnName() + 1;
		$pieces = explode('.', $this->name);
		$pieces[0] = preg_replace('#(\d+)$#', '', $pieces[0]) . $number;
		return implode('.', $pieces);
	}
	
	private function setError($error)
	{
		$this->error = $error;
		return false;
	}
	
	public function getError()
	{
		return $this->error;
	}
}
