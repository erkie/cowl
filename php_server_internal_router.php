<?php

$static_file = __DIR__ . '/..' . $_SERVER['SCRIPT_NAME'];

if (file_exists($static_file)) {
	return false;
}

$_SERVER['REQUEST_URI'] = "/index.php" . $_SERVER['REQUEST_URI'];
$_SERVER['SCRIPT_NAME'] = "/index.php";

include "index.php";