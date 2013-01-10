<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Core class that all associations must extend
 *
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Query_Builder_Insert extends Database_Query_Builder_Insert {

	public static function factory($model)
	{
		return new Jam_Query_Builder_Insert($model);
	}

	/**
	 * @var  Jam_Meta  The meta object (if found) that is attached to this builder
	 */
	protected $_meta = NULL;

	/**
	 * A store for user defined values for the builder
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
	 * @param   string|null  $model
	 * @param   mixed|null   $key
	 */
	public function __construct($model = NULL)
	{
		parent::__construct();

		if ( ! $model)
		{
			throw new Kohana_Exception('Jam_Query_Builder_Insert requires model to be set in the constructor');
		}

		$this->_meta = Jam::meta($model);

		$this->meta()->events()->trigger('builder.after_construct', $this);
	}

	public function compile(Database $db)
	{
		$this->_table = $this->meta()->table();

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

	public function meta()
	{
		return $this->_meta;
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
		$return = $this->_meta->events()->trigger_callback('builder', $this, $method, $args);
		return $return ? $return : $this;
	}

	/**
	 * Getter/setter for the params array used to store arbitrary values by the behaviors
	 * 
	 * @param  array|string $params 
	 * @param  mixed $param  
	 * @return Jam_Builder         $this
	 */
	public function params($params = NULL, $param = NULL)
	{
		// Accept params('name', 'param');
		if ($param !== NULL)
		{
			$params = array($params => $param);
		}

		if (is_array($params))
		{
			$this->_params = Arr::merge($params, $this->_params);
			return $this;
		}

		if (is_string($params))
		{
			return Arr::get($this->_params, $params);
		}

		return $this->_params;
	}

}