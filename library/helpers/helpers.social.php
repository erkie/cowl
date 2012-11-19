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

function share_links($limit = 4, $url = '', $title = '', $short_title = '', $image = '')
{
	$db = array('facebook', 'twitter', 'googleplus', 'reddit');
	
	$ret = array();
	foreach ( $db as $key )
	{
		$ret[$key] = share_url($key, $url, $title, $short_title, $image);
	}
	
	return array_slice($ret, 0, $limit);
}

/*
	Function:
		share_url
	
	Get a url for a single social sharing site.
	
	Available sites:
		facebook - Facebook.com
		twitter - Twitter.com
		googleplus - Google+
		reddit - Reddit.com
	
	Parameters:
		$name - The key of the site. Check "Available sites"
		$url - The url to share
		$title - The title or text that is shared
		$short_title - A short title used where possible
*/

function share_url($name, $url, $title, $short_title, $image = '')
{
	$url = urlencode($url);
	$title = urlencode($title);
	$short_title = urlencode($short_title);
	
	$db = array(
		'facebook' => sprintf('https://www.facebook.com/sharer/sharer.php?s=100&p%%5Bsummary%%5D=%s&p%%5Burl%%5D=%s&p%%5Btitle%%5D=%s', $title, $url, $short_title),
		'twitter' => sprintf('http://twitter.com/share?text=%s&url=%s', $title, $url),
		'googleplus' => sprintf('https://plus.google.com/share?url=%s&h1=%s', $url, $title),
		'reddit' => sprintf('http://reddit.com/submit?url=%s&title=%s', $url, $title)
	);
	
	$share_url = $db[$name];
	
	if ( strlen($image) && $name === "facebook" )
	{
		$share_url .= sprintf('&p[images][0]=%s', $image);
	}
	
	return $share_url;
}