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
class Kohana_Jam_Extension_Touch extends Jam_Extension {

	public function initialize(Jam_Attribute $attribute)
	{
		if ($attribute instanceof Jam_Association_Collection)
		{
			$attribute->bind('after.after_save', array($this, 'touch_collection'));
		}
		else
		{
			$attribute->bind('after.after_save', array($this, 'touch_model'));	
		}
	}

	public function touch_collection(Jam_Attribute $attribute, Jam_Event_Data $data, Jam_Model $model)
	{
		foreach ($model->{$attribute->name} as $item) 
		{								
			$item->_touch_if_untouched($model, $attribute->touch);
		}	
	}

	public function touch_model(Jam_Attribute $attribute, Jam_Event_Data $data, Jam_Model $model)
	{
		$item = $model->{$attribute->name};

		if ($item instanceof Jam_Model AND $item->loaded())
		{
			$item->_touch_if_untouched($model, $attribute->touch);
		}
	}
}
