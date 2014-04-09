<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Jam Validatior Rule
 *
 * @package    Jam
 * @category   Validation
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jam_Validator_Rule_Present extends Jam_Validator_Rule {

	public $validate_empty = TRUE;

	public $allow_zero = FALSE;

	public function validate(Jam_Validated $model, $attribute, $value)
	{
		if (Jam_Validator_Rule_Present::is_empty_value($value, $this->allow_zero)
			OR Jam_Validator_Rule_Present::is_empty_string($value)
			OR Jam_Validator_Rule_Present::is_empty_countable($value)
			OR Jam_Validator_Rule_Present::is_empty_upload_file($value))
		{
			$model->errors()->add($attribute, 'present');
		}
	}

	public static function is_empty_value($value, $allow_zero = FALSE)
	{
		return $allow_zero ? ( ! is_numeric($value) AND ! $value) : ! $value;
	}

	public static function is_empty_countable($value)
	{
		return ($value instanceof Countable AND ! count($value));
	}

	public static function is_empty_upload_file($value)
	{
		return (($value instanceof Upload_File) AND $value->is_empty());
	}

	public static function is_empty_string($value)
	{
		return (is_string($value) AND strlen(trim($value)) === 0);
	}

	public function html5_validation()
	{
		return array('required' => TRUE);
	}
}
