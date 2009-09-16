<?php

class TodoMainCommand extends Command
{
	protected $objects = array('TodoItem');
	
	public function index($page = 1)
	{
		Library::load('Pager');
		
		$this->pager = new Pager($this->todoitemmapper, $page);
		$this->items = $this->todoitemmapper->by('id')->find('all');
		
		$this->template->add('items', $this->items);
		$this->template->add('pager', $this->pager);
	}
	
	public function add()
	{
		try {
			$item = new TodoItem();
			$item->set('todo', Current::$request->get('value'));
			$this->todoitemmapper->uptodate($item);
			
			return array('todo');
		}
		catch (RegistryFailException $e) {}
		catch (ValidatorFailException $e)
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
			$this->todoitemmapper->remove(new TodoItem($id));
			$this->template->add('message', 'Removed!');
			
			return array('todo');
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
			
			return array('todo');
		}
	}
}
