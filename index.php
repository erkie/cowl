<?php

error_reporting(E_ALL);

require('cowl/frontcontroller.php');

$controle = new FrontController();
$controle->execute();

$time = xdebug_time_index();
echo '<p>', $time, '</p>';

