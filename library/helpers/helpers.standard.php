<?php

/*
	Helpers:
		View Helpers
	
	Standard view helpers.
*/

/*
	Function:
		p
	
	Print out something HTML safely. Used as printf, but the printed string is HTML safe.
	
	Parameters:
		$str - The string to print out
		$argN - Format data for <vsprintf>
*/

function p($str = '')
{
	$args = func_get_args();
	$args = array_slice($args, 1);
	
	if ( ! count($args) )
		$html = $str;
	else
		$html = vsprintf($str, $args);
	
	echo htmlentities($html);
}

/*
	Function:
		url
	
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
		css
	
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
		js
	
	Print out <script></scripts> for all files added to <Current::$request>-info-array of the key "js".
*/

function js()
{
	$argv = Current::$request->getInfo('argv');
	
	printf('<script type="text/javascript">');
	printf('	var COWL_BASE = "%s";', COWL_BASE);
	printf('	var COWL_CURRENT = [%s];', fimplode('"%__val__;"', array_map('addslashes', $argv['original_request']), ', '));
	printf('</script>');
	
	$scripts = Current::$request->getInfo('js');
	
	if ( is_array($scripts) )
	{
		foreach ( $scripts as $script )
		{
			printf('<script type="text/javascript" src="%s"></script>', Cowl::url($script));
		}
	}
	
	if ( $fire = Current::$request->getInfo('js_fire') )
	{
		$argv = Current::$request->getInfo('argv');
		printf('<script type="text/javascript">Cowl.fire("%s", "%s")</script>', strtolower(implode('.', $argv['pieces'])), $argv['method']);
	}
}

/*
	Function:
		only_if
	
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
		to_options
	
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


/*
	Function:
		fimplode
	
	Formatted implode. Implode an array of associative of arrays, replacing instances
	of %key; with the value of the key. If the passed array is not associative, the variables
	%__key__; and %__val__; are available.
	
	Examples:
		$arr = array(
			array(
				'id' => 1,
				'name' => 'Foo'
			),
			
			array(
				'id' => 2,
				'name' => 'Bar'
			)
		);
		
		echo fimplode('<a href="?id=%id;">%name;</a>', $arr, ', ');
		// <a href="?id=1">Foo</a>, <a href="?id=2">Bar</a>
	
	Parameters:
		string $format_string - The string to use as template
		array $arr - The array to implode
		string $format_end - A string that shouldn't be added to the last element.
					Subject to the same formatting rules as $format_string
	
	Returns:
		The formatted string.
*/

function fimplode($format_string, $arr, $format_end = '')
{
	if ( ! is_string($format_string) )
	{
		user_error('Format string must be string', E_USER_WARNING);
		die;
	}
	
	if ( ! is_array($arr) )
	{
		user_error('Array must be array', E_USER_WARNING);
		die;
	}
	
	if ( ! is_string($format_end) )
	{
		user_error('Format end must be string, leave empty if you don\'t intend to use it', E_USER_WARNING);
		die;
	}
	
	$len = count($arr);
	$index = 0;
	$string = '';
	foreach ( $arr as $key => $element )
	{
		if ( is_array($element) )
		{
			$keys = array_keys($element);
			$values = array_values($element);
			
			foreach ( $values as $key => $value )
			{
				if ( ! is_string($value) )
				{
					unset($values[$key], $keys[$key]);
				}
			}
		}
		else
		{
			$keys = array('__key__', '__val__');
			$values = array($key, $element);
		}
		foreach ( $keys as $k => $v )
		{
			$keys[$k] = '%' . $v . ';';
		}
		$string .= str_replace($keys, $values, $format_string);
		if ( $index < $len-1 )
		{
			$string .= str_replace($keys, $values, $format_end);
		}
		$index++;
	}
	return $string;
}

/*
	Function:
		flash
	
	Output message defined by previous pageview. See
*/

function flash()
{
	$classes = array(
		'flash' => 'flash',
		'flashError' => 'error',
		'flashSuccess' => 'success',
		'flashNotice' => 'notice'
	);
	
	foreach ( $classes as $key => $class )
	{
		$messages = Current::$request->getInfo($key);
	
		if ( ! is_array($messages) || ! count($messages) )
			continue;
	
		printf('<div class="cowl-flash-container cowl-flash-%s"><ul id="flash-%s" class="cowl-flash">', $class, $class);
		foreach ( $messages as $message )
		{
			printf('<li>%s</li>', $message);
		}
		printf('</ul></div>');
		
		// Remove the message from the flash as it has now been displayed
		Current::$request->setInfo($key, false);
	}
}
