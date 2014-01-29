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
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Validated extends Model implements Serializable {

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
	 * @var  boolean  Whether or not the model is validating
	 */
	protected $_is_validating = FALSE;

	/**
	 * @var  Jam_Meta  A copy of this object's meta object
	 */
	protected $_meta = NULL;

	/**
	 * @var  Boolean  A flag that keeps track of whether or not the model is valid
	 */
	 protected $_errors = NULL;

	/**
	 * Constructor.
	 *
	 * A key can be passed to automatically load a model by its
	 * unique key.
	 *
	 * @param  mixed|null  $key
	 */
	public function __construct($meta_name = NULL)
	{
		if ($meta_name === NULL)
		{
			$meta_name = $this;
		}

		// Load the object's meta data for quick access
		$this->_meta = Jam::meta($meta_name);

		$this->_original = $this->meta()->defaults();
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
		if ( ! array_key_exists($name, $this->_retrieved))
		{
			$this->_retrieved[$name] = $this->get($name);
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
		return (bool) ($this->meta()->field($name) OR array_key_exists($name, $this->_unmapped));
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
		unset($this->_changed[$name]);
		unset($this->_retrieved[$name]);

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
		return (string) get_class($this).'('.($this->is_valid() ? 'Valid' : 'Not Valid').')';
	}

	/**
	 * Gets the value for a field.
	 *
	 * @param   string       $name The field's name
	 * @return  array|mixed
	 */
	public function get($name)
	{
		if ($field = $this->_meta->field($name))
		{
			// Alias the name to its actual name
			$name = $field->name;

			if (array_key_exists($name, $this->_changed))
			{
				return $field->get($this, $this->_changed[$name], TRUE);
			}
			else
			{
				return $this->original($name);
			}
		}
		// Return unmapped data from custom queries
		elseif ($this->unmapped($name))
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
	public function original($field_name = NULL)
	{
		if ($field_name === NULL)
			return $this->_original;

		if ($field = $this->_meta->field($field_name))
		{
			return $field->get($this, $this->_original[$field_name], FALSE);
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

		foreach ($values as $key => & $value)
		{
			if ($field = $this->meta()->field($key))
			{
				if ($value !== $this->{$field->name})
				{
					$this->_changed[$field->name] = $field->set($this, $value, TRUE);

					if (array_key_exists($field->name, $this->_retrieved))
					{
						unset($this->_retrieved[$field->name]);
					}
				}
			}
			elseif (property_exists($this, $key))
			{
				$this->$key = $value;
			}
			else
			{
				unset($this->_retrieved[$key]);
				$this->_unmapped[$key] = $value;
			}
		}

		return $this;
	}

	public function is_valid()
	{
		return count($this->errors()) == 0;
	}

	/**
	 * Validates the current model's data
	 *
	 * @throws  Jam_Exception_Validation
	 * @param   Validation|null   $extra_validation
	 * @return  Kohana_Jam_Model
	 */
	public function check($force = FALSE)
	{
		$this->_is_validating = TRUE;
		// Run validation only when new or changed
		if ($this->changed() OR $force)
		{
			// Reset the errors before checking
			$this->_errors = FALSE;

			$this->meta()->events()->trigger('model.before_check', $this, array($this->_changed));

			$this->meta()->execute_validators($this, $force);

			$this->meta()->events()->trigger('model.after_check', $this, array($this->_changed));
		}

		$this->_is_validating = FALSE;

		return $this->is_valid();
	}

	public function check_insist()
	{
		if ( ! $this->check())
			throw new Jam_Exception_Validation('There was an error validating the :model: :errors', $this);

		return $this;
	}

	/**
	 * Override this function to add custom validation after the validators.
	 * Having an empty validate function allow safely calling parent::validate()
	 * when extending models.
	 *
	 * You need to set errors with:
	 *     $this->errors()->add('field', 'error_name');
	 *
	 * @link http://git.io/5I47Tw docs
	 */
	public function validate() {}

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
		if ( ! $this->_errors)
		{
			$this->_errors = new Jam_Errors($this, $this->meta()->errors_filename());
		}

		if ($name !== NULL)
			return $this->_errors->messages($name);

		return $this->_errors;
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
		$this->_errors = NULL;

		$this->_changed   =
		$this->_retrieved = array();

		return $this;
	}

	/**
	 * Sets a model to its original state, as if freshly instantiated
	 *
	 * @return  Jam_Model
	 */
	public function clear()
	{
		$this->_errors = NULL;

		$this->_changed   =
		$this->_retrieved =
		$this->_unmapped  = array();

		$this->_original = $this->_meta->defaults();

		return $this;
	}

	/**
	 * Whether or not the model is in the process of being validated
	 *
	 * @return  boolean
	 */
	public function is_validating()
	{
		return ($this->_is_validating OR $this->_is_saving);
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
	public function changed($name = NULL)
	{
		if ($name)
		{
			return array_key_exists($name, $this->_changed);
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
		return $this->get($this->meta()->primary_key());
	}

	/**
	 * Returns the value of the model's name key
	 *
	 * @return  mixed
	 */
	public function name()
	{
		return $this->get($this->meta()->name_key());
	}

	/**
	 * Returns the model's meta object
	 *
	 * @return  Jam_Meta
	 */
	public function meta()
	{
		if ( ! $this->_meta)
			throw new Kohana_Exception('Model for class :class does not have a meta', array(':class' => get_class($this)));

		return $this->_meta;
	}

	public function unmapped($name)
	{
		return array_key_exists($name, $this->_unmapped);
	}


	public function serialize()
	{
		return serialize(array(
			'original' => $this->_original,
			'changed' => $this->_changed,
			'unmapped' => $this->_unmapped,
		));
	}

	public function unserialize($data)
	{
		$data = unserialize($data);
		$this->_meta = Jam::meta($this);
		$this->_original = $data['original'];
		$this->_changed = $data['changed'];
		$this->_unmapped = $data['unmapped'];
	}
}
