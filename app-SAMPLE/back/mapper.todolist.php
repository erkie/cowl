<?php

class TodoListMapper extends DataMapper
{
	protected $table = 'todolist';
	
	public function findWithItems($args, $order = '', $offset = null, $amount = null)
	{
		$profile = DataMapper::get('TodoItem');
		
		$query = $this->builder->setPrefix('u')->format('
			SELECT * FROM %(table)
			
			LEFT JOIN %(ptable) AS p ON p.user_id = u.id
			
			%(body)
		', array(
			'table' => $this->table,
			'ptable' => $profile->getTable(),
			'body' => $this->builder->buildSelectBody($args, $order, $offset, $amount)
		));
		
		$result = Current::$db->execute($query);
		
		return new DomainCollection($result, $this);
	}
}
