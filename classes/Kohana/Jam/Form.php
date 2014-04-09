<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Handles building and maintaining HTML Forms
 *
 * @package    Jam
 * @category   Form
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Form {

	/**
	 * Helper method to build a prefix based
	 *
	 * @param string $prefix
	 * @param string $name
	 * @return string
	 */
	public static function generate_prefix($prefix, $name)
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

	/**
	 * Convert Jam_Builder or Jam_Collection to an array of id => name
	 *
	 * @param mixed $choices
	 * @return  array
	 */
	public static function list_choices($choices)
	{
		if ($choices instanceof Jam_Query_Builder_Select OR $choices instanceof Jam_Array_Model)
		{
			$choices = $choices->as_array(':primary_key', ':name_key');
		}

		return $choices;
	}

	/**
	 * Get the id or list of ids of an object (Jam_Model / Jam_Colleciton respectively)
	 *
	 * @param int|array $id
	 * @param bool $force_single if a value is an array get the first value
	 */
	public static function list_id($id, $force_single = FALSE)
	{
		if ($id instanceof Jam_Model)
		{
			$id = $id->id();
		}
		elseif ($id instanceof Jam_Query_Builder_Select OR $id instanceof Jam_Array_Model)
		{
			$id = $id->ids();
		}

		if ($force_single AND is_array($id))
		{
			$id = reset($id);
		}

		return $id;
	}

	/**
	 * Add a class to the 'class' attribute, without removing existing value
	 *
	 * @param array  $attributes
	 * @param array $class
	 */
	public static function add_class(array $attributes, $class)
	{
		$attributes['class'] = (isset($attributes['class']) ? $attributes['class'].' ' : '').$class;
		return $attributes;
	}

	public static function common_params($collection, array $params = array())
	{
		$collection = ($collection instanceof Jam_Query_Builder_Collection OR $collection instanceof Jam_Array_Model) ? $collection->as_array() : $collection;

		$common = array();
		foreach ($params as $name => $param)
		{
			$attribute_name = is_numeric($name) ? $param : $name;
			$param_collection = array_map(function($item) use ($attribute_name) { return $item->$attribute_name; }, $collection);

			if (is_numeric($name))
			{
				$common[$param] = array_reduce($param_collection, function($result, $item){
					return Jam_Form::list_id($result) !== Jam_Form::list_id($item) ? NULL : $item;
				}, reset($param_collection));
			}
			else
			{
				$common[$name] = Jam_Form::common_params($param_collection, $param);
			}
		}

		return $common;
	}

	/**
	 * The "prefix" of the form - this is used to implement nesting with html forms
	 *
	 * @var string
	 */
	protected $_prefix = '%s';

	/**
	 * The current object the form is bound to
	 *
	 * @var Jam_Model
	 */
	protected $_object;

	/**
	 * This flag determines if we want to have html5 validation of the form
	 * @var boolean
	 */
	protected $_validation = TRUE;

	/**
	 * The meta of the Jam_Object the form is bound to
	 *
	 * @var Jam_Meta
	 */
	protected $_meta;

	function __construct(Jam_Validated $model)
	{
		$this->_object = $model;
		$this->_meta = Jam::meta($model);
	}

	/**
	 * Getter / setter of validation flag
	 * @param boolean $validation
	 */
	public function validation($validation = NULL)
	{
		if ($validation !== NULL)
		{
			$this->_validation = (bool) $validation;
			return $this;
		}
		return $this->_validation;
	}

	/**
	 * Getter / setter of the prefix
	 *
	 * @param string $prefix
	 */
	public function prefix($prefix = NULL)
	{
		if ($prefix !== NULL)
		{
			$this->_prefix = $prefix;
			return $this;
		}

		return $this->_prefix;
	}

	/**
	 * @return Jam_Validated this form is bound to
	 */
	public function object()
	{
		return $this->_object;
	}

	/**
	 * @return Jam_Meta of the model this form is bound to
	 */
	public function meta()
	{
		return $this->_meta;
	}

	/**
	 * Create a nested form for a child association of the model, assigning the correct prefix
	 *
	 * @param string $name  of the association
	 * @param int $index an index id of a collection (if the association if a colleciton)
	 * @return  Jam_Form
	 */
	public function fields_for($name, $index = NULL)
	{
		$object = $this->object()->$name;

		if ($index !== NULL)
		{
			if (is_numeric($index) AND $object[$index])
			{
				$object = $object[$index];
			}
			else
			{
				$object = $object->build();
			}
		}
		elseif ( ! $object)
		{
			$object = $this->object()->build($name);
		}


		$new_prefix = Jam_Form::generate_prefix($this->prefix(), $name, $index);

		return Jam::form($object, get_class($this))->prefix($new_prefix);
	}

	/**
	 * Get the default name for a field of this form
	 *
	 * @param string $name
	 * @return string
	 */
	public function default_name($name)
	{
		return sprintf($this->prefix(), $name);
	}

	/**
	 * Get the default html attribute id for a field of this form
	 *
	 * @param string $name
	 * @return string
	 */
	public function default_id($name)
	{
		return str_replace(array(']', '['), array('', '_'), $this->default_name($name));
	}

	/**
	 * Get the default attributes (name and id) for a field of this form
	 *
	 * @param string $name
	 * @param array  $overrides
	 * @return array
	 */
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

		if ($this->validation())
		{
			foreach ($this->object()->meta()->validators() as $validator)
			{
				if ( ! $validator->condition)
				{
					$overrides = Arr::merge($validator->html5_validation($this->object(), $name), $overrides);
				}
			}
		}

		return $overrides;
	}
}
