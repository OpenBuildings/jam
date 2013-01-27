<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Handles web links.
 *
 * @package    Jam
 * @category   Fields
 * @author     Ivan Kerin
 * @copyright  (c) 2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jam_Field_Weblink extends Jam_Field_String {

	/**
	 * Add http:// if it's not present upon save.
	 *
	 * @param  Jam_Model $model
	 * @param  string $value
	 * @param  boolean $loaded
	 * @return string the new url
	 */
	public function set(Jam_Validated $model, $value, $is_changed)
	{
		if ($value AND ! preg_match('|^http(s)?://|i', $value))
		{
			$value = "http://".$value;
		}

		return parent::set($model, $value, $is_changed);
	}

} // End Jam_Field_Weblink
