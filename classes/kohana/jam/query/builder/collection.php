<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Query_Builder_Collection extends Jam_Query_Builder_Select implements Countable{

	protected $_result;
	protected $_model;

	public function result(Database_Result $result = NULL)
	{
		if ($result !== NULL)
		{
			$this->_result = $result;
		}

		if ( ! $this->_result)
		{
			$this->_result = $this->execute();
		}

		return $this->_result;
	}

	public function model()
	{
		if ( ! $this->_model)
		{
			$this->_model = Jam::factory($this->meta()->model());
		}
		return $this->_model;
	}

	protected function _load_model($value)
	{
		if ( ! $value)
			return NULL;

		$model = clone $this->model();
		$model = $model->load_fields($value);

		return $model;
	}

	public function count()
	{
		return $this->result()->count();
	}

	public function offsetGet($offset)
	{
		$value = $this->result()->offsetGet($offset);
		if ( ! $value)
			return NULL;

		return $this->_load_model($value);
	}

	public function offsetExists($offset)
	{
		return $this->result()->offsetExists($offset);
	}

	public function offsetSet($offset, $value)
	{
		throw new Kohana_Exception('Database results are read-only');
	}

	public function offsetUnset($offset)
	{
		throw new Kohana_Exception('Database results are read-only');
	}

	public function as_array($key = NULL, $value = NULL)
	{
		$key = Jam_Query_Builder::resolve_meta_attribute($key, $this->meta());
		if ($value === NULL)
		{
			return array_map(array($this, '_load_model'), $this->result()->as_array($key));
		}
		else
		{
			$value = Jam_Query_Builder::resolve_meta_attribute($value, $this->meta());
			return $this->result()->as_array($key, $value);
		}
	}

	public function ids()
	{
		return $this->as_array(NULL, ':primary_key');
	}

} // End Kohana_Jam_Association