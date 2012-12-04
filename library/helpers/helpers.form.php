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
	
	$form->output($form->start());
	return $form;
}

function text_field_for($key, $label, $html_attrs = array(), $options = array())
{
	$form = Current::$request->getInfo('active_form');
	
	$html_attrs['type'] = 'text';
	$form->output($form->input('input', $key, $html_attrs, array_merge(array('label' => $label), $options)));
}

function text_area_for($key, $label, $html_attrs = array(), $options = array())
{
	$form = Current::$request->getInfo('active_form');
	
	$form->output($form->input('textarea', $key, $html_attrs, array_merge(array('label' => $label), $options)));
}

function password_field_for($key, $label, $html_attrs = array(), $options = array())
{
	$form = Current::$request->getInfo('active_form');
	
	$html_attrs['type'] = 'password';
	$form->output($form->input('input', $key, $html_attrs, array_merge(array('label' => $label), $options)));
}

function hidden_field_for($key, $html_attrs = array(), $options = array())
{
	$form = Current::$request->getInfo('active_form');
	
	$html_attrs['type'] = 'hidden';
	$form->output($form->input('input', $key, $html_attrs, array_merge(array('no_container' => true), $options)));
}

function upload_field_for($key, $label, $html_attrs = array(), $options = array())
{
	$form = Current::$request->getInfo('active_form');
	
	$html_attrs['type'] = 'file';
	$form->output($form->input('input', $key, $html_attrs, array_merge(array('label' => $label), $options)));
}

function checkbox_for($key, $label, $html_attrs = array(), $options = array())
{
	$form = Current::$request->getInfo('active_form');
	
	$html_attrs['type'] = 'checkbox';
	if ( $form->model->get($key) )
		$html_attrs['checked'] = 'checked';
	if ( ! isset($html_attrs['value']))
		$html_attrs['value'] = '1';
	
	$form->output($form->input('input', $key, $html_attrs, array_merge(array('label' => $label), $options)));
}

function submit_button($value, $html_attrs = array(), $options = array())
{
	$form = Current::$request->getInfo('active_form');
	
	$html_attrs['value'] = $value;
	$html_attrs['type'] = 'submit';
	
	$form->output($form->field('input', $html_attrs, array_merge(array('key' => 'submit'), $options)));
}

function form_end()
{
	$form = Current::$request->getInfo('active_form');
	$form->output($form->end());
	
	echo $form->buildOutput();
}

function form_errors()
{
	$form = Current::$request->getInfo('active_form');
	$form->output(array($form, 'getUnprintedErrors'));
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
	public $model;
	
	// Property: FormHelper::$model_name
	// The canonical name of the model, can be used in HTML
	private $model_name;
	
	// Property: FormHelper::$attributes
	// HTML attributes for the form
	private $attributes;
	
	// Property: FormHelper::$path
	// The path that the form maps to
	private $path;
	
	// Property: FormHelper::$printed_errors
	// Keeps track of which fields have printed their errors, so we can spit out the rest too
	private $printed_errors = array();
	
	// Property: FormHelper::$output
	// An array of strings to be outputted
	public $output = array();
	
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
		$form = $this;
		ob_start(array($this, 'obCallback'));
		
		$attributes = $this->attributes;
		$attributes['action'] = $this->path;
		
		// We need a method, default is POST
		if ( ! isset($attributes['method']) )
			$attributes['method'] = 'POST';
		
		// Default id is formed as modelname_form
		if ( ! isset($attributes['id']) )
			$attributes['id'] = sprintf('%s-form', $this->model_name);
		
		$attributes = fimplode('%__key__;="%__val__;"', $attributes, ' ');
		return sprintf('<form %s>', $attributes);
	}
	
	private function obCallback($a)
	{
		$this->outputNoFlush($a);
		return '';
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
		// Prefix the id:s with "modelname-"
		$id_prefix = $this->model_name . '-';
		$id = $id_prefix . $options['key'];
		
		// No container?
		$no_container = isset($options['no_container']) ? $options['no_container'] : false;
		
		// Content after <input>
		$after = isset($attrs['after']) ? $attrs['after'] : '';
		unset($attrs['after']);
		
		// No whitespace?
		$no_whitespace = isset($attrs['no_whitespace']) && $attrs['no_whitespace'];
		unset($attrs['no_whitespace']);
		
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
		
		$html = '';
		if ( ! $no_container )
		{
			$html .= sprintf('<%s class="%s" id="%s-container">', $container_type, implode(' ', $classes), $id);
		
			// If a label is specified we format it as a the label text inside a span, inside the label
			if ( isset($options['label']) )
			{
				$html .= sprintf('<label><span>%s</span>%s', $options['label'], $no_whitespace ? '' : ' ');
			}
			else
			{
				$html .= '<label>';
			}
		}
		
		// Build input
		$attrs['id'] = $id;
		$html .= $this->element($type, $attrs);
		
		if ( ! $no_container )
		{
			// Build closing tag
			$html .= sprintf('%s</label>', $after);
		
			if ( isset($options['errors']) && count($options['errors']) )
			{
				$html .= ' ' . $this->buildErrors($options['errors']);
				$this->printed_errors[] = $options['key'];
			}
		
			$html .= sprintf('</%s>', $container_type);
		}
		
		return $html;
	}
	
	public function input($type, $key, $html_attrs = array(), $options = array())
	{
		$html_attrs['name'] = $key;
		
		$options['key'] = $key;
		
		if ( ! isset($html_attrs['value']) && ! is_null($this->model->get($key)) )
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
	
	public function getUnprintedErrors()
	{
		$all_errors = $this->model->getValidator()->getErrors();
		$all_keys = array_keys($all_errors);
		
		$unprinted_errors = array_diff($all_keys, $this->printed_errors);
		
		if ( ! count($unprinted_errors) )
			return '';
		
		$errors = array();
		foreach ( $unprinted_errors as $key )
		{
			$errors[$key] = $all_errors[$key];
		}
		
		$errors = $this->model->getValidator()->getErrorMessages($errors);
		
		return $this->buildErrors($errors);
	}
	
	public function output($str)
	{
		ob_flush();
		$this->output[] = $str;
	}
	
	public function outputNoFlush($str)
	{
		$this->output[] = $str;
	}
	
	/*
		Method:
			FormHelper::end
		
		Get the end of the form.
	*/
	
	public function end()
	{
		ob_end_clean();
		return sprintf('</form>');
	}
	
	public function buildOutput()
	{
		foreach ( $this->output as $key => $val )
		{
			if ( is_callable($val) )
				$this->output[$key] = call_user_func_array($val, array());
		}
		return implode('', $this->output);
	}
}
