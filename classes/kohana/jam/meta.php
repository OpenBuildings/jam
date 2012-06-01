<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Jam Meta
 *
 * Jam_Meta objects act as a registry of information about a particular model.
 *
 * @package    Jam
 * @category   Meta
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 OpenBuildings Inc.
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
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
	 * @var  array  An array of 1:1 relationships to pass to with() for every SELECT
	 */
	protected $_load_with = array();

	/**
	 * @var  array  A map to the models's fields and how to process each column.
	 */
	protected $_fields = array();

	/**
	 * @var array  Extra validation rules to be added to the model validation
	 */
	protected $_extra_rules = array();

	/**
	 * @var  array  A map to the models's associations and how to process each column.
	 */
	protected $_associations = array();	

	/**
	 * @var  array  A map of aliases to fields
	 */
	protected $_aliases = array();

	/**
	 * @var  string  The builder class the model is associated with. This defaults to
	 *               Jam_Builder_Modelname, if that particular class is found.
	 */
	protected $_builder = '';

	/**
	 * @var  string  Rules and labels attached to the validation object
	 */
	protected $_validation_options = NULL;

	/**
	 * The message filename used for validation errors.
	 * Defaults to Jam_Meta::$_model
	 * @var string
	 */
	protected $_errors_filename = NULL;

	/**
	 * @var  array  A list of columns and how they relate to fields
	 */
	protected $_columns = array();

	/**
	 * @var  array  Default data for each field
	 */
	protected $_defaults = array();

	/**
	 * @var  array  A cache of retrieved fields, with aliases resolved
	 */
	protected $_field_cache = array();

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

	/**
	 * The most basic initialization possible
	 * @param string $model Model name
	 */
	public function __construct($model)
	{
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
		$behavior_class = Jam::behavior_prefix().$model;

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
			$behavior->initialize($this->_events, $model, $name);
		}

		// Allow modification of this meta object by the behaviors
		$this->_events->trigger('meta.before_finalize', $this);

		Jam::global_trigger('meta.before_finalize', $this);

		// Ensure certain fields are not overridden
		$this->_model       = $model;
		$this->_columns     =
		$this->_defaults    =
		$this->_field_cache =
		$this->_aliases     = array();

		if ( ! $this->_errors_filename)
		{
			// Default errors filename to the model's name
			$this->_errors_filename = 'jam-validation/'.$this->_model;
		}

		// Table should be a sensible default
		if (empty($this->_table))
		{
			$this->_table = Inflector::plural($model);
		}

		// See if we have a special builder class to use
		if (empty($this->_builder))
		{
			$builder = Jam::model_prefix().'builder_'.$model;

			if (class_exists($builder))
			{
				$this->_builder = $builder;
			}
			else
			{
				$this->_builder = 'Jam_Builder';
			}
		}

		// Can we set a sensible foreign key?
		if (empty($this->_foreign_key))
		{
			$this->_foreign_key = $model.'_id';
		}

		foreach ($this->_fields as $column => $field)
		{
			// Ensure a default primary key is set
			if ($field instanceof Jam_Field AND $field->primary AND empty($this->_primary_key))
			{
				$this->_primary_key = $column;
			}
		}

		foreach ($this->_associations as $column => & $association)
		{
			if (is_string($association))
			{
				$association = Jam::association($association);
			}

			$association->initialize($this, $model, $column);
		}

		// Initialize all of the fields with their column and the model name
		foreach ($this->_fields as $column => $field)
		{
			// Allow aliasing fields
			if (is_string($field))
			{
				if (isset($this->_fields[$field]))
				{
					$this->_aliases[$column] = $field;
				}

				// Aliases shouldn't pollute fields
				unset($this->_fields[$column]);

				continue;
			}

			$field->initialize($model, $column);

			// Set the defaults so they're actually persistent
			$this->_defaults[$column] = $field->default;
		}


		// Meta object is initialized and no longer writable
		$this->_initialized = TRUE;

		// Final meta callback
		$this->_events->trigger('meta.after_finalize', $this);

		Jam::global_trigger('meta.after_finalize', $this);
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
	 * Gets or sets the builder attached to this object
	 *
	 * @param   string  $value
	 * @return  Jam_Meta|string
	 */
	public function builder($value = NULL)
	{
		if (func_num_args() !== 0)
		{
			return $this->set('builder', $value);
		}

		return $this->_builder;
	}

	/**
	 * Getter / setter for individual fields.
	 *
	 * @param   string       $name     name of the field
	 * @param   mixed        $field    the field alias or object
	 * @return  Jam_Field|Jam_Meta|null
	 */
	public function field($name, $field = NULL)
	{
		if ($field === NULL)
		{
			// Get the field
			if ( ! isset($this->_field_cache[$name]))
			{
				// Set the resolved name to the given name for now
				$resolved_name = $name;

				if (isset($this->_aliases[$name]))
				{
					// If the field is among the aliases set the alias as resolved name
					$resolved_name = $this->_aliases[$name];
				}

				if (isset($this->_fields[$resolved_name]))
				{
					// Get the field from cache using the resolved name
					$this->_field_cache[$name] = $this->_fields[$resolved_name];
				}
				else
				{
					// No such field found
					return NULL;
				}
			}

			// Return the field
			return $this->_field_cache[$name];
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
		$this->_fields[$name] = $field;

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
	public function association($name, $association = NULL)
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
		$this->_associations[$name] = $association;

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
	 * Add rules and labels to the validation object
	 *
	 * @param   Validation  $validation
	 * @param   bool        $update Are we updating?
	 * @return  null|string
	 */
	public function validation_options(Validation $validation, $update = FALSE)
	{
		// Set validation options
		$this->_validation_options = $validation;

		// Set submitted fields
		if ($update)
		{
			$submitted_fields = $validation->data();
		}

		// Add our rules and labels
		foreach ($this->_fields as $name => $field)
		{
			// If updating add only the rules for the updated fields
			if ($update AND ! array_key_exists($name, $submitted_fields))
			{
				continue;
			}

			$this->_validation_options->label($name, $field->label);
			$this->_validation_options->rules($name, $field->rules);

		}

		foreach ($this->_associations as $name => $association) 
		{
			$this->_validation_options->label($name, $association->label);
		}

		// Apply extra rules added after the initializing of the model
		if ($this->_extra_rules)
		{
			foreach ($this->_extra_rules as $name => $rules) 
			{
				// If updating add only the rules for the updated fields
				if ($update AND ! array_key_exists($name, $submitted_fields) AND array_key_exists($name, $this->_fields))
				{
					continue;
				}

				$this->_validation_options->rules($name, $rules);
			}
		}

		// Return the validation object with rules and labels
		return $this->_validation_options;
	}

	/**
	 * Add some extra rules for validation of models for this meta, or pass array() to clear them all
	 * 
	 * @param string|array $rules 
	 * @param array $rule  
	 */
	public function extra_rules($rules = NULL, $rule = NULL)
	{
		// Accept rules('name', 'rule');
		if ($rule !== NULL)
		{
			$rules = array($rules => $rule);
		}

		if (is_array($rules))
		{
			// If passed empty array - clear the rules
			$this->_extra_rules = $rules ? Arr::merge($rules, $this->_extra_rules) : $rules;
			return $this;
		}

		if (is_string($rules))
		{
			return Arr::get($this->_extra_rules, $rules);
		}

		return $this->_extra_rules;
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

	/**
	 * Gets or sets the object's load_with properties
	 *
	 * @param   array|null  $value
	 * @return  array
	 */
	public function load_with($value = NULL)
	{
		if (func_num_args() !== 0)
		{
			// Convert the value to an array if needed
			if ( ! is_array($value))
			{
				$value = (array) $value;
			}

			return $this->set('load_with', $value);
		}

		return $this->_load_with;
	}

	/**
	 * Add methods for this meta on the fly (mixins) you can assign:
	 * Class - loads all static methods
	 * array or string/array callback
	 * array of closures
	 * @param  array|string   $callbacks 
	 * @param  mixed $callback  
	 * @return Jam_Meta              $this
	 */
	public function extend($callbacks, $callback = NULL)
	{
		// Handle input with second argument, so you can pass single items without an array
		if ($callback !== NULL)
		{
			$callbacks = array($callbacks => $callback);
		}

		$this->events()->bind_callbacks('meta', $callbacks);
		return $this;
	}

	/**
	 * Passes unknown methods along to the behaviors.
	 *
	 * @param   string  $method
	 * @param   array   $args
	 * @return  mixed
	 **/
	public function __call($method, $args)
	{
		return $this->events()->trigger_callback('meta', $this, $method, $args);
	}

} // End Kohana_Jam_Meta