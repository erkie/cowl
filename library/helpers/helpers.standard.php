<?php

/*
	Helpers:
		View Helpers
	
	Standard view helpers.
*/

/*
	Function:
		<url>
	
	Print out a URL without you having to worry about the base path. See documentation for <Cowl::url> for more information.
	
	Examples:
		url('forum', 'view', $id);
		> /forum/view/10
	
	Parameters:
		mixed $segment1 - One segment of the url
		mixed $segmentN - ... 
*/

function url()
{
	$args = func_get_args();
	echo COWL_BASE . implode('/', $args);
}
	
/*
	Function:
		<css>
	
	Print out <link />s to all CSS files added to <Current::$request> info-array of the key "css".
*/

function css()
{
	$stylesheets = Current::$request->getInfo('css');
	
	if ( is_array($stylesheets) )
	{
		foreach ( $stylesheets as $stylesheet )
		{
			printf('<link rel="stylesheet" type="text/css" media="all" href="%s" />', $stylesheet);
		}
	}
}

/*
	Function:
		<js>
	
	Print out <script></scripts> for all files atted to <Current::$request> info-array of the key "js".
*/

function js()
{
	$scripts = Current::$request->getInfo('js');
	
	if ( is_array($scripts) )
	{
		foreach ( $scripts as $script )
		{
			printf('<script type="text/javascript" src="%s"></script>', $script);
		}
	}
}

/*
	Function:
		<only_if>
	
	Print out $if_true only if $test is true, else print out $if_false.
	
	Parameters:
		bool $test - Test condition
		mixed $if_true - Message to print out if $test was true
		mixed $if_false - Message to print out if $test was false
*/

function only_if($test, $if_true, $if_false = '')
{
	echo ($test) ? $if_true : $if_false;
}

/*
	Function:
		<to_options>
	
	Create HTML options from a <DomainCollection> using specified keys for value="" and text of the <option></option>.
	
	Examples:
		// Let's say we have the class PostCategory, which has the members id, name
		// $categories holds the <DomainCollection>-instance of the categories
		
		<select>
			<option value="">Jump to category</select>
			<?php to_options($categories, 'id', 'name');
		</select>
	
	Parameters:
		DomainCollection $collection - The <DomainCollection> we wish to convert into <option>s
		string $key_value - The key in the <DomainObject>'s members that will be used as value (<option _value="foo"_>)
		string $key_text - The key in the <DomainObject>'s members that will be used as text (<option>_text_</option>)
		boolean $selected - Which value should be selected, if any
*/

function to_options(DomainCollection $collection, $key_value, $key_text, $selected = null)
{
	$html = '';
	
	foreach ( $collection as $object )
	{
		$value = $object->get($key_value);
		$is_selected = ($value == $selected) ? 'selected="selected" ' : '';
		
		$html .= sprintf('<option %svalue="%s">%s</option>', $is_selected, $value, $object->get($key_text));
	}
	echo $html;
}
