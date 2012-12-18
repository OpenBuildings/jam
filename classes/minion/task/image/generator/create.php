<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Minion_Task_Image_Generator_Create extends Minion_Task {

	protected $_config = array(
		'model' => FALSE,
		'image' => FALSE,
		'id' => FALSE,
		'limit' => FALSE,
		'offset' => FALSE,
		'where' => FALSE,
	);

	public function build_validation(Validation $validation)
	{
		return parent::build_validation($validation)
			->rule('model', 'not_empty')
			->rule('image', 'not_empty');
	}

	public function execute(array $options)
	{
		$builder = Jam::query($options['model'])->order_by('id', 'ASC');	

		if ($options['id'])
		{
			$ids = explode(',', $options['id']);
			$builder->where(':primary_key', 'IN', $ids);
		}
		
		if ($options['limit'])
		{
			$builder->limit($options['limit']);
		}
		
		if ($options['offset'])
		{
			$builder->offset($options['offset']);
		}

		if ($options['where'])
		{
			list($field, $value) = explode('=', $options['where']);
			$builder->where($field, '=', $value);
		}

		Minion_CLI::progress($builder->select_all(), function($item) use ($options) {
			$generator = $item->image_generator($options['image']);

			if ( ! is_file($generator->path()))
			{
				$generator->generate();
			}
		});
	}
}
