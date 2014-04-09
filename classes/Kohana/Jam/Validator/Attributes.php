<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * @package    Jam
 * @category   Query/Result
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Validator_Attributes {

	public static function factory(array $permit)
	{
		return new Jam_Validator_Attributes($permit);
	}

	private static function _clean(array $data, array $permitted)
	{
		$cleaned = array();

		foreach ($data as $name => $value)
		{

			if (is_numeric($name) AND is_array($value))
			{
				// Handle arrays of arrays for manytomany / hasmany associations
				$cleaned[$name] = Jam_Validator_Attributes::_clean($value, $permitted);
			}
			elseif (array_key_exists($name, $permitted))
			{
				// Handle single arrays (objects for belongsto / hasone associations)
				$cleaned[$name] = is_array($value) ? Jam_Validator_Attributes::_clean($value, Arr::get($permitted, $name, array())) : $value;
			}
			elseif (in_array($name, $permitted))
			{
				// Normal fields
				$cleaned[$name] = $value;
			}
		}

		return $cleaned;
	}

	/**
	 * @var  array
	 */
	protected $_data = array();
	protected $_permit = array();

	/**
	 * @param  array  $data
	 */
	public function __construct($permit)
	{
		$this->permit($permit);
	}

	/**
	 * Returns a string representation of the collection.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return get_class($this).': '.Jam::model_name($this->_model).' ('.$this->count().')';
	}


	public function permit($permit = NULL)
	{
		if ($permit !== NULL)
		{
			$this->_permit = (array) $permit;
			return $this;
		}
		return $this->_permit;
	}

	public function clean()
	{
		return Jam_Validator_Attributes::_clean($this->data(), $this->permit());
	}

	public function data($data = NULL)
	{
		if ($data !== NULL)
		{
			$this->_data = $data;
			return $this;
		}
		return $this->_data;
	}
}
