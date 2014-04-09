<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Generate a model file
 *
 * options:
 *
 *  - name: required, the name of the widget, eg product_purchase
 *  - author: set the author
 *  - module: which module to put this in, leave blank to put it in application
 *  - force: boolean flag - overwrite existing files
 *  - collection: boolean flag - optionally generate the 'collection' file for this model
 *  - unlink: boolean flag - unlink the generated files
 *
 * @package Jam tart
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Task_Jam_Model extends Minion_Task {

	protected $_options = array(
		'name' => FALSE,
		'module' => FALSE,
		'author' => FALSE,
		'force' => FALSE,
		'unlink' => FALSE,
		'collection' => FALSE,
	);

	public function build_validation(Validation $validation)
	{
		return parent::build_validation($validation)
			->rule('module', 'in_array', array(':value', array_keys(Kohana::modules())))
			->rule('name', 'not_empty');;
	}

	protected function _execute(array $options)
	{
		$module_name = $options['module'] ?: 'applicaiton';
		$module_dir = $options['module'] ? Arr::get(Kohana::modules(), $module_name) : APPPATH;

		$author = $options['author'] ?: '-';

		$name = $options['name'];
		$title = Jam::capitalize_class_name(str_replace('_', ' ', $name));
		$path = str_replace('_', DIRECTORY_SEPARATOR, $title);

		$dir = $module_dir.'classes'.DIRECTORY_SEPARATOR.'Model';
		$file = $dir.DIRECTORY_SEPARATOR.$path.EXT;
		$class ='Model_'.str_replace(' ', '_', $title);

		if ( ! is_dir(dirname($file)))
		{
			mkdir(dirname($file), 0777, TRUE);
		}

		$content = <<<MODEL
<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Jam Model: $title
 *
 * @package $module_name
 * @author $author
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class {$class} extends Jam_Model {

	public static function initialize(Jam_Meta \$meta)
	{
		\$meta
			->associations(array(
			))

			->fields(array(
				'id' => Jam::field('primary'),
				'name' => Jam::field('string'),
			))

			->validator('name', array('present' => TRUE));
	}
}
MODEL;

		Minion_Jam_Generate::modify_file($file, $content, $options['force'] !== FALSE, $options['unlink'] !== FALSE);

		if ($options['collection'] !== FALSE)
		{

			$dir = $module_dir.'classes'.DIRECTORY_SEPARATOR.'model'.DIRECTORY_SEPARATOR.'collection';
			$file = $dir.DIRECTORY_SEPARATOR.$path.EXT;
			$class ='Model_Collection_'.str_replace(' ', '_', $title);

			if ( ! is_dir(dirname($file)))
			{
				mkdir(dirname($file), 0777, TRUE);
			}

			$content = <<<COLLECTION
<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Collection for $title
 *
 * @package $module_name
 * @author $author
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class {$class} extends Jam_Query_Builder_Collection {


}
COLLECTION;
			Minion_Jam_Generate::modify_file($file, $content, $options['force'] !== FALSE, $options['unlink'] !== FALSE);
		}

	}
}
