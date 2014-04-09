<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Jam
 * @category   Behavior
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jam_Behavior_Tokenable extends Jam_Behavior {

	protected $_field = 'token';

	protected $_field_options = array();

	protected $_uppercase = FALSE;

	protected $_token_function = 'Jam_Behavior_Tokenable::generate_token';

	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$meta->field($this->_field, Jam::field('string', $this->_field_options));
	}

	public function model_before_create(Jam_Model $model)
	{
		if ( ! $model->{$this->_field})
		{
			$model->update_token();
		}
	}

	public static function generate_token()
	{
		return base_convert(rand(1, 1000000000), 10, 36);
	}

	public function new_token()
	{
		$token = call_user_func($this->_token_function);

		if ($this->_uppercase)
		{
			$token = strtoupper($token);
		}

		return $token;
	}

	public function model_call_update_token(Jam_Model $model, Jam_Event_Data $data)
	{
		do
		{
			$model->{$this->_field} = $this->new_token();
		}
		while (Jam::all($model->meta()->model())->where($this->_field, '=', $model->{$this->_field})->count_all() > 0);

		$data->return = $model;
	}

	/**
	 * Generate a where_token method for Jam_Query_Builder_Select
	 * @param  Jam_Query_Builder_Select $builder the builder object
	 * @param  Jam_Event_Data $data
	 * @param  string $token the token to search for
	 * @return void
	 */
	public function builder_call_where_token(Jam_Query_Builder_Select $builder, Jam_Event_Data $data, $token)
	{
		$builder->where($this->_model.'.'.$this->_field, '=', $token);
	}

	/**
	 * Generate a find_by_token method for Jam_Query_Builder_Select
	 * @param  Jam_Query_Builder_Select $builder the builder object
	 * @param  Jam_Event_Data $data
	 * @param  string $token the token to search for
	 */
	public function builder_call_find_by_token(Jam_Query_Builder_Select $builder, Jam_Event_Data $data, $token)
	{
		$this->builder_call_where_token($builder, $data, $token);
		$data->return = $builder->first();
		$data->stop = TRUE;
	}

	/**
	 * Generate a find_by_token_insist method for Jam_Query_Builder_Select
	 * @param  Jam_Query_Builder_Select $builder the builder object
	 * @param  Jam_Event_Data $data
	 * @param  string $token the token to search for
	 */
	public function builder_call_find_by_token_insist(Jam_Query_Builder_Select $builder, Jam_Event_Data $data, $token)
	{
		$this->builder_call_where_token($builder, $data, $token);
		$data->return = $builder->first_insist();
		$data->stop = TRUE;
	}
}
