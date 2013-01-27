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

	public function validate(Jam_Validated $model, $attribute, $value)
	{
		if ( 
			! $value
			OR (is_string($value) AND ! trim($value))
			OR (($value instanceof Jam_Query_Buidler_Collection) AND ! count($value))
			OR (($value instanceof Upload_File) AND $value->is_empty())
		)
		{
			$model->errors()->add($attribute, 'present');	
		}
	}
}