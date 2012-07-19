<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Jam Errors
 * 
 * @package    Jam
 * @category   Model
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 OpenBuildings Inc.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Errors implements Countable, SeekableIterator, ArrayAccess {

	public static function message($attribute, $error, $params)
	{
		if ($message = Kohana::message($this->_error_filename, "{$attribute}.{$error}"))
		{
			$converted_params = array();
			foreach ($params as $name => $value) 
			{
				$converted_params[':'.$name] = $value;
			}

			$message = strtr($message, $converted_params);
		}	
		elseif ($message = Kohana::message('validators', $error))
		{

		}
		else
		{
			$message = $this->_error_filename."{$attribute}.{$error}";
		}



		return $message;
	}

	/**
	 * @var  Jam_Meta  The current meta object, based on the model we're returning
	 */
	protected $_meta = NULL;

	/**
	 * @var  Jam_Model  The current class we're placing results into
	 */
	protected $_model = NULL;

	/**
	 * @var  string
	 */
	protected $_error_filename = NULL;

	private $_container = array();
	private $_current;

	/**
	 * Tracks a database result
	 *
	 * @param  mixed  $result
	 * @param  mixed  $model
	 */
	public function __construct(Jam_Model $model, $error_filename)
	{
		$this->_model = $model;
		$this->_error_filename = $error_filename;
	}

	public function as_array()
	{
		return $this->_container;
	}

	public function add($attribute, $error, array $params = array())
	{
		if ( ! isset($this->_container[$attribute]))
		{
			$this->_container[$attribute] = array();
		}

		$this->_container[$attribute][$error] = $params;

		return $this;
	}

	public function messages($attribute = NULL)
	{
		$messages = array();

		$params = array(
			'field'
		);

		if ($attribute !== NULL)
		{
			if (empty($this->_container[$attribute]))
				return array();

			foreach ($this->_container[$attribute] as $error => $params) 
			{
				$messages[] = Jam_Errors::message($attribute, $error, Arr::merge($params, array('model' => $this->_model->meta()->model(), 'field' => $attribute)));
			}

			return $messages;
		}

		foreach ($this->_container as $attribute => $errors) 
		{
			$messages[$attribute] = $this->messages($attribute);
		}

		return $messages;
	}
		

	public function seek($offset)
	{
		if ($this->offsetExists($offset))
		{
			$this->_current = $offset;

			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	public function offsetSet($offset, $value) 
	{
		throw new Kohana_Exception('Cannot set the errors directly, must use add() method');
	}

	public function offsetExists($offset) 
	{
	 return isset($this->_container[$offset]);
	}

	public function offsetUnset($offset)
	{
		unset($this->_container[$offset]);
		if ($this->_current == $offset)
		{
			$this->rewind();
		}
	}

	public function offsetGet($offset) 
	{
		return isset($this->_container[$offset]) ? $this->_container[$offset] : NULL;
	}

	public function rewind() 
	{
		reset($this->_container);
		$this->_current = key($this->_container);
	}

	public function current() 
	{
		return $this->offsetGet($this->_current);
	}

	public function key() 
	{
		return $this->_current;
	}

	public function next() 
	{
		next($this->_container);
		$this->_current = key($this->_container);
		return $this->current();
	}

	public function prev()
	{
		prev($this->_container);
		$this->_current = key($this->_container);
		return $this->current();
	}

	public function valid() 
	{
		return isset($this->_container[$this->_current]);
	}

	public function count() 
	{
		return count($this->_container);
	}
} // End Kohana_Jam_Collection