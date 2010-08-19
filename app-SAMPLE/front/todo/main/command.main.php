<?php

class TodoMainCommand extends Command
{
	protected $objects = array('TodoItem', 'TodoList');
	
	public function initialize()
	{
		$this->lists = $this->todolistmapper->find('all');
		$this->template->add('lists', $this->lists);
	}
	
	public function index($list_id = 1, $page = 1)
	{
		//$this->template->activateCache();
		
		if ( $this->template->isOutDated() )
		{
			Library::load('Pager');
			
			$this->pager = new Pager($this->todoitemmapper, $page, 20);
			$this->items = $this->todoitemmapper->by('is_done DESC', 'todo')->find(array('list_id' => $list_id));
			
			$this->template->add('items', $this->items);
			$this->template->add('pager', $this->pager);
			$this->template->add('list_id', $list_id);
		}
	}
	
	public function add()
	{
		try {
			$item = new TodoItem();
			$item->set('list_id', Current::$request->get('list_id'));
			$item->set('todo', Current::$request->get('value'));
			$this->todoitemmapper->uptodate($item);
			
			return array('todo', Current::$request->get('list_id'));
		}
		catch (RegistryException $e) {}
		catch (ValidatorException $e)
		{
			$this->template->add('message', $e->getMessage() . ': Input failed.');
		}
	}
	
	public function remove($id = null)
	{
		if ( ! is_numeric($id) )
		{
			$this->template->add('message', 'No id.');
		}
		else
		{
			$item = $this->todoitemmapper->populate(new TodoItem($id));
			
			$this->todoitemmapper->remove($item);
			$this->template->add('message', 'Removed!');
			
			return array('todo', $item->list_id);
		}
	}
	
	public function check($id = null)
	{
		if ( ! is_numeric($id) )
		{
			$this->template->add('message', 'No id.');
		}
		else
		{
			$item = new TodoItem($id);
			$this->todoitemmapper->populate($item);
			
			$item->is_done = ! $item->is_done;
			$this->todoitemmapper->update($item);
			
			$this->template->add('message', 'Updated!');
			
			return array('todo', $item->list_id);
		}
	}
}
