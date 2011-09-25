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

function share_links($limit = 5, $url = '', $title = '')
{
	$url = urlencode($url);
	$title = urlencode($title);
	
	$db = array(
		'facebook' => sprintf('http://www.facebook.com/sharer.php?u=%s&t=%s', $url, $title),
		'twitter' => sprintf('http://twitter.com/share?text=%s&url=%s', $title, $url),
		'googleplus' => sprintf('https://m.google.com/app/plus/x/?v=compose&content=%s%%20%s', $url, $title),
		'reddit' => sprintf('http://reddit.com/submit?url=%s&title=%s', $url, $title)
	);
	
	return array_slice($db, 0, $limit);
}
