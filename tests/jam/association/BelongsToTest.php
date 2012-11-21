<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests BelongsTo associatons.
 *
 * @package Jam
 * @group   jam
 * @group   jam.association
 * @group   jam.association.belongsto
 */
class Jam_Association_BelongsToTest extends Unittest_TestCase {

	public $meta;

	public function setUp()
	{
		parent::setUp();

		$this->meta = $this->getMock('Jam_Meta', array('field'), array('test_post'));
	}

	public function data_initialize()
	{
		return array(
			array('test_author', array(), 'test_author_id', NULL, NULL),
			array('creator',     array('foreign_model' => 'test_author'), 'creator_id', NULL, NULL),
			array('test_author', array('foreign_key' => 'author_uid'), 'author_uid', NULL, NULL),
			array('test_author', array('foreign_key' => 'test_author'), 'test_author', NULL, 'In association "test_author" for model "test_post" - invalid foreign_key name. Field and Association cannot be the same name'),
			array('test_author', array('polymorphic' => TRUE), 'test_author_id', 'test_author_model', NULL),
			array('creator',     array('foreign_model' => 'test_author', 'polymorphic' => TRUE), 'creator_id', 'creator_model', NULL),
			array('test_author', array('polymorphic' => 'author_type'), 'test_author_id', 'author_type', NULL),
			array('test_author', array('foreign_key' => 'author_uid', 'polymorphic' => 'author_type'), 'author_uid', 'author_type', NULL),
		);
	}

	/**
	 * @dataProvider data_initialize
	 */
	public function test_initialize($name, $options, $expected_foreign_key, $expected_polymorphic, $expected_exception)
	{
		if ($expected_exception)
		{
			$this->setExpectedException('Kohana_Exception', $expected_exception);
		}
		else
		{
			$this->meta
				->expects($this->at(0))
				->method('field')
				->with(
					$this->equalTo($expected_foreign_key), 
					$this->logicalAnd(
						$this->isInstanceOf('Jam_Field'), 
						$this->attributeEqualTo('default', NULL),
						$this->attributeEqualTo('allow_null', TRUE),
						$this->attributeEqualTo('convert_empty', TRUE)
					)
				);

			if ($expected_polymorphic)
			{
				$this->meta
					->expects($this->at(1))
					->method('field')
					->with(
						$this->equalTo($expected_polymorphic), 
						$this->logicalAnd(
							$this->isInstanceOf('Jam_Field'),
							$this->attributeEqualTo('default', NULL),
							$this->attributeEqualTo('allow_null', TRUE),
							$this->attributeEqualTo('convert_empty', TRUE)
						)
					);
			}
		}


		$association = new Jam_Association_Belongsto($options);
		$association->initialize($this->meta, $name);

		$this->assertEquals($name, $association->name);
		$this->assertEquals($expected_foreign_key, $association->foreign_key);
		$this->assertEquals($expected_polymorphic, $association->polymorphic);

		$this->assertEquals((bool) $expected_polymorphic, $association->is_polymorphic());
	}

	public function data_join()
	{
		return array(
			array('test_author', array(), NULL, NULL, 'JOIN `test_authors` ON (`test_authors`.`id` = `test_posts`.`test_author_id`)'),
			array('test_author', array(), 'author', NULL, 'JOIN `test_authors` AS `author` ON (`author`.`id` = `test_posts`.`test_author_id`)'),
			array('test_author', array(), NULL, 'LEFT', 'LEFT JOIN `test_authors` ON (`test_authors`.`id` = `test_posts`.`test_author_id`)'),
			array('creator',     array('foreign_model' => 'test_author'), NULL, NULL, 'JOIN `test_authors` ON (`test_authors`.`id` = `test_posts`.`creator_id`)'),
			array('creator',     array('foreign_model' => 'test_author'), 'creator', 'LEFT', 'LEFT JOIN `test_authors` AS `creator` ON (`creator`.`id` = `test_posts`.`creator_id`)'),
			array('creator',     array('polymorphic' => TRUE), 'test_author', NULL, 'JOIN `test_authors` ON (\'test_author\' = `test_posts`.`creator_model` AND `test_authors`.`id` = `test_posts`.`creator_id`)'),
			array('creator',     array('polymorphic' => 'test_type'), 'test_author', 'LEFT', 'LEFT JOIN `test_authors` ON (\'test_author\' = `test_posts`.`test_type` AND `test_authors`.`id` = `test_posts`.`creator_id`)'),
		);
	}

