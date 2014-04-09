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
class Kohana_Jam_Validator_Rule_Unique extends Jam_Validator_Rule {

	public $scope;

	public function validate(Jam_Validated $model, $attribute, $value)
	{
		// According to the SQL standard NULL is not checked by the unique constraint
		// We also skip this test if the value is the same as the default value
		if ($value !== $model->meta()->defaults($attribute))
		{
			// Build query
			$query = Jam::all($model)->where($attribute, '=', $value);

			if ($this->scope)
			{
				foreach ( (array) $this->scope as $scope_attribute)
				{
					$query->where($scope_attribute, '=', $model->$scope_attribute);
				}
			}

			$query->limit(1);

			if ($query->count() AND ( ! $model->loaded() OR $query[0]->id() !== $model->id()))
			{
				// Add error if duplicate found
				$model->errors()->add($attribute, 'unique');
			}
		}
	}
}
