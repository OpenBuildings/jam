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
class Kohana_Jam_Extension_General extends Jam_Extension {

	public function initialize(Jam_Attribute $association)
	{
		if ($association->conditions)
		{
			$association->bind('after.join', array($this, 'apply_conditions'));
			$association->bind('after.builder', array($this, 'apply_conditions'));
		}

		if ($association->extend)
		{
			$association->bind('after.builder', array($this, 'extend_builder'));
		}

	}

	/**
	 * Apply the conditions array of this association to a builder
	 * @param  Jam_Builder $builder 
	 * @return Jam_Builder
	 */
	public function apply_conditions(Jam_Association $association, Jam_Event_Data $data)
	{
		$builder = end($data->args);

		if ($association->conditions)
		{
			foreach ($association->conditions as $type => $args) 
			{
				call_user_func_array(array($builder, $type), $args);		
			}
		}
	}

	public function extend_builder(Jam_Association $associaiton, Jam_Event_Data $data)
	{
		$builder = end($data->args);

		$builder->extend($associaiton->extend);
	}
}
