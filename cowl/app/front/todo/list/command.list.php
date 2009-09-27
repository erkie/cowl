<?php

class TodoListCommand extends Command
{
	protected $objects = array('TodoList', 'TodoItem');
	
	public function index()
	{
		$lists = $this->todolistmapper->find('all');
		$items = $this->todoitemmapper->find('all');
		
		$temp = array();
		foreach ( $lists as $list )
		{
			$temp[$list->id] = array();
			
			foreach ( $items as $item )
			{
				if ( $item->list_id == $list->id && ! $item->is_done )
				{
					$temp[$list->id][] = $item;
				}
			}
		}
		
		$this->template->add('lists', $lists);
		$this->template->add('items', $temp);
	}
	
	public function add()
	{
		try {
			$name = Current::$request->get('name');
			
			$list = new TodoList();
			$list->name = $name;
			
			$list = $this->todolistmapper->uptodate($list);
			
			return array('todo', $list->getID());
		}
		catch ( RegistryException $e ) {}
		catch ( ValidatorException $e ) {}
	}
	
	public function remove($id = null)
	{
		if ( ! is_numeric($id) )
		{
			$this->template->add('message', 'No id!');
		}
		else
		{
			$this->todolistmapper->remove($id);
			
			return array('todo', 'list');
		}
	}
}
