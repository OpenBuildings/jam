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
class Kohana_Jam_Validator_Rule_Confirmed extends Jam_Validator_Rule {

	public $confirmation;

	public function validate(Jam_Validated $model, $attribute, $value)
	{
		$confirmation = $this->confirmation ? $this->confirmation : $attribute.'_confirmation';

		if ($model->$confirmation AND $value !== $model->$confirmation)
		{
			$model->errors()->add($attribute, 'confirmed', array(':confirmation' => $confirmation));
		}
	}
}
