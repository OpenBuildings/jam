<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Handles belongs to relationships
 *
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2010-2011 OpenBuildings
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jam_Extension_Filters extends Jam_Extension {

	public function initialize(Jam_Attribute $attribute)
	{
		$attribute->bind('after.set', array($this, 'run_filters'));
	}

	/**
	 * Apply the conditions array of this association to a builder
	 * @param  Jam_Builder $builder 
	 * @return Jam_Builder
	 */
	public function run_filters(Jam_Association $attribute, Jam_Event_Data $data, $model, $original_value, $value)
	{
		$bound = array(
			':model' => $model, 
			':field' => $attribute->name, 
		);

		foreach ($attribute->filters as $array) 
		{
			$bound[':value'] = $value;
			$filter = $array[0];
			$params = Arr::get($array, 1, array(':value'));

			foreach ($params as $key => $param)
			{
				if (is_string($param) AND array_key_exists($param, $bound))
				{
					// Replace with bound value
					$params[$key] = $bound[$param];
				}
			}

			$value = call_user_func_array($filter, $params);
		}

		return $value;
	}
}
