<?php

/*
	Helpers:
		Social Helpers
	
	Helper functions for social things. Share links etc
*/

/*
	Function:
		share_links
	
	Returns an array of social sites where you can share a URL.
	
	Parameters:
		(int) $limit - The number of links to limit to
*/

function share_links($limit = 5)
{
	$db = array(
		'facebook' => '',
		'twitter' => '',
		'googleplus' => '',
		'reddit' => ''
	);
	
	return array_slice($db, 0, $limit);
}
