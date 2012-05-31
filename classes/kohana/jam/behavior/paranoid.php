<?php

class Kohana_Jam_Behavior_Paranoid extends Jam_Behavior
{
	const ALL = 'all';
	const DELETED = 'deleted';
	const NORMAL = 'normal';

	protected $_field = 'is_deleted';

	public function initialize(Jam_Event $event, $model, $name) 
	{
		parent::initialize($event, $model, $name);  

		Jam::meta($model)
			->field($this->_field, Jam::field('boolean', array('default' => FALSE)));
	}

	public function builder_before_select(Jam_Builder $builder, Jam_Event_Data $data)
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

	public function builder_call_deleted(Jam_Builder $builder, Jam_Event_Data $data, $paranoid_type = Jam_Behavior_Paranoid::NORMAL)
	{
		if ( ! in_array($paranoid_type, array(Jam_Behavior_Paranoid::DELETED, Jam_Behavior_Paranoid::NORMAL, Jam_Behavior_Paranoid::ALL)))
			throw new Kohana_Exception("Deleted type should be Jam_Behavior_Paranoid::DELETED, Jam_Behavior_Paranoid::NORMAL or Jam_Behavior_Paranoid::ALL");

		$builder->params('paranoid_type', $paranoid_type);
	}

	public function model_before_delete(Jam_Model $model, Jam_Event_Data $data)
	{
		if ( ! $model->_real_delete)
		{
			$data->stop = TRUE;

			foreach ($model->meta()->associations() as $association)
			{
				$association->delete($model, $model->id());
			}

			Jam::query($model->meta()->model())->value($this->_field, TRUE)->key($model->id())->update();

			$data->return = FALSE;
		}
	}

	public function model_call_real_delete(Jam_Model $model, Jam_Event_Data $data)
	{
		$model->_real_delete = TRUE;
		$data->stop = TRUE;
		$data->return = $model->delete();
	}

	public function model_call_restore_delete(Jam_Model $model, Jam_Event_Data $data)
	{
		Jam::query($model->meta()->model())->value($this->_field, FALSE)->key($model->id())->update();
	}
}