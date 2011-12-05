<?php

/*
	Helper:
		form
	
	Create awesome and responsive forms without breaking a sweat. Each form is 
	linked to a model. It keeps track of previous input and error messages.
	
	Example:
		(begin code)
		
		<h2>Sign up</h2>
		
		<?php form_for($user, array('users', 'signup')); ?>
			<?php text_field_for('username', 'Username'); ?>
			<?php password_field_for('password', 'Password'); ?>
			<?php submit_button('Submit'); ?>
		<?php form_end(); ?>
		
		(end code)
*/

function form_for($model, $path, $html_attrs = array())
{
	$form = new FormHelper($model, $path, $html_attrs);
	Current::$request->setInfo('active_form', $form);
	
	echo $form->start();
	return $form;
}

function text_field_for($key, $label, $html_attrs = array())
{
	$form = Current::$request->getInfo('active_form');
	
	$html_attrs['type'] = 'text';
	echo $form->input('input', $key, $html_attrs, array('label' => $label));
}

function text_area_for($key, $label, $html_attrs = array())
{
	$form = Current::$request->getInfo('active_form');
	
	echo $form->input('textarea', $key, $html_attrs, array('label' => $label));
}

function password_field_for($key, $label, $html_attrs = array())
{
	$form = Current::$request->getInfo('active_form');
	
	$html_attrs['type'] = 'password';
	echo $form->input('input', $key, $html_attrs, array('label' => $label));
}

function submit_button($value, $html_attrs = array())
{
	$form = Current::$request->getInfo('active_form');
	
	$html_attrs['value'] = $value;
	$html_attrs['type'] = 'submit';
	
	echo $form->field('input', $html_attrs, array('key' => 'submit'));
}

function form_end()
{
	$form = Current::$request->getInfo('active_form');
	echo $form->end();
}

/*
	Class:
		FormHelper
	
	The class that takes care of building all html and stuff for forms.
*/

class FormHelper
{
	public static $with_close_tag = array('textarea');
	
	// Property: FormHelper::$model
	// The model we are modeling a form for.
	private $model;
	
	// Property: FormHelper::$model_name
	// The canonical name of the model, can be used in HTML
	private $model_name;
	
	// Property: FormHelper::$attributes
	// HTML attributes for the form
	private $attributes;
	
	// Property: FormHelper::$path
	// The path that the form maps to
	private $path;
	
	public function __construct($model, $path, $attributes = array(), $name = false)
	{
		$this->model = $model;
		$this->model_name = $name ? $name : strtolower(get_class($model));
		$this->path = $path;
		$this->attributes = $attributes;
	}
	
	/*
		Method:
			FormHelper::start
		
		Return the start of the form, i.e. the start form tag
	*/
	
	public function start()
	{
		$attributes = $this->attributes;
		$attributes['action'] = $this->path;
		
		// We need a method, default is POST
		if ( ! isset($attributes['method']) )
			$attributes['method'] = 'POST';
		
		// Default id is formed as modelname_form
		if ( ! isset($attributes['id']) )
			$attributes['id'] = sprintf('%s_form', $this->model_name);
		
		$attributes = fimplode('%__key__;="%__val__;"', $attributes, ' ');
		return sprintf('<form %s>', $attributes);
	}
	
	/*
		Method:
			element
		
		Get the basic markup for an input field.
	*/
	
	public function element($type, $attrs = array())
	{
		// Wether it is a <foo></foo> or a <bar />
		$with_both = in_array($type, self::$with_close_tag);
		
		// If a value attribute is specified put it inside the element for a $with_bith
		if ( $with_both && isset($attrs['value']) )
		{
			$value = $attrs['value'];
			unset($attrs['value']);
		}
		
		$ret = sprintf('<%s %s', $type, fimplode('%__key__;="%__val__;"', $attrs, ' '));
		
		if ( ! $with_both )
		{
			$ret = $ret . ' />';
			return $ret;
		}
		
		// Close the open tag
		$ret .= '>';
		
		// Add the value
		if ( isset($value) )
		{
			$ret .= $value;
		}
		
		$ret .= sprintf('</%s>', $type);
		return $ret;
	}
	
	public function field($type, $attrs = array(), $options = array())
	{
		// Prefix the id:s with "modelname_"
		$id_prefix = $this->model_name . '_';
		$id = $id_prefix . $options['key'];
		
		$has_errors = isset($options['errors']) && count($options['errors']);
		
		// Default container type is a 'p', but this is optional
		$container_type = isset($options['container_type']) ? $options['container_type'] : 'div';
		
		if ( ! isset($options['class']) )
			$options['class'] = array();
		
		// Create container html
		$classes = array_merge(array('form-field-container'), $options['class']);
		
		// Add error class to bad fields
		if ( $has_errors )
			$classes[] = 'form-error';
		
		$html = sprintf('<%s class="%s" id="%s_container">', $container_type, implode(' ', $classes), $id);
		
		// If a label is specified we format it as a the label text inside a span, inside the label
		if ( isset($options['label']) )
		{
			$html .= sprintf('<label><span>%s:</span> ', $options['label']);
		}
		else
		{
			$html .= '<label>';
		}
		
		// Build input
		$attrs['id'] = $id;
		$html .= $this->element($type, $attrs);
		
		if ( isset($options['errors']) && count($options['errors']) )
		{
			$html .= ' ' . $this->buildErrors($options['errors']);
		}
		
		// Build closing tag
		$html .= sprintf('</label></%s>', $container_type);
		
		return $html;
	}
	
	public function input($type, $key, $html_attrs = array(), $options = array())
	{
		$html_attrs['name'] = $key;
		
		$options['key'] = $key;
		
		if ( $this->model->get($key) )
			$html_attrs['value'] = $this->model->get($key);
		
		// Errors?
		$options['errors'] = $this->model->getValidator()->getErrors($key);
		
		return $this->field($type, $html_attrs, $options);
	}
	
	public function buildErrors($errors)
	{
		$html = '<ul class="form-errors">';
		
		foreach ( $errors as $error )
		{
			$html .= sprintf('<li>%s</li>', $error);
		}
		
		$html .= '</ul>';
		
		return $html;
	}
	
	/*
		Method:
			FormHelper::end
		
		Get the end of the form.
	*/
	
	public function end()
	{
		return sprintf('</form>');
	}
}
