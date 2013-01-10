<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Represents the changed data of a Jam_Collection. The same as Database_Result, but allow to change its contents
 * 
 * @package    Jam
 * @category   Collection
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jam_Query_Builder_Dynamic_Result extends Database_Result_Cached {

	protected $_changed;
	protected $_original;

	public function __construct(array $result, $sql, $as_object = NULL)
	{
		parent::__construct($result, $sql, $as_object);
		$this->_total_rows = count($result);

		$this->_original = $result;
		$this->changed(FALSE);
	}

	public function force_offsetSet($offset, $value, $is_changed = TRUE) 
	{
		if (is_numeric($offset)) 
		{
			if ( ! isset($this->_result[$offset]))
				throw new Kohana_Exception('Must be a valid offset');
				
			$this->_result[$offset] = $value;
			if ($is_changed)
			{
				$this->_changed[$offset] = TRUE;
			}
		} 
		else 
		{
			$this->_result[] = $value;
			if ($is_changed)
			{
				$this->_changed[] = TRUE;
			}
		}
		
		$this->_total_rows = count($this->_result);
	}
	
	public function changed($offset = NULL, $value = NULL)
	{
		if ($offset === NULL)
			return (bool) array_filter($this->_changed);

		if (is_array($offset))
		{
			$this->_changed = $offset;
		}
		elseif (is_bool($offset))
		{
			$this->_changed = array_fill(0, $this->_total_rows, $offset);
		}
		else
		{
			if ($value === NULL)
				return Arr::get($this->_changed, $offset);
	
			$this->_changed[$offset] = $value;
		}
	
		return $this;
	}

	public function force_offsetUnset($offset)
	{
		array_splice($this->_result, $offset, 1);
		array_splice($this->_changed, $offset, 1);

		if ($this->_current_row === $offset)
		{
			$this->rewind();
		}

		$this->_total_rows = count($this->_result);
	}

}
