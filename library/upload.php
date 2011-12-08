<?php

class Upload
{
	public function __construct($file_arr)
	{
		
	}
}

Library::load('upload');

$uploader = new Upload();
$uploader->setRules(array(
	'size' => 10
));
$uploader->upload('avatar');
