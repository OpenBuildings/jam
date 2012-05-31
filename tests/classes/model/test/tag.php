<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Represents a tag in the database.
 *
 * @package  Jelly
 */
class Model_Test_Tag extends Jelly_Model {

	public static function initialize(Jelly_Meta $meta)
	{
		// Set database to connect to
		$meta->db(Unittest_Jelly_Testcase::$database_connection);

		$meta->associations(array(
			'test_post'     => Jelly::association('belongsto', array(
				'conditions' => array(
					'where' => array('test_post._approved_by', 'IS', NULL)
				),
				'counter_cache' => TRUE,
				'touch' => TRUE
			)),
			'test_blogs' => Jelly::association('manytomany', array(
				'required' => TRUE,
				'through' => 'test_blogs_test_tags'
			))
		));

		$meta->behaviors(array(
			'sluggable' => Jelly::behavior('sluggable', array('uses_primary_key' => FALSE, 'unique' => TRUE, 'slug' => 'Model_Test_Tag::_slug'))
		));

		// Set fields
		$meta->fields(array(
			'id'              => Jelly::field('primary'),
			'name'            => Jelly::field('string'),
		));
	}

	public static function _slug(Jelly_Model $model)
	{
		return $model->name();
	}

} // End Model_Test_Tag