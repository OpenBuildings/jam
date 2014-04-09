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
class Kohana_Jam_Validator_Rule_Accepted extends Jam_Validator_Rule {

	public $accept = TRUE;

	public function validate(Jam_Validated $model, $attribute, $value)
	{
		if ($value != $this->accept)
		{
			$model->errors()->add($attribute, 'accepted', array(':accept' => $this->accept));
		}
	}
}