	/**
	 * @dataProvider data_join
	 */
	public function test_join($name, $options, $table, $type, $expected_sql)
	{
		$association = new Jam_Association_Belongsto($options);
		$association->initialize($this->meta, $name);

		$this->assertEquals($expected_sql, (string) $association->join($table, $type));
	}

	public function data_get()
	{
		return array(
			array('test_author', array(), NULL, NULL, NULL),
			array('test_author', array(), 1, 'test_author', 1),
			array('test_author', array(), 'test', 'test_author', 'test'),
			array('test_author', array(), array('id' => 2, 'name' => 'Test'), 'test_author', 2),
			array('test_author', array('polymorphic' => TRUE), array('test_category' => 1), 'test_category', 1),
			array('test_author', array('polymorphic' => TRUE), array('test_category' => array('id' => 2, 'name' => 'Test')), 'test_category', 2),
		);
	}

	/**
	 * @dataProvider data_get
	 */
	public function test_get($name, $options, $value, $expected_model, $expected_id)
	{
		$association = $this->getMock('Jam_Association_Belongsto', array('_find_item'), array($options));
		$association->initialize($this->meta, $name);
		$post = Jam::factory('test_post');

		if ($expected_model)
		{
			$author = Jam::factory('test_post');

			$association
				->expects($this->once())
				->method('_find_item')
				->with($this->equalTo($expected_model), $this->equalTo($expected_id))
				->will($this->returnValue($author));

			$this->assertSame($author, $association->get($post, $value, TRUE));
		}
		else
		{
			$this->assertSame($value, $association->get($post, $value, TRUE));			
		}

	}

	public function data_set()
	{
		return array(
			array('test_author', array(), NULL, NULL, NULL),
			array('test_author', array(), 1, 1, NULL),
			array('test_author', array(), 'test', NULL, NULL),
			array('test_author', array(), array('id' => 2, 'name' => 'Test'), 2, NULL),
			array('test_author', array('polymorphic' => TRUE), array('test_author' => 1), 1, 'test_author'),
			array('test_author', array('polymorphic' => TRUE), array('test_author' => array('id' => 2, 'name' => 'Test')), 2, 'test_author'),
		);
	}

	/**
	 * @dataProvider data_set
	 */
	public function test_set($name, $options, $value, $expected_foreign_key, $expected_polymorphic)
	{
		$association = new Jam_Association_Belongsto($options);
		$association->initialize($this->meta, $name);

		$model = new Model_Test_Post();
		$association->set($model, $value, TRUE);

		$this->assertEquals($expected_foreign_key, $model->{$association->foreign_key}, 'Should have correct value for column '.$association->foreign_key);

		if ($association->is_polymorphic())
		{
			$this->assertEquals($expected_polymorphic, $model->{$association->polymorphic}, 'Should have correct value for column '.$association->polymorphic);
		}
	}

	// /**
	//  * Provides test data for test_builder()
	//  *
	//  * @return  array
	//  */
	// public function provider_builder()
	// {
	// 	return array(
	// 		// Get existing author
	// 		array(array('test_post', 1, 'test_author'), TRUE),

	// 		// Get existing author
	// 		array(array('test_post', 2, 'test_author'), TRUE),

	// 		// Get approved_by model with custom column name and model
	// 		array(array('test_post', 2, 'approved_by'), TRUE),

	// 		// Get non-existing author
	// 		array(array('test_post', 555, 'test_author'), FALSE),

	// 		// Get author without specifying a post
	// 		array(array('test_post', NULL, 'test_author'), FALSE),

	// 		// Get polymorphic field
	// 		array(array('test_image', 1, 'test_holder'), TRUE),

	// 		// Get another polymorphic field
	// 		array(array('test_image', 2, 'test_holder'), TRUE),

	// 		// Get tag with condition TRUE
	// 		array(array('test_tag', 1, 'test_post'), TRUE),

