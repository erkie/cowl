<?php

class Pager extends Plugin
{
	private $mapper;
	private $page;
	private $pages;
	private $per_page;
	private $total;
	
	public function __construct(DataMapper $mapper, $page = 1, $per_page = 10)
	{
		$this->mapper = $mapper;
		$this->page = $page;
		$this->per_page = $per_page;
		
		Current::$plugins->addInstance($this);
	}
	
	public function dbFind(DataMapper $mapper, $args)
	{
		if ( $mapper === $this->mapper )
		{
			$this->page($args);
		}
	}
	
	public function page($count_args)
	{
		$this->total = call_user_func_array(array($this->mapper, 'count'), $count_args);
		$this->pages = ceil($this->total / $this->per_page);
		$this->mapper->limit($this->page * $this->per_page - $this->per_page, $this->per_page);
	}
	
	public function html($url)
	{
		if ( $this->pages <= 1 )
		{
			return '';
		}
		
		$html = '';
		
		// This is so we can replace __PAGE__ with the page
		$page_index = array_search('__PAGE__', $url);
		if ( $page_index === false )
		{
			$page_index = count($url);
		}
		
		if ( $this->page > 1 )
		{
			$url[$page_index] = $this->page - 1;
			$html .= sprintf('<a href="%s">&laquo;</a>', call_user_func_array('Cowl::url', $url));
		}
		else
		{
			$html .= '&laquo;';
		}
		
		for ( $i = 1; $i <= $this->pages; $i++ )
		{
			if ( $this->page == $i )
			{
				$html .= ' ' . $i;
				continue;
			}
			
			$url[$page_index] = $i;
			$html .= sprintf(' <a href="%s">%s</a>', call_user_func_array('Cowl::url', $url), $i);
		}
		
		if ( $this->page < $this->pages )
		{
			$url[$page_index] = $this->page + 1;
			$html .= sprintf(' <a href="%s">&raquo;</a>', call_user_func_array('Cowl::url', $url));
		}
		else
		{
			$html .= ' &raquo;';
		}
					
		return $html;
	}
}
