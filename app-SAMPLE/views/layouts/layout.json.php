<?php
	header('Content-type: application/json');
	echo json_encode($this->toJSON($this->vars));