	// 		// Get tag with condition FALSE
	// 		array(array('test_tag', 3, 'test_post'), FALSE),
	// 	);
	// }

	// /**
	//  * Tests for Jam_Association_BelongsTo::builder()
	//  *
	//  * @dataProvider  provider_builder
	//  * @param         Jam         $builder
	//  * @param         bool          $loaded
	//  * @return        void
	//  */
	// public function test_builder($builder, $loaded)
	// {
	// 	$model = Jam::factory($builder[0], $builder[1]);

	// 	if ( ! $model->loaded() AND $model->meta()->association($builder[2])->is_polymorphic())
	// 	{
	// 		$this->setExpectedException('Jam_Exception_NotLoaded');
	// 	}
		
	// 	$builder = $model->builder($builder[2]);

	// 	$this->assertTrue($builder instanceof Jam_Builder, "Must load Jam_Builder object for the association");

	// 	// Load the model
	// 	$model = $builder->select();

	// 	// Ensure it's loaded if it should be
	// 	$this->assertSame($loaded, $model->loaded());
	// }

	// public function test_count_cache()
	// {
	// 	$post = Jam::factory('test_post', 1);
	// 	$blog3 = Jam::factory('test_blog', 3);	

	// 	$post->test_blog = $blog3;
	// 	$post->save();

	// 	$blog1 = Jam::factory('test_blog', 1);
	// 	$blog3 = Jam::factory('test_blog', 3);

	// 	$this->assertEquals(2, $blog3->test_posts_count);
	// 	$this->assertEquals(0, $blog1->test_posts_count);

	// 	$post->delete();

	// 	$blog1 = Jam::factory('test_blog', 1);
	// 	$blog3 = Jam::factory('test_blog', 3);

	// 	$this->assertEquals(1, $blog3->test_posts_count);
	// 	$this->assertEquals(0, $blog1->test_posts_count);

	// }

	// public function test_model_assignment()
	// {
	// 	$test_post = Jam::factory('test_post', 1);
	// 	$test_author = Jam::factory('test_author', 2);

	// 	$test_post->test_author = $test_author;

	// 	$this->assertEquals($test_author->id(), $test_post->test_author_id, 'Should set the column');
	// 	$this->assertEquals($test_author->id(), $test_post->test_author->id(), 'Should set the actual model');

	// 	$test_post->save();

	// 	$test_post = Jam::factory('test_post', 1);

	// 	$this->assertEquals($test_author->id(), $test_post->test_author_id, 'Should set the column after save');
	// 	$this->assertEquals($test_author->id(), $test_post->test_author->id(), 'Should set the actual model after save');

	// 	$test_post->test_author = NULL;

	// 	$this->assertNull($test_post->test_author_id, 'Should set the column as null');

	// 	$test_post->save();

	// 	$test_post = Jam::factory('test_post', 1);

	// 	$this->assertNull($test_post->test_author_id, 'Should set the column as null');
	// }

	// public function test_column_assignment()
	// {
	// 	$test_post = Jam::factory('test_post', 1);
	// 	$test_author = Jam::factory('test_author', 2);

	// 	$test_post->test_author_id = $test_author->id();

	// 	$this->assertEquals($test_author->id(), $test_post->test_author_id, 'Should set the column');
	// 	$this->assertEquals($test_author->id(), $test_post->test_author->id(), 'Should set the actual model');

	// 	$test_post->save();

	// 	$test_post = Jam::factory('test_post', 1);

	// 	$this->assertEquals($test_author->id(), $test_post->test_author_id, 'Should set the column after save');
	// 	$this->assertEquals($test_author->id(), $test_post->test_author->id(), 'Should set the actual model after save');

	// 	$test_post = Jam::factory('test_post', 1);
	// 	$test_post->test_author_id = NULL;

	// 	$this->assertFalse($test_post->test_author->loaded(), 'Should set the model to empty one');

	// 	$test_post->save();

	// 	$test_post = Jam::factory('test_post', 1);

	// 	$this->assertFalse($test_post->test_author->loaded(), 'Should set the model to empty one');
	// }

