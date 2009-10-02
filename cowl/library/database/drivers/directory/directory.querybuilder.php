<?php

class QueryBuilder
{
	private $directory;
	
	public function __construct($directory, $nothing)
	{
		$this->directory = $directory;
	}
	
	public function buildFind($pattern, $orderby, $offset, $amount)
	{
		return array(
			'type' => 'find',
			'pattern' => $pattern,
			'sort' => $orderby,
			'limit' => array($offset, $amount)
		);
	}
	
	public function buildFindObject(DirectoryObject $object)
	{
		return array(
			'type' => 'find',
			'object' => $object
		);
	}
	
	public function buildInsert(DirectoryObject $object)
	{
		return array(
			'type' => 'insert',
			'object' => $object
		);
	}
	
	public function buildUpdate(DirectoryObject $object)
	{
		return array(
			'type' => 'update',
			'object' => $object
		);
	}
	
	public function buildDelete(DirectoryObject $object)
	{
		return array(
			'type' => 'delete',
			'object' => $object
		);
	}
	
	public function buildCount($pattern, $ordery, $offset, $amount)
	{
		return array(
			'type' => 'count',
			'pattern' => $pattern,
			'sort' => $orderby,
			'limit' => array($offset, $amount)
		);
	}
}
