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

	/**
	 * Convert Jam_Builder or Jam_Collection to an array of id => name
	 * 
	 * @param mixed $choices 
	 * @return  array 
	 */
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

	/**
	 * Get the id or list of ids of an object (Jam_Model / Jam_Colleciton respectively)
	 * 
	 * @param int|array $id 
	 */
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
			$object = $object[$index];
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

		return $overrides;
	}


} // End Kohana_Jam_Form