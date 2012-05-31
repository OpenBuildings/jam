<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Jam Model
 *
 * Jam_Model is the class all models must extend. It handles
 * various CRUD operations and relationships to other models.
 *
 * @package    Jam
 * @category   Models
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 OpenBuildings Inc.
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Model extends Model {

	/**
	 * @var  array  The original data set on the object
	 */
	protected $_original = array();

	/**
	 * @var  array  Data that's changed since the object was loaded
	 */
	protected $_changed = array();

	/**
	 * @var  array  Data that's already been retrieved is cached
	 */
	protected $_retrieved = array();

	/**
	 * @var  array  Unmapped data that is still accessible
	 */
	protected $_unmapped = array();

	/**
	 * @var  boolean  Whether or not the model is loaded
	 */
	protected $_loaded = FALSE;

	/**
	 * @var  boolean  Whether or not the model is saved
	 */
	protected $_saved = FALSE;

	/**
	 * @var  boolean  Whether or not the model is saved
	 */
	protected $_is_saving = FALSE;

	/**
	 * @var  Jam_Meta  A copy of this object's meta object
	 */
	protected $_meta = NULL;

	/**
	 * @var  Jam_Validation  A copy of this object's validation
	 */
	protected $_validation = NULL;

	/**
	 * @var  Boolean  A flag that keeps track of whether or not the model is valid
	 */
	 protected $_valid = FALSE;

	/**
	 * @var  Boolean  A flag that indicates a record has been deleted from the database
	 */
	 protected $_deleted = FALSE;

	/**
	 * @var  array  With data
	 */
	protected $_with = array();

	/**
	 * Constructor.
	 *
	 * A key can be passed to automatically load a model by its
	 * unique key.
	 *
	 * @param  mixed|null  $key
	 */
	public function __construct($key = NULL, $meta_name = NULL)
	{
		if ($meta_name === NULL)
		{
			$meta_name = $this;
		}

		// Load the object's meta data for quick access
		$this->_meta = Jam::meta($meta_name);

		// Copy over the defaults into the original data.
		$this->_original = $this->_meta->defaults();

		// Have an id? Attempt to load it
		if ($key)
		{
			$result = Jam::query($meta_name, $key)
				->as_object(FALSE)
				->select();

			// Only load if a record is found
			if ($result)
			{
				$this->load_values($result);
			}
		}
	}

	/**
	 * Gets the value of a field.
	 *
	 * Unlike Jam_Model::get(), values that are returned are cached
	 * until they are changed and relationships are automatically select()ed.
	 *
	 * @see     get()
	 * @param   string  $name The name or alias of the field you're retrieving
	 * @return  mixed
	 */
	public function &__get($name)
	{
		// Alias the field to its actual name. We must do this now
		// so that any aliases will be cached under the real field's
		// name, rather than under its alias name
		if ($association = $this->_meta->association($name))
		{
			$name = $association->name;
		}
		elseif ($field = $this->_meta->field($name))
		{
			$name = $field->name;
		}

		if ( ! array_key_exists($name, $this->_retrieved))
		{
			$this->_retrieved[$name] = $this->get($name);;
		}

		return $this->_retrieved[$name];
	}

	/**
	 * Sets the value of a field.
	 *
	 * @see     set()
	 * @param   string  $name  The name of the field you're setting
	 * @param   mixed   $value The value you're setting
	 * @return  void
	 */
	public function __set($name, $value)
	{
		// Being set by mysql_fetch_object, store the values for the constructor
		if (empty($this->_original))
		{
			$this->_preload_data[$name] = $value;
			return;
		}

		$this->set($name, $value);
	}

	/**
	 * Add methods for this model on the fly (mixins) you can assign:
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

		$this->events()->bind_callbacks('model', $callbacks);
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
		return $this->meta()->events()->trigger_callback('model', $this, $method, $args);
	}

	/**
	 * Returns true if $name is a field of the model or an unmapped column.
	 *
	 * This does not conform to the standard of returning FALSE if the
	 * property is set but the value is NULL. Rather this acts more like
	 * property_exists.
	 *
	 * @param  string    $name
	 * @return  boolean
	 */
	public function __isset($name)
	{
		return (bool) ($this->_meta->field($name) OR (bool) ($this->_meta->association($name)) OR array_key_exists($name, $this->_unmapped));
	}

	/**
	 * This doesn't unset fields. Rather, it sets them to their original
	 * value. Unmapped, changed, and retrieved values are unset.
	 *
	 * In essence, unsetting a field sets it as if you never made any changes
	 * to it, and clears the cache if the value has been retrieved with those changes.
	 *
	 * @param   string  $name
	 * @return  void
	 */
	public function __unset($name)
	{
		if ($this->_meta->field($name) OR $this->_meta->association($name))
		{
			// Ensure changed and retrieved data is cleared
			// This effectively clears the cache and any changes
			unset($this->_changed[$name]);
			unset($this->_retrieved[$name]);
		}

		// We can safely delete this no matter what
		unset($this->_unmapped[$name]);
	}

	/**
	 * Returns a string representation of the model in the
	 * form of `Model_Name (id)` or `Model_Name (NULL)` if
	 * the model is not loaded.
	 *
	 * This is designed to be useful for debugging.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return (string) get_class($this).'('.($this->id() ? $this->id() : 'NULL').')';
	}

	/**
	 * Gets the value for a field.
	 *
	 * @param   string       $name The field's name
	 * @return  array|mixed
	 */
	public function get($name)
	{
		if ($association = $this->_meta->association($name))
		{
			$name = $association->name;

			if (array_key_exists($name, $this->_changed))
			{
				return $this->_changed[$name];
			}
			else
			{
				return $association->get($this);
			}
		}
		elseif ($field = $this->_meta->field($name))
		{
			// Alias the name to its actual name
			$name = $field->name;

			if (array_key_exists($name, $this->_changed))
			{
				return $field->get($this, $this->_changed[$name]);
			}
			else
			{
				return $this->original($name);
			}
		}
		// Return unmapped data from custom queries
		elseif (isset($this->_unmapped[$name]))
		{
			return $this->_unmapped[$name];
		}
	}

	/**
	 * Returns the original value of a field, before it was changed.
	 *
	 * This method—combined with get(), which first searches for changed
	 * values—is useful for comparing changes that occurred on a model.
	 *
	 * @param   string  $field The field's or alias name
	 * @return  mixed
	 */
	public function original($field = NULL)
	{
		if ($field === NULL)
			return $this->_original;

		if ($field = $this->_meta->field($field))
		{
			// Alias the name to its actual name
			return $field->get($this, $this->_original[$field->name]);
		}
	}

	/**
	 * Returns an array of values in the fields.
	 *
	 * You can pass an array of field names to retrieve
	 * only the values for those fields:
	 *
	 *     $model->as_array(array('id', 'name', 'status'));
	 *
	 * @param   array  $fields
	 * @return  array
	 */
	public function as_array(array $fields = NULL)
	{
		$fields = $fields ? $fields : array_keys($this->_meta->fields());
		$result = array();

		foreach ($fields as $field)
		{
			$result[$field] = $this->__get($field);
		}

		return $result;
	}

	/**
	 * Set preloaded values, without changing the save, loaded and changed flags
	 * @param  array|string $values 
	 * @param  mixed $value  
	 * @return Jam_Model         $this
	 */
	public function retrieved($values, $value = NULL)
	{
		// Accept retrieved('name', 'value');
		if ( ! is_array($values))
		{
			$values = array($values => $value);
		}

		$this->_retrieved = Arr::merge($this->_retrieved, $values);

		return $this;
	}
	/**
	 * Sets the value of a field.
	 *
	 * You can pass an array of key => value pairs
	 * to set multiple fields at the same time:
	 *
	 *    $model->set(array(
	 *        'field1' => 'value',
	 *        'field2' => 'value',
	 *         ....
	 *    ));
	 *
	 * @param   array|string  $values
	 * @param   string        $value
	 * @return  Jam_Model
	 */
	public function set($values, $value = NULL)
	{
		// Accept set('name', 'value');
		if ( ! is_array($values))
		{
			$values = array($values => $value);
		}

		foreach ($values as $key => $value)
		{
			if ($field = $this->_meta->association($key))
			{
				$this->_changed[$field->name] = $field->set($this, $value);
				$this->_saved = $this->_valid = FALSE;
			}
			else
			{
				$field = $this->_meta->field($key);

				// If this isn't a field, we just throw it in unmapped
				if ( ! $field)
				{
					unset($this->_retrieved[$key]);
					$this->_unmapped[$key] = $value;

					continue;
				}

				// Compare the new value with the current value
				// If it's not changed, we don't need to continue
				$value = $field->set($value);
				$current_value = array_key_exists($field->name, $this->_changed)
											 ? $this->_changed[$field->name]
											 : $this->_original[$field->name];

				// Ensure data is really changed
				if ($value === $current_value)
					continue;

				// Data has changed
				$this->_changed[$field->name] = $value;

				// Run filters after it's set as changed
				$this->_changed[$field->name] = $this->run_filter($field, $this->_changed[$field->name]);
			}


			// Invalidate the cache
			if (array_key_exists($field->name, $this->_retrieved))
			{
				unset($this->_retrieved[$field->name]);
			}

			// Model is no longer saved or valid
			$this->_saved = $this->_valid = FALSE;
		}

		return $this;
	}

	/**
	 * Filters a value for a specific column
	 *
	 * @param    string       $field  The column name
	 * @param    string       $value  The value to filter
	 * @return   string
	 * @credits  Kohana Team
	 */
	protected function run_filter($field, $value)
	{
		// Set filters
		$filters = $field->filters;

		// Set the actual field
		$field = $field->name;

		// Bind the field name and model so they can be used in the filter method
		$_bound = array
		(
			':field' => $field,
			':model' => $this,
		);

		foreach ($filters as $array)
		{
			// Value needs to be bound inside the loop so we are always using the
			// version that was modified by the filters that already ran
			$_bound[':value'] = $value;

			// Filters are defined as array($filter, $params)
			$filter = $array[0];
			$params = Arr::get($array, 1, array(':value'));

			foreach ($params as $key => $param)
			{
				if (is_string($param) AND array_key_exists($param, $_bound))
				{
					// Replace with bound value
					$params[$key] = $_bound[$param];
				}
			}

			// Replace bound values for the filter
			if (is_array($filter) AND ($filter[0] == ':model' OR $filter[0] == ':field') AND array_key_exists(':model', $_bound))
			{
				if ($filter[0] == ':model')
				{
					// Replace with bound value
					$filter[0] = $_bound[$filter[0]];
				}
				elseif ($filter[0] == ':field')
				{
					// Set fields
					$_fields = $_bound[':model']->meta()->fields();

					// Replace with bound value
					$filter[0] = $_fields[$field];
				}
			}

			if (is_array($filter) OR ! is_string($filter))
			{
				// This is either a callback as an array or a lambda
				$value = call_user_func_array($filter, $params);
			}
			elseif (strpos($filter, '::') === FALSE)
			{
				// Use a function call
				$function = new ReflectionFunction($filter);

				// Call $function($this[$field], $param, ...) with Reflection
				$value = $function->invokeArgs($params);
			}
			else
			{
				// Split the class and method of the rule
				list($class, $method) = explode('::', $filter, 2);

				// Use a static method call
				$method = new ReflectionMethod($class, $method);

				// Call $Class::$method($this[$field], $param, ...) with Reflection
				$value = $method->invokeArgs(NULL, $params);
			}
		}

		return $value;
	}

	/**
	 * Clears the object and loads an array of values into the object.
	 *
	 * This should only be used for setting from database results
	 * since the model declares itself as saved and loaded after.
	 *
	 * @param   Jam_Collection|Jam_Model|array  $values
	 * @return  Jam_Model
	 */
	public function load_values($values)
	{
		// Clear the object
		$this->clear();

		foreach ($values as $key => $value)
		{
			// Key is coming from a with statement
			if (substr($key, 0, 1) === ':')
			{
				// The field comes back as ':model:field',
				// but can have infinite :field parts
				$targets = explode(':', ltrim($key, ':'), 2);

				// Alias as it comes back in, which allows
				// people to use with() with alaised field names
				$relationship = $this->_meta->association_insist(array_shift($targets))->name;

				// Find the field we need to set the value as
				$target = implode(':', $targets);

				// If there is no ":" in the target, it is a
				// column, otherwise it's another with()
				if (FALSE !== strpos($target, ':'))
				{
					$target = ':'.$target;
				}

				$this->_with[$relationship][$target] = $value;
			}
			// Standard setting of a field
			elseif ($field = $this->_meta->field($key))
			{
				$this->_original[$field->name] = $field->set($value);
			}
			// Unmapped data
			else
			{
				$this->_unmapped[$key] = $value;
			}
		}
		
		// Model is now saved and loaded
		$this->_saved = $this->_loaded = TRUE;

		return $this;
	}

	/**
	 * Validates the current model's data
	 *
	 * @throws  Jam_Validation_Exception
	 * @param   Validation|null   $extra_validation
	 * @return  Kohana_Jam_Model
	 */
	public function check()
	{
		$key = $this->_original[$this->_meta->primary_key()];

		// For loaded models, we're only checking what's changed, otherwise we check it all
		$data = $key ? ($this->_changed) : ($this->_changed + $this->_original);

		// Always build a new validation object
		$this->_validation($data + $this->_unmapped, (bool) $key);

		// Run validation
		if ( ! $this->_valid)
		{
			$this->_meta->events()->trigger('model.before_validate', $this, array($this->_validation));

			$this->_valid = $this->_validation->check();

			foreach ($this->_meta->associations() as $association_name => $association) 
			{
				if ($key AND ! array_key_exists($association_name, $this->_changed))
					continue;

				$association->after_check($this, $this->_validation, Arr::get($data, $association_name));
			}

			$this->_valid = $this->_validation->is_valid();

			if ( ! $this->_valid)
			{
				Kohana::$log->add(Log::DEBUG, "Errors in ".$this." : ".print_r($this->errors(), TRUE));
			}

			$this->_meta->events()->trigger('model.after_validate', $this, array($this->_validation));
		}

		return $this->_valid;
	}

	public function check_insist()
	{
		if ( ! $this->check())
			throw new Jam_Validation_Exception($this->_meta->errors_filename(), $this->_validation);
		
		return $this;
	}

	/**
	 * Get the errors from the previous check, if you provide a name, will return the errors only for that name
	 * Automatically loads the error messages file from messages/jam-validation/<model_name>
	 * If there are no errors yet - return NULL
	 * 
	 * @param  string $name the name of the field to get errors of
	 * @return array|NULL
	 */
	public function errors($name = NULL)
	{
		if ( ! $this->_validation)
			return NULL;

		$errors = $this->_validation->errors($this->_meta->errors_filename());

		return $name === NULL ? $errors : Arr::get($errors, $name);
	}

	/**
	 * Initializes validation rules, and labels
	 *
	 * @param   array  $data
	 * @param   bool   $update
	 * @return  void
	 */
	protected function _validation($data, $update = FALSE)
	{
		// Build the validation object with its rules
		$this->_validation = Jam_Validation::factory($data)
			->bind(':model', $this);

		// Add rules and labels
		$this->_validation = $this->_meta->validation_options($this->_validation, $update);
	}

	/**
	 * Creates or updates the current record.
	 *
	 * @param   bool|null        $validate
	 * @return  Kohana_Jam_Model
	 */
	public function save($validate = NULL)
	{
		$this->_is_saving = TRUE;

		$key = $this->_original[$this->_meta->primary_key()];
		
		// Run validation
		if ($validate !== FALSE)
		{
			$this->check_insist();
		}

		// These will be processed later
		$values = $saveable = array();

		// Trigger callbacks and ensure we should proceed
		if ($this->_meta->events()->trigger('model.before_save', $this) === FALSE)
		{
			return $this;
		}
		
		$event_type = $key ? 'update' : 'create';
		
		if ($this->_meta->events()->trigger('model.before_'.$event_type, $this) === FALSE)
		{
			return $this;
		}

		foreach ($this->_retrieved as $column => $value) 
		{
			if ( ! isset($this->_changed[$column])
				AND $association = $this->_meta->association($column)
				AND $association instanceof Jam_Association_Collection 
				AND $value->changed())
			{
				$this->_changed[$column] = $value;
			}
		}
		
		foreach ($this->_meta->associations() as $name => $association)
		{
			$association->before_save($this, Arr::get($this->_changed, $name), (bool) isset($this->_changed[$name]));
		}				

		// Iterate through all fields in original in case any unchanged fields
		// have save() behavior like timestamp updating...
		// 
		
		foreach ($this->_changed + $this->_original as $column => $value)
		{
			if ($field = $this->_meta->field($column))
			{
				// Only save in_db values
				if ($field->in_db)
				{
					// See if field wants to alter the value on save()
					$value = $field->save($this, $value, $key);

					// Only set the value to be saved if it's changed from the original
					if ($value !== $this->_original[$column])
					{
						$values[$field->name] = $value;
					}
					// Or if we're INSERTing and we need to set the defaults for the first time
					elseif ( ! $key AND ! $this->changed($field->name) AND ! $field->primary)
					{
						$values[$field->name] = $field->default;
					}
				}
			}
		}

		// If we have a key, we're updating
		if ($key)
		{
			// Do we even have to update anything in the row?
			if ($values)
			{
				Jam::query($this, $key)
					 ->set($values)
					 ->update();
			}
		}
		else
		{
			list($id) = Jam::query($this)
							 ->columns(array_keys($values))
							 ->values(array_values($values))
							 ->insert();

			// Gotta make sure to set this
			$key = $this->_changed[$this->_meta->primary_key()] = $id;
		}
		
		// Re-set any saved values; they may have changed
		foreach ($values as $column => $value)
		{
			$this->set($column, $value);
		}

		$this->_loaded = $this->_saved = TRUE;
		
		foreach ($this->_meta->associations() as $name => $association)
		{			
			$association->after_save($this, Arr::get($this->_changed, $name), (bool) isset($this->_changed[$name]));
		}
		// Set the changed data back as original
		$this->_original = array_merge($this->_original, $this->_changed);

		// We're good!
		$this->_retrieved = $this->_changed = array();


		$this->_meta->events()->trigger('model.after_'.$event_type, $this);
		
		// Trigger post-save callback
		$this->_meta->events()->trigger('model.after_save', $this);

		$this->_is_saving = FALSE;

		return $this;
	}

	/**
	 * Deletes a single record.
	 *
	 * @return  boolean
	 **/
	public function delete()
	{
		$result = FALSE;

		// Are we loaded? Then we're just deleting this record
		if ($this->_loaded)
		{
			$key = $this->_original[$this->_meta->primary_key()];

			// Trigger callbacks to ensure we proceed
			$result = $this->_meta->events()->trigger('model.before_delete', $this);

			if ($result === NULL)
			{
				// Trigger field callbacks
				foreach ($this->_meta->associations() as $association)
				{
					$association->delete($this, $key);
				}

				$result = Jam::query($this, $key)->delete();
			}
		}

		// Trigger the after-delete
		$this->_meta->events()->trigger('model.after_delete', $this);

		// Clear the object so it appears deleted anyway
		$this->clear();

		// Set the flag indicatig the model has been successfully deleted
		$this->_deleted = (bool) $result;

		return $this->_deleted;
	}

	/**
	 * Removes any changes made to a model.
	 *
	 * This method only works on loaded models.
	 *
	 * @return  Jam_Model
	 */
	public function revert()
	{
		if ($this->_loaded)
		{
			$this->_loaded =
			$this->_saved  = TRUE;

			$this->_changed   =
			$this->_retrieved = array();
		}

		return $this;
	}

	/**
	 * Sets a model to its original state, as if freshly instantiated
	 *
	 * @return  Jam_Model
	 */
	public function clear()
	{
		$this->_valid  =
		$this->_loaded =
		$this->_saved  = FALSE;

		$this->_with      =
		$this->_changed   =
		$this->_retrieved =
		$this->_unmapped  = array();

		$this->_original = $this->_meta->defaults();

		return $this;
	}

	/**
	 * Returns whether or not the model is loaded
	 *
	 * @return  boolean
	 */
	public function loaded()
	{
		return $this->_loaded;
	}

	public function loaded_insist()
	{
		if ( ! $this->loaded())
			throw new Kohana_Jam_Exception_NotLoaded("Model not loaded", $this);

		return $this;
	}

	/**
	 * Whether or not the model is saved
	 *
	 * @return  boolean
	 */
	public function saved()
	{
		return $this->_saved;
	}

	/**
	 * Whether or not the model is in the process of being saved
	 *
	 * @return  boolean
	 */
	public function is_saving()
	{
		return $this->_is_saving;
	}

	/**
	 * Whether or not the model is deleted
	 *
	 * @return  boolean
	 */
	public function deleted()
	{
		return $this->_deleted;
	}

	/**
	 * Returns whether or not the particular $field has changed.
	 *
	 * If $field is NULL, the method returns whether or not any
	 * data whatsoever was changed on the model.
	 *
	 * @param   string   $field
	 * @return  boolean
	 */
	public function changed($field = NULL)
	{
		if ($field)
		{
			return array_key_exists($this->_meta->field($field)->name, $this->_changed);
		}
		else
		{
			return (bool) $this->_changed;
		}
	}

	/**
	 * Returns the value of the model's primary key
	 *
	 * @return  mixed
	 */
	public function id()
	{
		return $this->get($this->_meta->primary_key());
	}

	/**
	 * Returns the value of the model's name key
	 *
	 * @return  mixed
	 */
	public function name()
	{
		return $this->get($this->_meta->name_key());
	}

	/**
	 * Returns the model's meta object
	 *
	 * @return  Jam_Meta
	 */
	public function meta()
	{
		return $this->_meta;
	}

	public function builder($name)
	{
		return $this->_meta->association_insist($name)->builder($this);
	}

	public function build($name, array $attributes = NULL)
	{
		return $this->_meta->association_insist($name)->build($this, $attributes);
	}

	public function create($name, array $attributes = NULL)
	{
		return $this->_meta->association_insist($name)->create($this, $attributes);
	}
}  // End Kohana_Jam_Model
