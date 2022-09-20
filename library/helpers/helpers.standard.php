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
	
	// We check that the passed $str is instance of HTMLSafeString because of the printf aspect of this function
	if ( $str instanceof HTMLSafeString )
		echo $html;
	else
		echo escape_html($html);
}

function escape_html($html)
{
	if ( $html instanceof HTMLSafeString )
		return $html;
	if (!$html) {
		return "";
	}
	return htmlentities($html, ENT_COMPAT, 'UTF-8');
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
	p(Cowl::url($args));
}

/*
	Function:
		make_url
	
	Convenience function to create a URL string, like <url> does, without outputting it.
	
	See: <url>
*/

function make_url()
{
	$args = func_get_args();
	return Cowl::url($args);
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
		$tag = Current::$config->get('release_tag');
		
		if ( $tag === 'dev' )
			$tag .= time();
		
		foreach ( $stylesheets as $stylesheet )
		{
			printf('<link rel="stylesheet" type="text/css" media="all" href="%s?%s" />' . PHP_EOL, $stylesheet, $tag);
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
	$request = Current::$request->getInfo('request');
	
	printf('<script type="text/javascript">');
	printf('	var COWL_BASE = "%s";', COWL_BASE);
	printf('	var COWL_CURRENT = [%s];', fimplode('"%__val__;"', array_map('addslashes', $request->original_request), ', '));
	printf('</script>');
	
	$scripts = Current::$request->getInfo('js');
	
	if ( is_array($scripts) )
	{
		$tag = Current::$config->get('release_tag');
		
		if ( $tag === 'dev' )
			$tag .= time();
		
		foreach ( $scripts as $script )
		{
			printf('<script type="text/javascript" src="%s?%s"></script>', Cowl::url($script), $tag);
		}
	}
	
	if ( $fire = Current::$request->getInfo('js_fire') )
	{
		$request = Current::$request->getInfo('request');
		printf('<script type="text/javascript">Cowl.fire("%s", "%s")</script>', strtolower(implode('.', $request->pieces)), $request->method);
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
		p_only_if
	
	Same as only_if, but use safe print function.
	
	Parameters:
		See: <only_if>
*/

function p_only_if($test, $if_true, $if_false = '')
{
	p(($test) ? $if_true : $if_false);
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
		
		$html .= sprintf('<option %svalue="%s">%s</option>', $is_selected, $value, escape_html($object->get($key_text)));
	}
	echo $html;
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

/*
	Private class:
	 	HTMLBuilder
	
	Used for building HTML
*/

class HTMLBuilder
{
	public $tag;
	public $self_close = false;
	public $value = '';
	
	private $attributes = array();
	
	public function __construct($tag, $self_close = false)
	{
		$this->tag = $tag;
		$this->self_close = $self_close;
	}
	
	public function setValue($val)
	{
		$this->value = $val;
	}
	
	public function addAttributes($attrs)
	{
		foreach ( $attrs as $key => $val )
		{
			$this->addAttribute($key, $val);
		}
	}
	
	public function addAttribute($name, $value)
	{
		$this->attributes[$name] = escape_html($value);
	}
	
	public function build()
	{
		$html = sprintf('<%s %s', $this->tag, $this->buildAttributes());
		
		if ( $this->self_close )
			$html .= ' />';
		else
			$html .= sprintf('>%s</%s>', escape_html($this->value), $this->tag);
		
		return $html;
	}
	
	private function buildAttributes()
	{
		return fimplode('%__key__;="%__val__;"', $this->attributes, ' ');
	}
}

/*
	Function:
		link_to
	
	Output an HTML link.
	
	Parameters:
		$text - Text to be linkified
		$url - The URL to link to
		(optional) $html_options - Array of HTML options
*/

function link_to($text, $url, $html_options = array())
{
	$builder = new HTMLBuilder('a');
	$builder->addAttribute('href', $url);
	$builder->addAttributes($html_options);
	$builder->setValue($text);
	echo $builder->build();
}

/*
	Function:
		safe
	
	Indicate that a string is safe to print out without escaping. This can be used with <p>, <link_to>,
	and any other <HTMLBuilder> helper method.
*/

function safe($text)
{
	return new HTMLSafeString($text);
}

class HTMLSafeString
{
	public $text = '';
	
	public function __construct($text)
	{
		$this->text = $text;
	}
	
	public function __toString()
	{
		return $this->text;
	}
}
