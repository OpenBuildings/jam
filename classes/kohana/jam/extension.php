<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Jam Extension
 *
 * A generic extension class used for extending fields, associations and attributes
 *
 * @package    Jam
 * @category   Extensions
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 OpenBuildings Inc.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Extension {

	/**
	 * Initialize options as object attributes
	 * 
	 * @param array $options [description]
	 */
	public function __construct($options = array())
	{
		foreach ($options as $key => $option)
		{
			$this->$key = $option;
		}
	}
	

	abstract public function initialize(Jam_Attribute $attribute);

} // End Kohana_Jam_Behavior
