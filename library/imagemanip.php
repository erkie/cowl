<?php

class ImageManipFileNotFoundException extends Exception {}
class ImageManipUnsupportedFormatException extends Exception {}
class ImageManipException extends Exception {}

class ImageManip
{
	public static $FORMATS = array('png', 'gif', 'jpg', 'jpeg');
	
	private $path;
	private $size = false;
	private $max_size = false;
	private $format = false;
	
	private $res = false;
	
	public function __construct($path)
	{
		if ( ! file_exists($path) )
		{
			throw new ImageManipFileNotFoundException($path);
		}
		
		$this->path = $path;
		
		// Try to determine format by file extension
		$format = strtolower(array_last(explode('.', $this->path)));
		if ( ! in_array($format, self::$FORMATS) )
		{
			throw new ImageManipUnsupportedFormatException($format);
		}
		$this->format = $format;
	}
	
	public function setSize($w, $h)
	{
		$this->size = array('w' => $w, 'h' => $h);
	}
	
	public function setMaxSize($w, $h)
	{
		$this->max_size = array('w' => $w, 'h' => $h);
	}
	
	public function setFormat($format)
	{
		if ( ! in_array($format, self::$FORMATS) )
		{
			throw new ImageManipUnsupportedFormat($format);
		}
		
		$this->format = $format;
	}
	
	public function save($filename = false)
	{
		$this->openImage();
		
		if ( $this->max_size )
			$this->ensureMaxSize();
		
		if ( $this->size )
			$this->ensureSize();
		
		$this->saveToDisk($filename);
	}
	
	private function openImage()
	{
		$data = getimagesize($this->path);
		
		// Make sure we can read the image
		$accepted_formats = array(
			'image/jpeg' => 'jpeg',
			'image/pjpeg' => 'jpeg',
			'image/png' => 'png',
			'image/x-png' => 'png',
			'image/gif' => 'gif'
		);
		
		if ( ! isset($accepted_formats[$data['mime']]) )
			throw new ImageManipException();
		
		$load_function = sprintf('imagecreatefrom%s', $accepted_formats[$data['mime']]);
		$this->res = call_user_func($load_function);
	}
	
	private function ensureMaxSize()
	{
		list($w, $h) = $this->getSizeOnDisk();
		
		$original_w = $w;
		$original_h = $h;
		
		if ( $w > $this->max_size['w'] )
		{	
			$h *= $this->max_size['w']/$w;
			$w = $this->max_size['w'];
		}
		
		if ( $h > $this->max_size['h'] )
		{
			$w *= $this->max_size['h']/$h;
			$h = $this->max_size['h'];
		}
		
		$this->setSize($w, $h);
	}
	
	private function ensureSize()
	{
		
	}
	
	private function saveToDisk($filename)
	{
		
	}
	
	private function getSizeOnDisk()
	{
		list($width, $height) = getimagesize($this->path);
		return array($width, $height);
	}
}
