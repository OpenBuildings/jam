<?php defined('SYSPATH') OR die('No direct script access.');

abstract class Kohana_Jam_Form {

	/**
	 * Helper method to build a prefix based
	 * @param string $prefix 
	 * @param string $name 
	 * @return string
	 */
	static public function generate_prefix($prefix, $name)
	{
		$additional = array_slice(func_get_args(), 2);
		foreach ($additional as $additional_name)
		{
			if ($additional_name !== NULL)
				$name .= "[$additional_name]";
		}

		if (strpos($prefix,'[') !== FALSE)
		{
			return str_replace('[%s]','',$prefix).preg_replace('/^([^\[]+)(.*)$/', "[\$1]\$2[%s]", $name);
		}
		else
		{
			return preg_replace('/^([^\[]+)(.*)$/', "{$name}[\$1]\$2", $prefix);
		}
	}

	static public function list_choices($choices)
	{
		if ($choices instanceof Jam_Builder)
		{
			$choices = $choices->select_all()->as_array(':primary_key', ':name_key');
		}
		elseif ($choices instanceof Jam_Collection)
		{
			$choices = $choices->as_array(':primary_key', ':name_key');
		}
		
		return $choices;
	}

	static public function list_id($id)
	{
		if ($id instanceof Jam_Model)
		{
			$id = $id->id();
		}
		elseif ($id instanceof Jam_Collection) 
		{
			$id = $id->ids();
		}
		
		return $id;
	}


	protected $_prefix = '%s';
	protected $_object;
	protected $_meta;

	function __construct(Jam_Model $model)
	{
		$this->_object = $model;
		$this->_meta = Jam::meta($model);
	}


	public function prefix($prefix = NULL)
	{
		if ($prefix !== NULL)
		{
			$this->_prefix = $prefix;
			return $this;
		}

		return $this->_prefix;
	}

	public function object()
	{
		return $this->_object;
	}

	public function meta()
	{
		return $this->_meta;
	}

	public function fields_for($name, $index = NULL)
	{
		$object = $this->object()->$name;

		if ($index !== NULL)
		{
			$object = $object[$index];
		}

		$new_prefix = Jam_Form::generate_prefix($this->prefix(), $name, $index);

		return Jam::form($object, get_class($this))->prefix($new_prefix);
	}

	public function default_name($name)
	{
		return sprintf($this->prefix(), $name);
	}

	public function default_id($name)
	{
		return str_replace(array(']', '['), array('', '_'), $this->default_name($name));
	}

	public function default_attributes($name, array $overrides = array())
	{
		if ( ! isset($overrides['id']))
		{
			$overrides['id'] = $this->default_id($name);
		}

		if ( ! isset($overrides['name']))
		{
			$overrides['name'] = $this->default_name($name);
		}

		return $overrides;
	}


} // End Kohana_Jam_Field