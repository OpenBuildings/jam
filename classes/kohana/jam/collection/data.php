<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Represents the changed data of a Jam_Collection. The same as Database_Result, but allow to change its contents
 * 
 * @package    Jam
 * @category   Collection
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 OpenBuildings Inc.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jam_Collection_Data implements ArrayAccess, Iterator, Countable, SeekableIterator {

	private $_container = array();
	private $_current;

	public function __construct(array $container = NULL) 
	{
		$this->_container = $container;
		$this->_current = key($this->_container);
	}

	public function as_array($key = NULL, $value = NULL)
	{
		$results = array();

		if ($key === NULL AND $value === NULL)
		{
			$results = $this->_container;
		}
		elseif ($key === NULL)
		{
			// Indexed columns
			foreach ($this as $row)
			{
				$row_value = is_object($row) ? $row->$value : $row[$value];
				$results[] = $row_value;
			}
		}
		elseif ($value === NULL)
		{
			// Associative rows
			foreach ($this as $row)
			{
				$row_name = is_object($row) ? $row->$key : $row[$key];
				$results[$row_name] = $row;
			}
		}
		else
		{
			// Associative columns
			foreach ($this as $row)
			{
				$row_name = is_object($row) ? $row->$key : $row[$key];
				$row_value = is_object($row) ? $row->$value : $row[$value];
				$results[$row_name] = $row_value;
			}
		}

		$this->rewind();

		return $results;
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
		if (is_numeric($offset)) 
		{
			$this->_container[$offset] = $value;
		} 
		else 
		{
			$this->_container[] = $value;
		}
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

}
