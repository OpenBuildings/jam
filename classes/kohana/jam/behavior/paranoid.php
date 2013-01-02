<?php

/**
 * Implementation of the paranoid behavior, so when you delete soemthing it does not dissapear but is set with a flag is_deleted
 * 
 * @package    Jam
 * @category   Behavior
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jam_Behavior_Paranoid extends Jam_Behavior
{
	const ALL = 'all';
	const DELETED = 'deleted';
	const NORMAL = 'normal';

	/**
	 * The field used for checking if an item is deleated
	 * 
	 * @var string
	 */
	protected $_field = 'is_deleted';

	public function initialize(Jam_Meta $meta, $name) 
	{
		parent::initialize($meta);  

		$meta
			->field($this->_field, Jam::field('boolean', array('default' => FALSE)));

		$meta->event()
			->bind('builder.before_select', $this->builder_paranoid_filter)
			->bind('builder.before_delete', $this->builder_paranoid_filter)
			->bind('builder.before_update', $this->builder_paranoid_filter);

	}

	/**
	 * Perform the actual where modification when it is needed
	 * 
	 * @param Database_Builder    $builder 
	 */
	public function builder_paranoid_filter(Database_Builder $builder)
	{

		switch ($builder->params('paranoid_type'))
		{
			case Jam_Behavior_Paranoid::ALL:
			break;

			case Jam_Behavior_Paranoid::DELETED:
				$builder->where($this->_field, '=', TRUE);
			break;

			case Jam_Behavior_Paranoid::NORMAL:
			default:
				$builder->where($this->_field, '=', FALSE);
			break;
		}
	}

	/**
	 * $builder->deleted(Jam_Behavior_Paranoid::ALL), 
	 * $builder->deleted(Jam_Behavior_Paranoid::DELETED), 
	 * $builder->deleted(Jam_Behavior_Paranoid::NORMAL)
	 * 
	 * @param Jam_Builder    $builder       
	 * @param Jam_Event_Data $data          
	 * @param string         $paranoid_type 
	 */
	public function builder_call_deleted(Jam_Builder $builder, Jam_Event_Data $data, $paranoid_type = Jam_Behavior_Paranoid::NORMAL)
	{
		if ( ! in_array($paranoid_type, array(Jam_Behavior_Paranoid::DELETED, Jam_Behavior_Paranoid::NORMAL, Jam_Behavior_Paranoid::ALL)))
			throw new Kohana_Exception("Deleted type should be Jam_Behavior_Paranoid::DELETED, Jam_Behavior_Paranoid::NORMAL or Jam_Behavior_Paranoid::ALL");

		$builder->params('paranoid_type', $paranoid_type);
	}

	/**
	 * $model->delete() Delete the item only if the "real delete" flag has been set to TRUE, otherwise set the 'is_deleted' column to TRUE
	 * 
	 * @param Jam_Model      $model
	 * @param Jam_Event_Data $data 
	 */
	public function model_before_delete(Jam_Model $model, Jam_Event_Data $data)
	{
		if ( ! $model->_real_delete)
		{
			foreach ($model->meta()->associations() as $association)
			{
				$association->delete($model, $model->id());
			}

			Jam::update($this->_model, $model->id())->value($this->_field, TRUE)->execute();

			$data->return = FALSE;
		}
	}

	/**
	 * $model->real_delte() Set the flag 'real_delete' to true and perform the deletion
	 * 
	 * @param Jam_Model      $model
	 * @param Jam_Event_Data $data 
	 */
	public function model_call_real_delete(Jam_Model $model, Jam_Event_Data $data)
	{
		$model->_real_delete = TRUE;
		$data->stop = TRUE;
		$data->return = $model->delete();
	}

	/**
	 * $model->restore_delete() Perform this to "undelete" a model
	 * 
	 * @param Jam_Model      $model 
	 * @param Jam_Event_Data $data  
	 */
	public function model_call_restore_delete(Jam_Model $model)
	{
		Jam::update($this->_model, $model->id())->value($this->_field, FALSE)->execute();
	}
}