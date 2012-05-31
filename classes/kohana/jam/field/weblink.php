<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Handles web links.
 *
 * @package    Jam
 * @category   Fields
 * @author     Ivan Kerin
 * @copyright  (c) 2012 OpenBuildings Inc.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jam_Field_Weblink extends Jam_Field_String {

	public $rules = array(array(
		'Valid::url'
	));

	/**
	 * Add http:// if it's not present upon save.
	 *
	 * @param  Jam_Model $model
	 * @param  string $value
	 * @param  boolean $loaded
	 * @return string the new url
	 */
	public function set($value)
	{
		if ($value AND ! preg_match('|^http(s)?://|i', $value))
		{
			$value = "http://".$value;
		}

		return parent::set($value);
	}

} // End Jam_Field_Weblink
