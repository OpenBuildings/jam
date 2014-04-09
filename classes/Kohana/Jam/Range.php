<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * This class is what the upload field accually returns
 * and has all the nesessary info and manipulation abilities to save / delete / validate itself
 *
 * @package    Jam
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
*/
class Kohana_Jam_Range implements ArrayAccess, Serializable {

	protected $_min;

	protected $_max;

	protected $_format = ":min - :max";

	public function format($format = NULL)
	{
		if ($format !== NULL)
		{
			$this->_format = $format;
			return $this;
		}
		return $this->_format;
	}

	public static function sum(array $ranges, $format = NULL)
	{
		$min = 0;
		$max = 0;

		foreach ($ranges as $range)
		{
			$min += $range->min();
			$max += $range->max();
		}

		return new Jam_Range(array($min, $max), $format);
	}

	public static function merge(array $ranges, $format = NULL)
	{
		$min = 0;
		$max = 0;

		foreach ($ranges as $range)
		{
			$min = max($min, $range->min());
			$max = max($max, $range->max());
		}

		return new Jam_Range(array($min, $max), $format);
	}

	public function __construct($source = NULL, $format = NULL)
	{
		if (is_string($source))
		{
			$source = explode('|', $source);
		}

		if (is_array($source))
		{
			$this->min($source[0]);
			$this->max($source[1]);
		}
		elseif ($source instanceof Jam_Range)
		{
			$this->min($source->min());
			$this->max($source->max());
		}

		$this->format($format);
	}

	public function min($min = NULL)
	{
		if ($min !== NULL)
		{
			$this->_min = $min;
			return $this;
		}
		return $this->_min;
	}

	public function max($max = NULL)
	{
		if ($max !== NULL)
		{
			$this->_max = $max;
			return $this;
		}
		return $this->_max;
	}

	public function add(Jam_Range $addition)
	{
		return Jam_Range::sum(array($this, $addition), $this->format());
	}

	public function offsetSet($offset, $value)
	{
		if (is_null($offset))
			throw new Kohana_Exception('Cannot add new values to range');

		if ($offset == 0)
		{
			$this->min($value);
		}
		elseif ($offset == 1)
		{
			$this->max($value);
		}
		else
		{
			throw new Kohana_Exception('Use offset 0 for min and offset 1 for max, offset :offset not supported', array(':offset' => $offset));
		}
	}

	public function offsetExists($offset)
	{
		return ($offset == 0 OR $offset == 1);
	}

	public function offsetUnset($offset)
	{
		throw new Kohana_Exception('Cannot unset range object');
	}

	public function offsetGet($offset)
	{
		if ( ! $this->offsetExists($offset))
			throw new Kohana_Exception('Use offset 0 for min and offset 1 for max, offset :offset not supported', array(':offset' => $offset));

		return $offset ? $this->max() : $this->min();
	}

	public function __toString()
	{
		return $this->min().'|'.$this->max();
	}

	public function humanize()
	{
		if (is_callable($this->format()))
		{
			return call_user_func($this->format(), $this->min(), $this->max());
		}
		else
		{
			return strtr($this->format(), array(':min' => $this->min(), ':max' => $this->max()));
		}
	}

	public function as_array()
	{
		return array($this->min(), $this->max());
	}

	public function serialize()
	{
		return $this->__toString();
	}

	public function unserialize($data)
	{
		list($min, $max) = explode('|', $data);

		$this->min($min);
		$this->max($max);
	}
}
