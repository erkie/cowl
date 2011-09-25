<?php

/*
	Class:
		<Pager>
	
	Pages results using <DataMapper>s. Extends <Plugin> so that the user won't have to call <Pager::$page>, keeping the API simple.
	
	Examples:
		$pager = new Pager($mapper, 1, 10);
		$results = $mapper->by('id')->find('all');
		
		// Results now contain only 10 results beginning at 1
*/

class Pager extends Plugin
{
	// Property: <Pager::$mapper>
	// The instance of <DataMapper> to paginate
	private $mapper;
	
	// Property: <Pager::$page>
	// The current page.
	private $page;
	
	// Property: <Pager::$pages>
	// The number of pages. Calculated with ceil(total results / how many items per page)
	private $pages;
	
	// Property: <Pager::$per_page>
	// How many results per page.
	private $per_page;
	
	// Property: <Pager::$total>
	// How many results lay in the datebase.
	private $total;
	
	/*
		Constructor:
			<Pager::__construct>
		
		Initialize all values and add the instance to <Current::$plugins>.
		
		Parameters:
			DataMapper $mapper - The mapper to paginate.
			integer $page - The current page.
			integer $per_page - Amount of results per page.
	*/
	
	public function __construct(DataMapper $mapper, $page = 1, $per_page = 10)
	{
		$this->mapper = $mapper;
		$this->page = $page;
		$this->per_page = $per_page;
		
		Current::$plugins->addInstance($this);
	}
	
	/*
		Method:
			<Pager::dbFind>
		
		Called as a hook to <DataMapper::find>. If the $mapper passed is the same as the <Pager::$mapper>, page it using <Pager::page>. After completion the instance of $this will be removed from <Current::$plugins>.
		
		Parameters:
			DataMapper $mapper - The mapper called upon.
			array $args - The args passed to <DataMapper::find>.
	*/
	
	public function dbFind(DataMapper $mapper, $args)
	{
		if ( $mapper === $this->mapper )
		{
			$this->page($args);
			Current::$plugins->removeInstance($this);
		}
	}
	
	/*
		Method:
			<Pager::page>
		
		Do the paging. Use <DataMapper::count> to get total results. Calculate how many pages there are. Call <DataMapper::limit> to limit results appropriately.
		
		Parameters:
			array $count_args - The arguments for the count call. You should not have to worry about this, because it is called automatically by <Pager::dbFind>.
	*/
	
	public function page($count_args)
	{
		$this->total = call_user_func_array(array($this->mapper, 'count'), $count_args);
		$this->pages = ceil($this->total / $this->per_page);
		$this->mapper->limit($this->page * $this->per_page - $this->per_page, $this->per_page);
	}
	
	/*
		Method:
			<Pager::hasPrev>
		
		Returns:
			Wether there are previous entries from the current page
	*/
	
	public function hasPrev()
	{
		return $this->page > 1;
	}
	
	/*
		Method:
			<Pager::hasNext>
		
		Returns:
			Wether there are more pages after the current page
	*/
	
	public function hasNext()
	{
		return $this->page < $this->pages;
	}
	
	/*
		Method:
			<Pager::nextPage>
		
		Returns:
			The next page's index.
	*/
	
	public function nextPage()
	{
		return $this->page + 1;
	}
	
	/*
		Method:
			<Pager::prevPage>
		
		Returns:
			The previous page's index.
	*/
	
	public function prevPage()
	{
		return $this->page - 1;
	}
	
	/*
		Method:
			<Pager::html>
		
		Create and return the page navigation HTML.
		
		Parameters:
			array $url - The URL used for the links. Add __PAGE__ as a placeholder for where the index will be added.
		
		Returns:
			The HTML, nothing if there is no need for navigation.
	*/
	
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
			$html .= sprintf('<a href="%s">&laquo;</a>', Cowl::url($url));
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
			$html .= sprintf(' <a href="%s">%s</a>', Cowl::url($url), $i);
		}
		
		if ( $this->page < $this->pages )
		{
			$url[$page_index] = $this->page + 1;
			$html .= sprintf(' <a href="%s">&raquo;</a>', Cowl::url($url));
		}
		else
		{
			$html .= ' &raquo;';
		}
					
		return $html;
	}
}
