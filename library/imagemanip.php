<?php

class ImageManipFileNotFoundException extends Exception {}
class ImageManipUnsupportedFormatException extends Exception {}
class ImageManipBadGravity extends Exception {}
class ImageManipException extends Exception {}

class ImageManip
{
	public static $FORMATS = array('png', 'gif', 'jpg', 'jpeg');
	
	private $path;
	
	// Size settings
	private $canvas_size = false;
	private $size = false;
	private $max_size = false;
	
	private $max_allowed_size = array(4000, 4000);
	
	// Crop options
	private $crop_gravity = array('center', 'center');
	
	// Save options
	private $format = false;
	
	// Internal
	private $im = false; // The loaded image
	private $res = false; // The loaded canvas
	private $res_size = array(0, 0);
	private $dest_pos = array(0, 0);
	
	public function __construct($path = false)
	{
		if ( ! $path )
			return;
		$this->setPath($path);
	}
	
	public function setPath($path)
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
	
	/*
		Method:
			setCanvasSize
		
		Set the size of the image canvas. If present this will affect how <setSize> and <setMaxSize> are handled.
		If present the image will be drawn onto a canvas $wx$h big.
	*/
	
	public function setCanvasSize($w, $h)
	{
		$this->canvas_size = array($w, $h);
	}
	
	/*
		Method:
			setSize
		
		Set the size of the image without taking into account any aspect ratio.
	*/
	
	public function setSize($w, $h)
	{
		$this->size = array($w, $h);
	}
	
	/*
		Method:
			setMaxSize
		
		Set the max size of an image. This will respect the aspect ratio of the image.
	*/
	
	public function setMaxSize($w, $h)
	{
		$this->max_size = array($w, $h);
	}
	
	/*
		Method:
			setMaxImageSize
		
		Set the max size allowed for an image. If it is larger than this <save> will return false.
	*/
	
	public function setMaxImageSize($w, $h)
	{
		$this->max_allowed_size = array($w, $h);
	}
	
	/*
		Method:
			setFormat
		
		Force a specific image format.
	*/
	
	public function setFormat($format)
	{
		if ( ! in_array($format, self::$FORMATS) )
		{
			throw new ImageManipUnsupportedFormat($format);
		}
		
		$this->format = $format;
	}
	
	/*
		Method:
			setCropGravity
		
		Where the 'gravity' of a crop should be. So if an image is larger than the canvas, where should it 
		base the crop off?
		
		It takes two values, one for the x-axis, and one for the y-axis.
		
		Possible values are:
		
			x: left, right, center
			y: top, bottom, center
		
		Parameters:
			$gravity_x - The x-axis
			$gravity_y - The y-axis
	*/
	
	public function setCropGravity($gravity_x, $gravity_y)
	{
		if ( ! in_array($gravity_x, array('left', 'right', 'center')) )
			throw new ImageManipBadGravity($gravity_x);
		
		if ( ! in_array($gravity_y, array('top', 'center', 'bottom')) )
			throw new ImageManipBadGravity($gravity_y);
		
		$this->crop_gravity = array($gravity_x, $gravity_y);
	}
	
	/*
		Method:
			save
		
		Perform all cropping and processing and save the image.
		
		Parameters:
			$filename - If specified, the output filename, otherwise this will overwrite the original image
	*/
	
	public function save($filename = false)
	{
		if ( $this->sourceImageIsTooLarge() )
			return false;
		
		$this->openImage();
		
		if ( $this->max_size )
			$this->ensureMaxSize();
		
		$this->makeCanvas();
		$this->calculateCrop();
		$this->perform();
		
		$this->saveToDisk($filename ?: $this->path);
		
		return true;
	}
	
	public function sourceImageIsTooLarge()
	{
		if ( ! $this->max_allowed_size )
			return false;
		
		list($sw, $sh) = $this->getSizeOnDisk();
		return $sw > $this->max_allowed_size[0] || $sh > $this->max_allowed_size[1];
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
			throw new ImageManipException('Bad mime');
		
		$load_function = sprintf('imagecreatefrom%s', $accepted_formats[$data['mime']]);
		$this->im = call_user_func($load_function, $this->path);
		
		if ( ! $this->im )
			throw new ImageManipException('Could not load');
	}
	
	private function ensureMaxSize()
	{
		list($w, $h) = $this->getSizeOnDisk();
		
		$original_w = $w;
		$original_h = $h;
		
		if ( $w > $this->max_size[0] )
		{	
			$h *= $this->max_size[0]/$w;
			$w = $this->max_size[0];
		}
		
		if ( $h > $this->max_size[1] )
		{
			$w *= $this->max_size[1]/$h;
			$h = $this->max_size[1];
		}
		
		$this->setSize($w, $h);
	}
	
	private function makeCanvas()
	{	
		// Get size of canvas
		list($w, $h) = $this->canvas_size ?: $this->getSizeOnDisk();
		
		// Create GD canvas
		$this->res = imagecreatetruecolor($w, $h);
		$this->res_size = array($w, $h);
	}
	
	private function calculateCrop()
	{
		list($canvas_w, $canvas_h) = $this->res_size;
		
		// First check if the destination size needs cropping
		if ( $this->size && ($this->size[0] != $canvas_w || $this->size[1] != $canvas_h) )
		{	
			// Size is not the same as canvas, cropping will be had
			list($w, $h) = $this->size;
			
			$cropmap = array(
				'x' => array(
					'left' => 0,
					'center' => $canvas_w/2 - $w/2,
					'right' => $canvas_w - $w
				),
				'y' => array(
					'top' => 0,
					'center' => $canvas_h/2 - $h/2,
					'bottom' => $canvas_h - $h
				)
			);
			
			// This should calculate the x, y coordinates of the image to be drawn onto the canvas
			$x = $cropmap['x'][$this->crop_gravity[0]];
			$y = $cropmap['y'][$this->crop_gravity[1]];
		}
		else
			list($x, $y) = array(0, 0);
		
		$this->dest_pos = array($x, $y);
	}

	private function perform()
	{
		list($w, $h) = $this->size ?: $this->res_size;
		list($img_w, $img_h) = $this->getSizeOnDisk();
		
		imagecopyresampled($this->res, $this->im, $this->dest_pos[0], $this->dest_pos[1], 0, 0, $w, $h, $img_w, $img_h);
		imagedestroy($this->im);
	}
	
	private function saveToDisk($filename)
	{
		if ( $this->format == 'jpg' )
			imagejpeg($this->res, $filename, 100);
		elseif ( $this->format == 'png' )
			imagepng($this->res, $filename);
		elseif ( $this->format == 'gif' )
			imagegif($this->res, $filename);
		imagedestroy($this->im);
	}
	
	private function getSizeOnDisk()
	{
		list($width, $height) = getimagesize($this->path);
		return array($width, $height);
	}
}
