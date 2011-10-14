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
		(string) $url - The url to share
		(string) $title - The title or text that is shared
		(string) $short_title - A short title used where possible
*/

function share_links($limit = 5, $url = '', $title = '', $short_title = '')
{
	$url = urlencode($url);
	$title = urlencode($title);
	$short_title = urlencode($short_title);
	
	$db = array(
		'facebook' => sprintf('https://www.facebook.com/sharer/sharer.php?s=100&p%%5Bsummary%%5D=%s&p%%5Burl%%5D=%s&p%%5Btitle%%5D=%s', $title, $url, $short_title),
		'twitter' => sprintf('http://twitter.com/share?text=%s&url=%s', $title, $url),
		'googleplus' => sprintf('https://m.google.com/app/plus/x/?v=compose&content=%s%%20%s', $url, $title),
		'reddit' => sprintf('http://reddit.com/submit?url=%s&title=%s', $url, $title)
	);
	
	return array_slice($db, 0, $limit);
}
