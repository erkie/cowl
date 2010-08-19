<?php

/*
	Class:
		<Plugin>
	
	Abstract base class for all plugins.
*/

abstract class Plugin
{
	// FrontController-related hooks
	public function prePathParse(Controller $controller) {}
	public function postPathParse($args) {}
	public function postRun() {}
	
	// Command-related hooks
	public function commandRun($method, $args) {}
	
	// ORM-related hooks
	public function dbPopulate(DataMapper $mapper, DomainObject $object) {}
	public function dbFind(DataMapper $mapper, $args) {}
	public function dbInsert(DataMapper $mapper, DomainObject $object) {}
	public function dbUpdate(DataMapper $mapper, DomainObject $object) {}
	public function dbRemove(DataMapper $mapper, $id) {}
}
