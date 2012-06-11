<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Represents a post in the database.
 *
 * @package  Jam
 */
class Model_Test_Post extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		// Set database to connect to
		$meta->db(Unittest_Jam_Testcase::$database_connection);

		// Posts always load_with an author
		//$meta->load_with(array('test_author'));

		$meta->name_key('name');
		
		$meta->associations(array(
			'test_blog'       => Jam::association('belongsto', array('inverse_of' => 'test_posts')),
			'test_author'     => Jam::association('belongsto', array()),
			'test_tags'       => Jam::association('hasmany', array(
				'counter_cache' => TRUE,
				'conditions' => array(
					'where' => array(DB::expr('LEFT(test_tags.name, 2)'), '!=', "--")
				),
				'inverse_of' => 'test_post',
				'extend' => array(
					'list_items' => function ($builder) {
						$builder->where(DB::expr('LEFT(test_tags.name, 2)'), '=', '* ');
					},
					'non_list_items' => 'Model_Test_Post::_non_list_items'
				)
			)),
			'approved_by'     => Jam::association('belongsto', array(
				'foreign' => 'test_author.id',
				'column'  => '_approved_by',
				'inverse_of' => 'test_author',
			)),
			'test_images'     => Jam::association('hasmany', array(
				'as' => 'test_holder',
				'dependent' => Jam_Association::DELETE
			)),
			'test_cover_image'     => Jam::association('hasone', array(
				'as' => 'test_holder',
				'foreign' => 'test_image',
				'dependent' => Jam_Association::DELETE
			)),
			'test_categories' => Jam::association('manytomany'),
			'types' => Jam::association('taxonomy_terms', array('vocabulary' => 'Types', 'vocabulary_model' => 'test_vocabulary', 'through' => 'test_terms_items', 'foreign' => 'test_term.id'))
		));

		// Set fields
		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'name'            => Jam::field('string'),
			'slug'            => Jam::field('slug', array(
				'unique' => TRUE
			)),
			'status'          => Jam::field('enum', array(
				'choices' => array('draft', 'published', 'review'),
			)),
			'created'         => Jam::field('timestamp', array(
				'auto_now_create' => TRUE
			)),	
			'updated'         => Jam::field('timestamp', array(
				'auto_now_update' => TRUE
			)),	

			// Alias columns, for testing
			'_id'             => 'id',
			'_slug'           => 'slug',
		));
	}

	static public function _non_list_items(Jam_Builder $builder, Jam_Event_Data $data)
	{
		$builder->where(DB::expr('LEFT(test_tags.name, 2)'), '!=', '* ');
	}

} // End Model_Test_Post