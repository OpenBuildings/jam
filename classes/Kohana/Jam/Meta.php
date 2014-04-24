<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Jam Meta
 *
 * Jam_Meta objects act as a registry of information about a particular model.
 *
 * @package    Jam
 * @category   Meta
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Meta {

	/**
	 * @var  boolean  If this is FALSE, properties can still be set on the meta object
	 */
	protected $_initialized = FALSE;

	/**
	 * @var  string  The model this meta object belongs to
	 */
	protected $_model = NULL;

	/**
	 * @var  string  The database key to use for connection
	 */
	protected $_db;

	/**
	 * @var  string  The table this model represents, defaults to the model name pluralized
	 */
	protected $_table = '';

	/**
	 * @var  string  The primary key, defaults to the first Field_Primary found.
	 *               This can be referenced in query building as :primary_key
	 */
	protected $_primary_key = '';

	/**
	 * @var  string  The title key. This can be referenced in query building as :name_key
	 */
	protected $_name_key = 'name';

	/**
	 * @var  string  The foreign key for use in other tables. This can be referenced in query building as :foreign_key
	 */
	protected $_foreign_key = '';

	/**
	 * @var string The method needed to get the item
	 */
	protected $_unique_key = '';

	/**
	 * @var  string  The polymorphic key for the model tree.
	 */
	protected $_polymorphic_key = NULL;

	/**
	 * @var  array  An array of this model's children
	 */
	protected $_children = array();

	/**
	 * @var  array  An array of ordering options for SELECTs
	 */
	protected $_sorting = array();

	/**
	 * @var  array  A map to the models's fields and how to process each column.
	 */
	protected $_fields = array();

	/**
	 * @var  array  A map to the models's associations and how to process each column.
	 */
	protected $_associations = array();


	/**
	 * The message filename used for validation errors.
	 * Defaults to Jam_Meta::$_model
	 * @var string
	 */
	protected $_errors_filename = NULL;

	/**
	 * @var  array  Default data for each field
	 */
	protected $_defaults = array();

	/**
	 * @var Jam_Event Events attached to this model
	 */
	protected $_events = array();

	/**
	 * @var  array  Behaviors attached to this model
	 */
	protected $_behaviors = array();

	/**
	 * @var  string  The parent model of this model
	 */
	protected $_parent = NULL;

	protected $_validators = array();

	protected $_with_options;

	protected $_collection = NULL;

	/**
	 * The most basic initialization possible
	 * @param string $model Model name
	 */
	public function __construct($model)
	{
		$this->_model = $model;

		// Set up event system
		$this->_events = new Jam_Event($model);
	}

	/**
	 * This is called after initialization to
	 * finalize any changes to the meta object.
	 *
	 * @param  string  $model
	 * @return
	 */
	public function finalize($model)
	{
		if ($this->_initialized)
			return;

		// Set the name of a possible behavior class
		$behavior_class = Jam::behavior_prefix().Jam::capitalize_class_name($model);

		// See if we have a special behavior class to use
		if (class_exists($behavior_class))
		{
			// Load behavior
			$behavior = new $behavior_class;

			if ( ! in_array($behavior, $this->_behaviors))
			{
				// Add to behaviors
				$this->_behaviors[] = $behavior;
			}
		}

		foreach ($this->_behaviors as $name => $behavior)
		{
			if ( ! $behavior instanceof Jam_Behavior)
				throw new Kohana_Exception('Behavior at index [ :key ] is not an instance of Jam_Behavior, :type found.', array(
					':type' => is_object($behavior) ? ('instance of '.get_class($behavior)) : gettype($behavior),
					':key'   => $name,
				));

			// Initialize behavior
			$behavior->initialize($this, $name);
		}

		// Allow modification of this meta object by the behaviors
		$this->_events->trigger('meta.before_finalize', $this);

		// Ensure certain fields are not overridden
		$this->_model       = $model;
		$this->_defaults    = array();

		if ( ! $this->_errors_filename)
		{
			// Default errors filename to the model's name
			$this->_errors_filename = 'validators/'.$this->_model;
		}

		// Table should be a sensible default
		if (empty($this->_table))
		{
			$this->_table = Inflector::plural($model);
		}

		// Can we set a sensible foreign key?
		if (empty($this->_foreign_key))
		{
			$this->_foreign_key = $model.'_id';
		}

		if (empty($this->_unique_key) AND method_exists(Jam::class_name($this->_model), 'unique_key'))
		{
			$this->_unique_key = Jam::class_name($this->_model).'::unique_key';
		}

		foreach ($this->_fields as $column => $field)
		{
			// Ensure a default primary key is set
			if ($field instanceof Jam_Field AND $field->primary AND empty($this->_primary_key))
			{
				$this->_primary_key = $column;
			}

			// Ensure a default plymorphic key is set
			if ($field instanceof Jam_Field_Polymorphic AND empty($this->_polymorphic_key))
			{
				$this->_polymorphic_key = $column;
			}
		}

		foreach ($this->_associations as $column => & $association)
		{
			$association->initialize($this, $column);
		}

		foreach ($this->_fields as $column => & $field)
		{
			$field->initialize($this, $column);
			$this->_defaults[$column] = $field->default;
		}

		if ( ! $this->_collection AND ($class = Jam::collection_prefix().Jam::capitalize_class_name($this->_model)) AND class_exists($class))
		{
			$this->_collection = $class;
		}

		// Meta object is initialized and no longer writable
		$this->_initialized = TRUE;

		// Final meta callback
		$this->_events->trigger('meta.after_finalize', $this);
	}

	/**
	 * Returns a string representation of the meta object.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return (string) get_class($this).': '.$this->_model;
	}

	/**
	 * Returns whether or not the meta object has finished initialization
	 *
	 * @return  boolean
	 */
	public function initialized()
	{
		return $this->_initialized;
	}

	public function collection($value = NULL)
	{
		if (func_num_args() !== 0)
		{
			return $this->set('collection', $value);
		}
		return $this->_collection;
	}

	public function validator($field, $options)
	{
		$fields = func_get_args();
		$options = (array) array_pop($fields);

		if ($this->_with_options)
		{
			$options = Arr::merge($options, $this->_with_options);
		}

		$this->_validators[] = new Jam_Validator($fields, $options);

		return $this;
	}

	public function with_options($options)
	{
		$this->_with_options = $options;
		return $this;
	}

	public function end()
	{
		$this->_with_options = NULL;
		return $this;
	}

	public function validators()
	{
		return $this->_validators;
	}

	public function execute_validators(Jam_Validated $model, $force = FALSE)
	{
		foreach ($this->_validators as $validator)
		{
			$validator->validate_model($model, $force);
		}

		$model->validate();

		return $this;
	}

	/**
	 * Allows setting a variable only when not initialized.
	 *
	 * @param   string      $key
	 * @param   mixed       $value
	 * @return  Jam_Meta
	 */
	protected function set($key, $value)
	{
		if ($this->_initialized)
		{
			throw new Kohana_Exception(':class already initialized, cannot set :key to :value', array(
				':class' => Jam::class_name($this->_model),
				':key'   => $key,
				':value' => $value,
			));
		}

		// Set key's value
		$this->{'_'.$key} = $value;

		return $this;
	}

	/**
	 * Allows appending an array to a variable only when not initialized.
	 *
	 * @param   string      $key
	 * @param   mixed       $value
	 * @return  Jam_Meta
	 */
	protected function set_append($key, $value)
	{
		if ($this->_initialized)
		{
			// Throw exception
			throw new Kohana_Exception(':class already initialized, cannot append to :key', array(
				':class' => Jam::class_name($this->_model),
				':key'   => $key,
			));
		}

		if (is_array($value))
		{
			// Set key's value
			$this->{'_'.$key} += $value;
		}

		return $this;
	}

	/**
	 * Gets or sets the db group
	 *
	 * @param   string  $value
	 * @return  Jam_Meta|string
	 */
	public function db($value = NULL)
	{
		if (func_num_args() !== 0)
		{
			return $this->set('db', $value);
		}

		return $this->_db ? $this->_db : Database::$default;
	}

	/**
	 * Returns the model name this object is attached to
	 *
	 * @return  string
	 */
	public function model()
	{
		return $this->_model;
	}

	/**
	 * Gets or sets the table
	 *
	 * @param   string  $value
	 * @return  Jam_Meta|string
	 */
	public function table($value = NULL)
	{
		if (func_num_args() !== 0)
		{
			return $this->set('table', $value);
		}

		return $this->_table;
	}

	/**
	 * Getter / setter for individual fields.
	 *
	 * @param   string       $name     name of the field
	 * @param   mixed        $field    the field alias or object
	 * @return  Jam_Field|Jam_Meta|null
	 */
	public function field($name, $field = NULL, $prepend = FALSE)
	{
		if ($field === NULL)
		{
			// Get the association
			return Arr::get($this->_fields, $name);
		}

		if ($this->_initialized)
		{
			// Cannot set after initialization
			throw new Kohana_Exception(':class already initialized, cannot set :field', array(
				':class' => Jam::class_name($this->_model),
				':field'   => $name,
			));
		}

		// Set the field
		if ($prepend)
		{
			$this->_fields = array($name => $field) + $this->_fields;
		}
		else
		{
			$this->_fields[$name] = $field;
		}

		// Return Jam_Meta
		return $this;
	}

	/**
	 * The same as field method, but throws an exception if association does not exist
	 *
	 * @param   string       $name     name of the field
	 * @param   mixed        $field    the field alias or object
	 * @return  Jam_Field|Jam_Meta|null
	 */
	public function field_insist($name, $field = NULL)
	{
		if ( ! isset($this->_fields[$name]))
			throw new Kohana_Exception('The field :name for this model :model does not exist', array(':name' => $name, ':model' => $this->_model));

		return $this->field($name, $field);
	}

	public function attribute($name)
	{
		return Arr::get($this->_fields, $name, Arr::get($this->_associations, $name));
	}

	public function attribute_insist($name)
	{
		$attribute = $this->attribute($name);

		if ( ! $attribute)
			throw new Kohana_Exception('The attrubute :name for this model :model does not exist', array(':name' => $name, ':model' => $this->_model));

		return $attribute;
	}


	/**
	 * Gets and sets the fields for this object.
	 *
	 * Calling this multiple times will overwrite fields.
	 *
	 * @param   array|null  $fields
	 * @return  array|Jam_Meta
	 */
	public function fields(array $fields = NULL)
	{
		if ($fields === NULL)
		{
			// Return the fields
			return $this->_fields;
		}

		foreach ($fields as $name => $field)
		{
			// Set the field
			$this->field($name, $field);
		}

		// Return Jam_Meta
		return $this;
	}

	/**
	 * Getter / setter for individual associations.
	 *
	 * @param   string       $name     name of the association
	 * @param   mixed        $association    the association alias or object
	 * @return  Jam_Association|Jam_Meta|null
	 */
	public function association($name, $association = NULL, $prepend = FALSE)
	{
		if ($association === NULL)
		{
			// Get the association
			return Arr::get($this->_associations, $name);
		}

		if ($this->_initialized)
		{
			// Cannot set after initialization
			throw new Kohana_Exception(':class already initialized, cannot set :association', array(
				':class' => Jam::class_name($this->_model),
				':association'   => $name,
			));
		}

		// Set the association
		if ($prepend)
		{
			$this->_associations = array($name => $association) + $this->_associations;
		}
		else
		{
			$this->_associations[$name] = $association;
		}

		// Return Jam_Meta
		return $this;
	}

	/**
	 * The same as assocation method, but throws an exception if association does not exist
	 * @param   string       $name     name of the association
	 * @param   mixed        $association    the association alias or object
	 * @return  Jam_Association|Jam_Meta|null
	 * @see  association
	 */
	public function association_insist($name, $association = NULL)
	{
		if ( ! isset($this->_associations[$name]))
			throw new Kohana_Exception('The association :name for this model :model does not exist', array(':name' => $name, ':model' => $this->_model));

		return $this->association($name, $association);
	}


	/**
	 * Gets and sets the associations for this object.
	 *
	 * Calling this multiple times will overwrite associations.
	 *
	 * @param   array|null  $associations
	 * @return  array|Jam_Meta
	 */
	public function associations(array $associations = NULL)
	{
		if ($associations === NULL)
		{
			// Return the associations
			return $this->_associations;
		}

		foreach ($associations as $name => $association)
		{
			// Set the associations
			$this->association($name, $association);
		}

		// Return Jam_Meta
		return $this;
	}

	/**
	 * Returns the defaults for the object.
	 *
	 * If $name is specified, then the defaults
	 * for that field are returned.
	 *
	 * @param   string|null  $name
	 * @return  array|mixed|null
	 */
	public function defaults($name = NULL)
	{
		if ($name === NULL)
		{
			return $this->_defaults;
		}

		return $this->field($name)->default;
	}

	/**
	 * Returns or sets the name of the file used for errors.
	 *
	 * @return string
	 */
	public function errors_filename($value = NULL)
	{
		if (func_num_args() !== 0)
		{
			return $this->set('errors_filename', $value);
		}

		return $this->_errors_filename;
	}

	/**
	 * Gets or sets the behaviors attached to the object.
	 *
	 * @param   array|null  $behaviors
	 * @return  array|Kohana_Jam_Meta
	 */
	public function behaviors(array $behaviors = NULL)
	{
		if (func_num_args() == 0)
		{
			return $this->_behaviors;
		}

		// Try to append
		return $this->set_append('behaviors', $behaviors);
	}


	/**
	 * Get / Set individual behaviors.
	 *
	 * @param   string       $name     name of the association
	 * @param   mixed        $association    the association alias or object
	 * @return  Jam_Association|Jam_Meta|null
	 */
	public function behavior($name, $behavior = NULL, $prepend = FALSE)
	{
		if ($behavior === NULL)
		{
			// Get the behavior
			return Arr::get($this->_behaviors, $name);
		}

		if ($this->_initialized)
		{
			// Cannot set after initialization
			throw new Kohana_Exception(':class already initialized, cannot set :behavior', array(
				':class' => Jam::class_name($this->_model),
				':behavior'   => $name,
			));
		}

		// Set the behavior
		if ($prepend)
		{
			$this->_behaviors = array($name => $behavior) + $this->_behaviors;
		}
		else
		{
			$this->_behaviors[$name] = $behavior;
		}

		// Return Jam_Meta
		return $this;
	}

	/**
	 * Gets the events attached to the object.
	 *
	 * @return  array|Jam_Event
	 */
	public function events()
	{
		return $this->_events;
	}

	/**
	 * Gets or sets the model's primary key.
	 *
	 * @param   string  $value
	 * @return  mixed
	 */
	public function primary_key($value = NULL)
	{
		if (func_num_args() !== 0)
		{
			return $this->set('primary_key', $value);
		}

		return $this->_primary_key;
	}

	/**
	 * Gets or sets the model's name key
	 *
	 * @param   string  $value
	 * @return  string
	 */
	public function name_key($value = NULL)
	{
		if (func_num_args() !== 0)
		{
			return $this->set('name_key', $value);
		}

		return $this->_name_key;
	}

	/**
	 * Gets or sets the model's foreign key
	 *
	 * @param   string  $value
	 * @return  string
	 */
	public function foreign_key($value = NULL)
	{
		if (func_num_args() !== 0)
		{
			return $this->set('foreign_key', $value);
		}

		return $this->_foreign_key;
	}

	/**
	 * Gets the unique key basend on a model method
	 *
	 * @param   string  $value
	 * @return  string
	 */
	public function unique_key($value = NULL)
	{
		if (is_callable($value) AND ! $this->initialized())
		{
			return $this->set('unique_key', $value);
		}

		if ($this->_unique_key)
		{
			return call_user_func($this->_unique_key, $value);
		}
		else
		{
			return (is_numeric($value) OR $value === NULL) ? $this->primary_key() : $this->name_key();
		}
	}

	/**
	 * Gets the model's polymorphic key.
	 *
	 * @param   string  $value
	 * @return  string
	 */
	public function polymorphic_key($value = NULL)
	{
		return $this->_polymorphic_key;
	}

	/**
	 * Gets the model's child models
	 *
	 * @param   array  $children
	 * @return  array
	 */
	public function children(array $children = NULL)
	{
		if (func_num_args() == 0)
		{
			return $this->_children;
		}

		// Try to append
		return $this->set_append('children', $children);
	}

	/**
	 * Gets or sets the object's sorting properties
	 *
	 * @param   array|null  $value
	 * @return  array
	 */
	public function sorting($value = NULL)
	{
		if (func_num_args() !== 0)
		{
			return $this->set('sorting', $value);
		}

		return $this->_sorting;
	}

} // End Kohana_Jam_Meta
