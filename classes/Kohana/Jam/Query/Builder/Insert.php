<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * A class to create queries for deleting jam models from the database
 *
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Query_Builder_Insert extends Database_Query_Builder_Insert {

	/**
	 * Create instance of Jam_Query_Builder_Insert
	 *
	 * @param  string $model
	 * @return Jam_Query_Builder_Insert
	 */
	public static function factory($model, array $columns = array())
	{
		return new Jam_Query_Builder_Insert($model, $columns);
	}

	/**
	 * @var  Jam_Meta The meta object that is attached to this builder
	 */
	protected $_meta = NULL;

	/**
	 * User defined values for the builder
	 *
	 * @var array
	 */
	protected $_params = array();

	/**
	 * Constructs a new Jam_Builder instance.
	 *
	 * $model is not actually allowed to be NULL. It has
	 * a default because PHP throws strict errors otherwise.
	 *
	 * @throws  Kohana_Exception
	 * @param   string  $model
	 * @param   array   $columns
	 */
	public function __construct($model, array $columns = array())
	{
		$this->_meta = Jam::meta($model);

		parent::__construct($this->meta()->table(), $columns);

		$this->meta()->events()->trigger('builder.after_construct', $this);
	}

	public function compile($db = NULL)
	{
		if ($db === NULL AND $this->meta())
		{
			$db = Database::instance($this->meta()->db());
		}

		$this->meta()->events()->trigger('builder.before_insert', $this);

		$result = parent::compile($db);

		$this->meta()->events()->trigger('builder.after_insert', $this);

		return $result;
	}

	public function execute($db = NULL, $as_object = NULL, $object_params = NULL)
	{
		if ($db === NULL AND $this->meta())
		{
			$db = Database::instance($this->meta()->db());
		}

		return parent::execute($db, $as_object, $object_params);
	}

	/**
	 * Get the Jam_Meta instance attached to the builder
	 *
	 * @return Jam_Meta
	 */
	public function meta()
	{
		return $this->_meta;
	}

	/**
	 * Pass unknown methods to the behaviors.
	 *
	 * @param   string  $method
	 * @param   array   $args
	 * @return  mixed
	 */
	public function __call($method, $args)
	{
		$return = $this->meta()
			->events()
				->trigger_callback('builder', $this, $method, $args);

		return ($return !== NULL) ? $return : $this;
	}

	/**
	 * Get/Set the params used to store arbitrary values for the behaviors
	 *
	 * @param  array|string $params
	 * @param  mixed $param
	 * @return Jam_Query_Builder_Insert|mixed $this when setting; mixed when getting
	 */
	public function params($params = NULL, $param = NULL)
	{
		if (is_array($params))
		{
			$this->_params = Arr::merge($this->_params, $params);

			return $this;
		}

		if ($param !== NULL)
			return $this->params(array($params => $param));

		if (is_string($params))
			return Arr::get($this->_params, $params);

		return $this->_params;
	}

	/**
	 * Get the compiled SQL
	 * or a text representation of exceptions occured during compilation.
	 * @return string
	 */
	public function __toString()
	{
		try
		{
			// Return the SQL string
			return $this->compile();
		}
		catch (Exception $e)
		{
			return Kohana_Exception::text($e);
		}
	}
}