	// public function test_build_association()
	// {
	// 	$test_post = Jam::factory('test_post', 1);
	// 	$test_author = $test_post->build('test_author');
	// 	$this->assertInstanceOf('Model_Test_Author', $test_author);
	// 	$this->assertSame($test_post->test_author, $test_author);
	// }

	// public function test_create_association()
	// {
	// 	$test_post = Jam::factory('test_post', 1);
	// 	$test_author = $test_post->create('test_author', array('test_position_id' => 1));
	// 	$test_post->save();

	// 	$this->assertInstanceOf('Model_Test_Author', $test_author);
	// 	$this->assertEquals($test_post->test_author->id(), $test_author->id());
	// 	$this->assertEquals($test_post->test_author_id, $test_author->id());
	// }

	// public function test_delete_normal()
	// {
	// 	$test_copyright = Jam::factory('test_copyright', 1);
	// 	$test_copyright_id = $test_copyright->id();
	// 	$test_image_id = $test_copyright->test_image->id();

	// 	$test_copyright->delete();
	// 	$this->assertNotExists('test_copyright', $test_copyright_id);
	// 	$this->assertNotExists('test_image', $test_image_id);
	// }

	// public function test_delete_nested_depencies()
	// {
	// 	$test_blog = Jam::factory('test_blog', 1);
	// 	$test_post = $test_blog->test_posts->first();
	// 	$images_ids = $test_post->test_images->as_array(NULL, 'id');

	// 	$test_blog->delete();
	// 	$this->assertNotExists('test_post', $test_post->id());
	// 	$this->assertNotExists('test_image', $images_ids);
	// }

	// public function test_mass_assignument()
	// {
	// 	$test_post = Jam::factory('test_post', 1);
	// 	$test_post->test_author = array('name' => 'new_author', 'test_position_id' => 1);
	// 	$test_post->save();

	// 	$this->assertInstanceOf('Model_Test_Author', $test_post->test_author);
	// 	$this->assertTrue($test_post->test_author->loaded());
	// 	$this->assertEquals('new_author', $test_post->test_author->name);

	// 	$test_post->test_author = 1;

	// 	$this->assertInstanceOf('Model_Test_Author', $test_post->test_author);
	// 	$this->assertTrue($test_post->test_author->loaded());
	// 	$this->assertEquals(1, $test_post->test_author->id());

	// 	$test_post->save();

	// 	$this->assertInstanceOf('Model_Test_Author', $test_post->test_author);
	// 	$this->assertTrue($test_post->test_author->loaded());
	// 	$this->assertEquals(1, $test_post->test_author->id());
	// 	$this->assertEquals(1, $test_post->test_author_id);
	// }

	// public function test_polymorphic_mass_assignment()
	// {
	// 	$test_image = Jam::factory('test_image', 1);

	// 	$test_image->test_holder = array('test_post' => array('name' => 'polymorphic post', 'slug' => 'pol-post-1'));
	// 	$test_image->save();

	// 	$this->assertInstanceOf('Model_Test_Post', $test_image->test_holder);
	// 	$this->assertTrue($test_image->test_holder->loaded());
	// 	$this->assertEquals('polymorphic post', $test_image->test_holder->name);

	// 	$test_image->test_holder = array('test_post' => 1);
	// 	$test_image->save();

	// 	$this->assertInstanceOf('Model_Test_Post', $test_image->test_holder);
	// 	$this->assertTrue($test_image->test_holder->loaded());
	// 	$this->assertEquals(1, $test_image->test_holder->id());
	// }

	// public function test_polymorphic_model_set()
	// {
	// 	$test_image = Jam::factory('test_image', 3);

	// 	$this->assertNull($test_image->test_holder, 'Should return NULL if no model or id set');
	// 	$test_image->test_holder = array('test_post' => NULL);

	// 	$this->assertInstanceOf('Model_Test_Post', $test_image->test_holder, 'Should set the test model only');
	// 	$this->assertFalse($test_image->test_holder->loaded(), 'Should not be loaded if only the model is set');

	// }

	// public function test_polymorphic_join_association()
	// {
	// 	$this->setExpectedException('Kohana_Exception');
	// 	Jam::query('test_image')->join_association('test_holder');
	// }

} // End Jam_Association_BelongsToTest