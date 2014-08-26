<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Jam Core
 *
 * This core class is the main interface to all
 * models, builders, and meta data.
 *
 * @package    Jam
 * @category   Base
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam {

	/**
	 * @var  string  The prefix to use for all model's class names
	 *               This can be overridden to allow you to place
	 *               models and collections in a different location.
	 */
	protected static $_model_prefix = 'Model_';

	/**
	 * @var  string  The prefix to use for all model's collection class names
	 *               This can be overridden to allow you to place
	 *               models and collections in a different location.
	 */
	protected static $_collection_prefix = 'Model_Collection_';

	/**
	 * @var  string  This prefix to use for all model's field classes
	 *               This can be overridden to allow you to place
	 *               field classes in a different location.
	 */
	protected static $_field_prefix = 'Jam_Field_';

	/**
	 * @var  string  This prefix to use for all behavior classes
	 *               This can be overridden to allow you to place
	 *               behavior classes in a different location.
	 */
	protected static $_behavior_prefix = 'Jam_Behavior_';

	/**
	 * @var  string  This prefix to use for all model's association classes
	 *               This can be overridden to allow you to place
	 *               association classes in a different location.
	 */
	protected static $_association_prefix = 'Jam_Association_';

	/**
	 * @var  string  This prefix to use for all model's form classes
	 *               This can be overridden to allow you to place
	 *               form classes in a different location.
	 */
	protected static $_form_prefix = 'Jam_Form_';

	/**
	 * @var  string  This prefix to use for all attribute's validator rule classes
	 *               This can be overridden to allow you to place
	 *               form classes in a different location.
	 */
	protected static $_validator_rule_prefix = 'Jam_Validator_Rule_';

	/**
	 * @var  array  Contains all of the meta classes related to models
	 */
	public static $_models = array();

	/**
	 * Hold model objects for templates
	 * @var array
	 */
	protected static $_build_templates = array();

	/**
	 * Make a new object of the given model, optionally setting some fields
	 * @param  string $model_name
	 * @param  array  $attributes
	 * @return Jam_Model
	 */
	public static function build($model_name, array $attributes = NULL)
	{
		$meta = Jam::meta($model_name);

		if ($meta AND $meta->polymorphic_key() AND ! empty($attributes[$meta->polymorphic_key()]))
		{
			$model_name = $attributes[$meta->polymorphic_key()];
		}

		$class = Jam::class_name($model_name);

		$object = new $class();

		if ($attributes)
		{
			$object->set($attributes);
		}

		return $object;
	}

	/**
	 * Create a new object of a given model, optionally setting some fields, and then save it to the database
	 * @param  string $model
	 * @param  array  $attributes
	 * @return Jam_Model
	 */
	public static function create($model, array $attributes = array())
	{
		return Jam::build($model, $attributes)->save();
	}

	/**
	 * Gets a particular set of metadata about a model. If the model
	 * isn't registered, it will attempt to register it.
	 *
	 * FALSE is returned on failure.
	 *
	 * @param   string|Jam_Model  $model
	 * @return  Jam_Meta
	 */
	public static function meta($model)
	{
		$model = Jam::model_name($model);

		if ( ! isset(Jam::$_models[$model]))
		{
			if ( ! Jam::register($model))
			{
				return FALSE;
			}
		}

		return Jam::$_models[$model];
	}

	/**
	 * Factory for instantiating fields.
	 *
	 * @param   string  $type
	 * @param   mixed   $options
	 * @return  Jam_Field
	 */
	public static function field($type, $options = NULL)
	{
		$field = Jam::$_field_prefix.Jam::capitalize_class_name($type);

		return new $field($options);
	}

	/**
	 * Factory for instantiating associations.
	 *
	 * @param   string  $type
	 * @param   mixed   $options
	 * @return  Jam_Association
	 */
	public static function association($type, $options = NULL)
	{
		$association = Jam::$_association_prefix.Jam::capitalize_class_name($type);

		return new $association($options);
	}


	/**
	 * Factoring for instantiating behaviors.
	 *
	 * @param   string  $type
	 * @param   mixed   $options
	 * @return  Jam_Behavior
	 */
	public static function behavior($type, $options = array())
	{
		$behavior = Jam::$_behavior_prefix.Jam::capitalize_class_name($type);

		return new $behavior($options);
	}

	/**
	 * Factoring for instantiating behaviors.
	 *
	 * @param   string  $type
	 * @param   mixed   $options
	 * @return  Jam_Validator_Rule
	 */
	public static function validator_rule($type, $options = array())
	{
		$rule = Jam::$_validator_rule_prefix.Jam::capitalize_class_name($type);

		return new $rule($options);
	}



	/**
	 * Automatically loads a model, if it exists,
	 * into the meta table.
	 *
	 * Models are not required to register
	 * themselves; it happens automatically.
	 *
	 * @param   string  $model
	 * @return  boolean
	 */
	public static function register($model)
	{
		$class = Jam::class_name($model);
		$model = Jam::model_name($model);

		// Don't re-initialize!
		if (isset(Jam::$_models[$model]))
		{
			return TRUE;
		}

		 // Can we find the class?
		if (class_exists($class))
		{
			// Prevent accidentally trying to load ORM or Sprig models
			if ( ! is_subclass_of($class, 'Jam_Validated'))
			{
				return FALSE;
			}
		}
		else
		{
			return FALSE;
		}

		// Load it into the registry
		Jam::$_models[$model] = $meta = new Jam_Meta($model);

		// Let the intialize() method override defaults.
		call_user_func(array($class, 'initialize'), $meta);

		// Finalize the changes
		$meta->finalize($model);

		return TRUE;
	}

	public static function capitalize_class_name($class_name)
	{
		return str_replace(' ', '_', ucwords(str_replace('_', ' ', $class_name)));
	}

	/**
	 * Returns the class name of a model
	 *
	 * @param   string|Jam_Validated  $model
	 * @return  string
	 */
	public static function class_name($model)
	{
		if ($model instanceof Jam_Validated)
		{
			return get_class($model);
		}
		else
		{
			return Jam::$_model_prefix.Jam::capitalize_class_name($model);
		}
	}

	/**
	 * Returns the model name of a class
	 *
	 * @param   string|Jam_Validated  $model
	 * @return  string
	 */
	public static function model_name($model)
	{
		if ($model instanceof Jam_Validated)
		{
			$model = get_class($model);
		}

		$prefix_length = strlen(Jam::$_model_prefix);

		// Compare the first parts of the names and chomp if they're the same
		if (strtolower(substr($model, 0, $prefix_length)) === strtolower(Jam::$_model_prefix))
		{
			$model = substr($model, $prefix_length);
		}

		return strtolower($model);
	}

	/**
	 * Returns the prefix to use for all models and collections.
	 *
	 * @return  string
	 */
	public static function model_prefix()
	{
		return Jam::$_model_prefix;
	}

	/**
	 * Returns the prefix to use for all models and collections.
	 *
	 * @return  string
	 */
	public static function collection_prefix()
	{
		return Jam::$_collection_prefix;
	}

	/**
	 * Returns the prefix to use for all fields.
	 *
	 * @return  string
	 */
	public static function field_prefix()
	{
		return Jam::$_field_prefix;
	}

	/**
	 * Returns the prefix to use for forms.
	 *
	 * @return  string
	 */
	public static function form_prefix()
	{
		return Jam::$_form_prefix;
	}


	/**
	 * Returns the prefix to use for all behaviors.
	 *
	 * @return  string
	 */
	public static function behavior_prefix()
	{
		return Jam::$_behavior_prefix;
	}

	/**
	 * Clear the cache of the models. You should do this only when you dynamically change models
	 * @param  string $name optionally clear only one model
	 */
	public static function clear_cache($name = NULL)
	{
		if ($name !== NULL)
		{
			unset(Jam::$_models[$name]);
		}
		else
		{
			Jam::$_models = array();
		}
	}

	/**
	 * Make an object of class Jam_Query_Builder_Delete
	 * @param  string $model
	 * @return Jam_Query_Builder_Delete
	 */
	public static function delete($model)
	{
		return new Jam_Query_Builder_Delete($model);
	}

	/**
	 * Make an object of class Jam_Query_Builder_Update
	 * @param  string $model
	 * @return Jam_Query_Builder_Update
	 */
	public static function update($model)
	{
		return new Jam_Query_Builder_Update($model);
	}

	/**
	 * Make an object of class Jam_Query_Builder_Insert
	 * @param  string $model
	 * @return Jam_Query_Builder_Insert
	 */
	public static function insert($model, array $columns = array())
	{
		return Jam_Query_Builder_Insert::factory($model, $columns);
	}

	/**
	 * Make an object of class Jam_Query_Builder_Select
	 * @param  string $model
	 * @return Jam_Query_Builder_Select
	 */
	public static function select($model)
	{
		return new Jam_Query_Builder_Select($model);
	}

	/**
	 * Make an object of class Jam_Query_Builder_Collection
	 * @param  string $model
	 * @return Jam_Query_Builder_Collection
	 */
	public static function all($model)
	{
		if ( ! ($meta = Jam::meta($model)))
			throw new Kohana_Exception('Model :model does not exist', array(':model' => $model));

		$class = $meta->collection();

		if ( ! $class)
		{
			$class = 'Jam_Query_Builder_Collection';
		}
		return new $class($model);
	}

	protected static function find_or($method, $model, array $values)
	{
		$collection = Jam::all($model);
		$converted_keys = array();
		foreach ($values as $key => $value)
		{
			$key = Jam_Query_Builder::resolve_meta_attribute($key, Jam::meta($model), $value);

			$collection->where($key, '=', $value);
			$converted_keys[$key] = $value;
		}

		if ($item = $collection->first())
			return $item;

		return call_user_func($method, $model, $converted_keys);
	}

	/**
	 * Try to find a model with the given fields, if one cannot be found,
	 * build it and set the fields we've search with to it.
	 * @param  string $model
	 * @param  array  $values
	 * @return Jam_Model
	 */
	public static function find_or_build($model, array $values)
	{
		return Jam::find_or('Jam::build', $model, $values);
	}

	/**
	 * Try to find a model with the given fields, if one cannot be found,
	 * create it and set the fields we've search with to it. Save the model to the database.
	 * @param  string $model
	 * @param  array  $values
	 * @return Jam_Model
	 */
	public static function find_or_create($model, array $values)
	{
		return Jam::find_or('Jam::create', $model, $values);
	}

	/**
	 * Find a model with its unique key. Return NULL on failure.
	 * You can pass an array - then it tries to find all the models corresponding to the keys
	 * @param  string $model
	 * @param  string|array $key
	 * @return Jam_Model
	 */
	public static function find($model, $key)
	{
		if ( ! $key)
			throw new Jam_Exception_Invalidargument(':model - no id specified', $model);

		$collection = Jam::all($model);
		$collection->where_key($key);
		return is_array($key) ? $collection : $collection->first();
	}

	/**
	 * Find a model with its unique key. Throw Jam_Exception_Notfound on failure
	 * You can pass an array of unique keys. If even one of them is not found, through Jam_Exception_Notfound
	 * @param  string $model
	 * @param  string|array $key
	 * @throws Jam_Exception_Invalidargument If id is array() or null
	 * @throws Jam_Exception_Notfound If no model was found
	 * @return Jam_Model
	 */
	public static function find_insist($model, $key)
	{
		if ( ! $key)
			throw new Jam_Exception_Invalidargument(':model - no id specified', $model);

		$result = Jam::find($model, $key);

		if (is_array($key))
		{
			$missing = array_diff(array_values($key), array_values($result->ids()));
		}
		else
		{
			$missing = $result ? array() : array($key);
		}

		if ($missing)
			throw new Jam_Exception_Notfound(':model (:missing) not found', $model, array(':missing' => join(', ', $missing)));

		return $result;
	}

	/**
	 * Filter the $data array by removing everything that does not have a key in the permit array
	 * @param  array  $permit array of permitted keys
	 * @param  array  $data
	 * @return array
	 */
	public static function permit(array $permit = array(), array $data = array())
	{
		return Jam_Validator_Attributes::factory($permit)->data($data)->clean();
	}

	public static function form($model, $class = NULL)
	{
		if ($class === NULL)
		{
			$class = Kohana::$config->load('jam.default_form');
		}

		$class = Jam::capitalize_class_name($class);

		if (is_string($model))
		{
			$model = Jam::build($model);
		}

		if (class_exists(Jam::$_form_prefix.$class))
		{
			$class = Jam::$_form_prefix.$class;
		}

		return new $class($model);
	}

	public static function build_template($model_name, array $values = NULL)
	{
		$meta = Jam::meta($model_name);

		$model_name = ($meta AND $meta->polymorphic_key()) ? Arr::get($values, $meta->polymorphic_key(), $model_name) : $model_name;

		if ( ! isset(Jam::$_build_templates[$model_name]))
		{
			Jam::$_build_templates[$model_name] = Jam::build($model_name);
		}

		return Jam::$_build_templates[$model_name];
	}

} // End Kohana_Jam
