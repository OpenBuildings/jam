<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Handles passwords
 *
 * Passwords are automatically hashed before they're
 * saved to the database.
 *
 * It is important to note that a new password is hashed in a validation
 * callback. This gives you a chance to validate the password, and have it
 * be hashed after validation.
 *
 * @package    Jam
 * @category   Fields
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Field_Password extends Jam_Field_String {

	/**
	 * @var  callback  a valid callback to use for hashing the password or FALSE to not hash
	 */
	public $hash_with = 'sha1';

	public $allow_null = TRUE;

	/**
	 * Hash and set the password to the database
	 * @param  Jam_Model $model
	 * @param  string      $value  plain password
	 * @param  bool        $loaded
	 * @return string
	 */
	public function convert(Jam_Validated $model, $value, $is_loaded)
	{
		$hashed = call_user_func($this->hash_with, $value);

		// Do not re hash passwords hashed passwords or empty values
		return (strlen($value) === strlen($hashed) OR ! $value) ? $value : $hashed;
	}

} // End Kohana_Jam_Field_Password
