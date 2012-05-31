<?php defined('SYSPATH') OR die('No direct script access.');

abstract class Kohana_Jam_Form_General extends Jam_Form {

	public $template = '<div class="row :name-field :type-field :with-errors">:label<div class="input"><div class="field-wrapper">:field:errors</div>:help</div></div>';

	public function row($type, $name, array $options = array(), array $attributes = array())
	{
		$errors = $this->errors($name);

		$field = call_user_func(array($this, $type), $name, $options, $attributes);

		$help = Arr::get($options, 'help');

		$slots = array(
			':name' => $name,
			':type' => $type,
			':label' => $this->label($name, Arr::get($options, 'label'), $attributes),
			':with-errors' => $errors ? 'with-errors' : '',
			':errors' => $errors,
			':help' => $help ? "<span class=\"help-block\">{$help}</span>" : '',
			':field' => $field,
		);

		return strtr($this->template, $slots);
	}

	public function errors($name)
	{
		$errors = join(', ', Arr::flatten( (array) $this->object()->errors($name)));
		return $errors ? "<span class=\"form-error\">{$errors}</span>" : '';
	}

	public function label($name, $label = NULL, array $attributes = array())
	{
		$attributes = $this->default_attributes($name, $attributes);

		if ($label === NULL)
		{
			$label = ucfirst(Inflector::humanize($name));
		}
		return Form::label($attributes['id'], $label);
	}

	public function input($name, array $options = array(), array $attributes = array())
	{
		$attributes = $this->default_attributes($name, $attributes);

		return Form::input($attributes['name'], $this->object()->$name, $attributes);
	}

	public function hidden($name, array $options = array(), array $attributes = array())
	{
		$attributes = $this->default_attributes($name, $attributes);

		return Form::hidden($attributes['name'], $this->object()->$name, $attributes);
	}

	public function checkbox($name, array $options = array(), array $attributes = array())
	{
		$attributes = $this->default_attributes($name, $attributes);

		return 
			Form::hidden($attributes['name'], 0)
			.Form::checkbox($attributes['name'], 1, $this->object()->$name, $attributes);	
	}

	public function radio($name, array $options = array(), array $attributes = array())
	{
		$value = Arr::get($options, 'value');

		if ( ! $value)
			throw new Kohana_Exception("form widget 'radio' needs a 'value' option");

		if ( ! isset($attributes['id']))
		{
			$attributes['id'] = $this->default_id($name).'_'.URL::title($value);
		}

		$attributes = $this->default_attributes($name, $attributes);

		return Form::radio($attributes['name'], $value, $this->object()->$name == $value, $attributes);	
	}

	public function file($name, array $options = array(), array $attributes = array())
	{
		$attributes = $this->default_attributes($name, $attributes);

		return Form::file($attributes['name'], $attributes);
	}

	public function textarea($name, array $options = array(), array $attributes = array())
	{
		$attributes = $this->default_attributes($name, $attributes);

		return Form::textarea($attributes['name'], $this->object()->$name, $attributes);
	}

	public function select($name, array $options = array(), array $attributes = array())
	{
		$attributes = $this->default_attributes($name, $attributes);

		if ( ! isset($options['choices']))
			throw new Kohana_Exception("Select tag widget requires a 'choices' option");

		$choices = Jam_Form::list_choices($options['choices']);


		if ($blank = Arr::get($options, 'include_blank'))
		{
			Arr::unshift($choices, '', ($blank === TRUE) ? " -- Select -- " : $blank);
		}

		$selected = Jam_Form::list_id($this->object()->$name);

		return Form::select($attributes['name'], $choices, $selected, $attributes);
	}
} // End Kohana_Jam_Field