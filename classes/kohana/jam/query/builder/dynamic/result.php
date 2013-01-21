<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Represents the changed data of a Jam_Query_Builder_Collection. 
 * The same as Database_Result, but allow to change its contents
 * 
 * @package    Jam
 * @category   Collection
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jam_Query_Builder_Dynamic_Result extends Database_Result_Cached implements Serializable{

	/**
	 * An array holding information which fields have been changed
	 * @var array
	 */
	protected $_changed;

	/**
	 * A boolean showing if there have been any removed items
	 * @var boolean
	 */
	protected $_removed_items = FALSE;

	public function __construct(array $result, $sql, $as_object = NULL)
	{
		parent::__construct($result, $sql, $as_object);
		$this->_total_rows = count($result);

		$this->changed(FALSE);
	}

	/**
	 * Set the new value for the offset. By Default set the associated entry in the changed array to TRUE
	 * Set the last argument to FALSE to not do that.
	 * 
	 * @param  integer  $offset     
	 * @param  mixed    $value      
	 * @param  boolean $is_changed 
	 */
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
	
	/**
	 * Getter / setter of the changed array
	 * 
	 * @param  integer $offset 
	 * @param  boolean   $value  
	 * @return mixed
	 */
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

	/**
	 * Remove the entry from the results and from the changed array
	 * @param  integer $offset 
	 */
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

	/**
	 * Get the total rows count
	 * @return integer 
	 */
	public function total_rows()
	{
		return $this->total_rows;
	}

	/**
	 * Implement serializable behavior
	 * @return string
	 */
	public function serialize()
	{
		return serialize(array(
			'result' => $this->_result,
			'changed' => $this->_changed,
		));
	}

	/**
	 * Implement serializable behavior
	 * @param  string $data 
	 */
	public function unserialize($data)
	{
		$data = unserialize($data);

		$this->_result = $data['result'];
		$this->_changed = $data['changed'];
		$this->_total_rows = count($this->_result);
	}
}
