<?php

class Upload
{
	public function __construct($file_arr)
	{
		
	}
}

Library::load('Upload');

$uploader = new Upload($_FILES['avatar']);
$uploader->setRules(array(
	'size' => 10
));
$uploader->upload();
