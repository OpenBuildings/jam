<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Jam Validatior Rule
 *
 * @package    Jam
 * @category   Validation
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 OpenBuildings Inc.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jam_Validator_Rule_Present extends Jam_Validator_Rule {

	public $allow_null = FALSE;

	public function validate(Jam_Model $model, $attribute, $value)
	{
		if ( ! $value OR (($value instanceof Jam_Model) AND $value->loaded()) OR (($value instanceof Jam_Collection) AND ! count($value)))
		{
			$model->errors()->add($attribute, 'present');	
		}
	}
}