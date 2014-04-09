<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Represents a tag in the database.
 *
 * @package  Jam
 */
class Model_Test_Tag extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		// Set database to connect to
		$meta->db(Kohana::TESTING);

		$meta->associations(array(
			'test_post'     => Jam::association('belongsto', array(
				'conditions' => array(
					'where' => array('test_post._approved_by', 'IS', NULL)
				),
				'counter_cache' => TRUE,
				'touch' => 'updated'
			)),
			'test_blogs' => Jam::association('manytomany', array(
				'join_table' => 'test_blogs_test_tags'
			))
		));

		$meta->behaviors(array(
			'sluggable' => Jam::behavior('sluggable', array('uses_primary_key' => FALSE, 'unique' => TRUE, 'slug' => 'Model_Test_Tag::_slug'))
		));

		// Set fields
		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'name'            => Jam::field('string'),
		));
	}

	public static function _slug(Jam_Model $model)
	{
		return $model->name();
	}

} // End Model_Test_Tag
