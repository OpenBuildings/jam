<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Minion_Task_Image_Generator_Clear extends Minion_Task {

	protected $_config = array(
		'model' => FALSE,
		'image' => FALSE,
		'id' => FALSE,
		'limit' => FALSE,
		'offset' => FALSE,
	);

	public function build_validation(Validation $validation)
	{
		return parent::build_validation($validation)
			->rule('model', 'not_empty');
	}

	public function execute(array $options)
	{
		$builder = Jam::query($options['model']);

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

		Minion_CLI::progress($builder->select_all(), function($item) use ($options) {
			if ($options['image'])
			{
				$item->image_generator($options['image'])->clear();
			}
			else
			{
				$item->image_generator_clear();
			}
		});
	}
}
