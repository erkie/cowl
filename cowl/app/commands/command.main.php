<?php

class MainCommand extends Command
{
	protected $aliases = array('andra' => 'edit');
	
	public function index()
	{
		$result = Current::$db->execute('insert into tests values(null, "%s")', 'Hello');
		printf('<p>Added #%d to the database. %d added.</p>', $result->getID(), $result->getAffected());
		
		$result = Current::$db->execute('select * from tests order by rand() limit 2');
		foreach ( $result->fetch() as $row )
		{
			printf('<p>#%d: %s</p>', $row['id'], $row['value']);
		}
		
		$row = Current::$db->execute('select * from tests where id = 3 limit 1')->row();
		printf('<p>Third is %s</p>', $row['value']);
		
		$result = Current::$db->execute('select * from tests');
		
		echo '<p>Index::index</p>';
	}
	
	public function edit()
	{
		echo 'Editing frontpage';	
	}
}