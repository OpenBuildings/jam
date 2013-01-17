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
class Kohana_Jam_Query_Builder_Dynamic_Result extends Database_Result_Cached implements Serializable{

	protected $_changed;
	protected $_removed_items = FALSE;

	public function __construct(array $result, $sql, $as_object = NULL)
	{
		parent::__construct($result, $sql, $as_object);
		$this->_total_rows = count($result);

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
			return ( (bool) array_filter($this->_changed) OR $this->_removed_items);

		if (is_array($offset))
		{
			$this->_changed = $offset;
		}
		elseif (is_bool($offset))
		{
			$this->_changed = $this->_total_rows ? array_fill(0, $this->_total_rows, $offset) : array();
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

		$this->_removed_items = TRUE;

		if ($this->_current_row === $offset)
		{
			$this->rewind();
		}

		$this->_total_rows = count($this->_result);
	}

	public function total_rows()
	{
		return $this->total_rows;
	}

	public function serialize()
	{
		return serialize(array(
			'result' => $this->_result,
			'changed' => $this->_changed,
		));
	}

	public function unserialize($data)
	{
		$data = unserialize($data);

		$this->_result = $data['result'];
		$this->_changed = $data['changed'];
		$this->_total_rows = count($this->_result);
	}
}
