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

	public function force_offsetSet($offset, $value) 
	{
		if (is_numeric($offset)) 
		{
			if ( ! isset($this->_result[$offset]))
				throw new Kohana_Exception('Must be a valid offset');
				
			$this->_result[$offset] = $value;
		} 
		else 
		{
			$this->_result[] = $value;
		}
		
		$this->_total_rows = count($this->_result);
	}

	public function force_offsetUnset($offset)
	{
		array_splice($this->_result, $offset, 1);

		if ($this->_current_row === $offset)
		{
			$this->rewind();
		}

		$this->_total_rows = count($this->_result);
	}

}
