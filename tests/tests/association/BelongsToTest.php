<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests Belongsto associatons.
 *
 * @package Jam
 * @group   jam
 * @group   jam.association
 * @group   jam.association.belongsto
 */
class Jam_Association_BelongstoTest extends Testcase_Database {

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
			array('test_author', array('count_cache' => TRUE), 'test_author_id', NULL, NULL),
			array('creator',     array('foreign_model' => 'test_author'), 'creator_id', NULL, NULL),
			array('test_author', array('foreign_key' => 'author_uid'), 'author_uid', NULL, NULL),
			array('test_author', array('foreign_key' => 'test_author'), 'test_author', NULL, 'In association "test_author" for model "test_post" - invalid foreign_key name. Field and Association cannot be the same name'),
			array('test_author', array('polymorphic' => TRUE), 'test_author_id', 'test_author_model', NULL),
			array('test_author', array('polymorphic' => TRUE, 'count_cache' => TRUE), 'test_author_id', 'test_author_model', 'Cannot use count cache on polymorphic associations'),
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
			array(FALSE, NULL, NULL, TRUE, NULL, NULL),
			array(FALSE, NULL, NULL, FALSE, 10, 'test_author'),
			array(FALSE, NULL, 1, TRUE, 1, 'test_author'),
			array(FALSE, NULL, 'test', TRUE, 'test', 'test_author'),
			array(FALSE, NULL, array('id' => 2, 'name' => 'Test'), TRUE, 2, 'test_author'),
			array(TRUE, NULL, 1, TRUE, 1, 'test_category'),
			array(TRUE, NULL, NULL, FALSE, 10, 'test_category'),
			array(TRUE, 'test_blog', array('id' => 2, 'name' => 'Test'), TRUE, 2, 'test_blog'),
			array(TRUE, NULL, array('id' => 2, 'name' => 'Test'), TRUE, 2, 'test_category'),
		);
	}

	/**
	 * @dataProvider data_get
	 */
	public function test_get($is_polymorphic, $polymorphic_default_model, $value, $is_changed, $expected_id, $expected_model)
	{
		$association = $this->getMock('Jam_Association_Belongsto', array('_find_item'), array(array('polymorphic' => $is_polymorphic, 'polymorphic_default_model' => $polymorphic_default_model)));
		$association->initialize($this->meta, 'test_author');

		$post = Jam::build('test_post')->load_fields(array('id' => 1, 'test_author_id' => 10, 'test_author_model' => $polymorphic_default_model ? NULL : 'test_category'));

		if ($expected_model)
		{
			$author = Jam::build('test_author');

			$association
				->expects($this->once())
				->method('_find_item')
				->with($this->equalTo($expected_model), $this->equalTo($expected_id))
				->will($this->returnValue($author));

			$this->assertSame($author, $association->get($post, $value, $is_changed));
		}
		else
		{
			$this->assertSame($value, $association->get($post, $value, $is_changed));
		}
	}

	public function test_get_inverse_of()
	{
		$association = $this->getMock('Jam_Association_Belongsto', array('_find_item'), array(array('inverse_of' => 'test_post')));
		$association->initialize($this->meta, 'test_author');

		$author = Jam::build('test_author');
		$post = Jam::build('test_post')->load_fields(array('id' => 1, 'test_author_id' => 1));

		$association
			->expects($this->once())
			->method('_find_item')
			->will($this->returnValue($author));

		$this->assertSame($post, $association->get($post, 1, FALSE)->test_post);
	}

	public function data_set()
	{
		return array(
			array(FALSE, NULL, NULL, NULL, NULL, NULL),
			array(FALSE, NULL, 1, 1, 1, NULL),
			array(FALSE, NULL, 'test', 'test', NULL, NULL),
			array(FALSE, NULL, array('id' => 2, 'name' => 'Test'), array('id' => 2, 'name' => 'Test'), 2, NULL),
			array(TRUE, NULL, array('test_author' => 1), 1, 1, 'test_author'),
			array(TRUE, NULL, array('test_author' => array('id' => 2, 'name' => 'Test')), array('id' => 2, 'name' => 'Test'), 2, 'test_author'),
			array(TRUE, 'test_author', array('test_author' => 1), 1, 1, 'test_author'),
			array(TRUE, 'test_author', 1, 1, 1, 'test_author'),
		);
	}

	/**
	 * @dataProvider data_set
	 */
	public function test_set($is_polymorphic, $polymorphic_default_model, $value, $expected_value, $expected_foreign_key, $expected_polymorphic)
	{
		$association = new Jam_Association_Belongsto(array('polymorphic' => $is_polymorphic, 'polymorphic_default_model' => $polymorphic_default_model));
		$association->initialize($this->meta, 'test_author');

		$model = new Model_Test_Post();
		$value = $association->set($model, $value, TRUE);

		$this->assertEquals($expected_value, $value);

		$this->assertEquals($expected_foreign_key, $model->{$association->foreign_key}, 'Should have correct value for column '.$association->foreign_key);

		if ($association->is_polymorphic())
		{
			$this->assertEquals($expected_polymorphic, $model->{$association->polymorphic}, 'Should have correct value for column '.$association->polymorphic);
		}
	}

	public function test_set_inverse_of()
	{
		$association = new Jam_Association_Belongsto(array('inverse_of' => 'test_post'));
		$association->initialize($this->meta, 'test_author');

		$author = Jam::build('test_author');
		$post = Jam::build('test_post')->load_fields(array('id' => 1, 'test_author_id' => 1));

		$this->assertSame($post, $association->set($post, $author, TRUE)->test_post);
	}

	public function test_build()
	{
		$association = new Jam_Association_Belongsto(array('polymorphic' => FALSE, 'inverse_of' => 'test_post'));
		$association->initialize($this->meta, 'test_author');

		$model = new Model_Test_Post();
		$value = $association->build($model);
		$this->assertInstanceOf('Model_Test_Author', $value);
		$this->assertSame($value->test_post, $model);

		$association = new Jam_Association_Belongsto(array('polymorphic' => TRUE, 'inverse_of' => 'test_post'));
		$association->initialize($this->meta, 'test_author');

		$model = new Model_Test_Post();
		$value = $association->build($model);
		$this->assertNull($value);

		$model->set('test_author_model', 'test_author');

		$value = $association->build($model);
		$this->assertInstanceOf('Model_Test_Author', $value);
		$this->assertSame($value->test_post, $model);

		$association = new Jam_Association_Belongsto(array('inverse_of' => 'test_post'));
		$association->initialize($this->meta, 'test_position');

		$value = $association->build($model);
		$this->assertInstanceOf('Model_Test_Position', $value);

		$value = $association->build($model, array('model' => 'test_position_big'));
		$this->assertInstanceOf('Model_Test_Position_Big', $value);

	}

	public function test_load_fields()
	{
		$association = new Jam_Association_Belongsto(array());
		$association->initialize($this->meta, 'test_author');

		$model = new Model_Test_Post();
		$value = $association->load_fields($model, array('id' => 2, 'name' => 'Test'));

		$this->assertInstanceOf('Jam_Model', $value);
		$this->assertTrue($value->loaded());
		$this->assertEquals(2, $value->id());
		$this->assertEquals('Test', $value->name());
	}

	public function data_model_after_check()
	{
		return array(
			array(array('test_author' => 1), FALSE),
			array(array('test_author' => 'test'), FALSE),
			array(array('test_author' => array('id' => 2, 'name' => 'test')), TRUE),
			array(array('test_author' => array('name' => 'test')), TRUE),
		);
	}

	/**
	 * @dataProvider data_model_after_check
	 */
	public function test_model_after_check($changed, $perform_check)
	{
		$author = $this->getMock('Model_Test_Post', array('check'), array('test_post'));
		$author
			->expects($perform_check ? $this->once() : $this->never())
			->method('check')
			->will($this->returnValue(TRUE));

		$model = new Model_Test_Post();
		$model->test_author = $author;

		$model->meta()->association('test_author')->model_after_check($model, new Jam_Event_Data(array()), $changed);
	}

	public function data_model_after_check_polymorphic()
	{
		return array(
			array(array('test_holder' => 1), FALSE),
			array(array('test_holder' => 'test'), FALSE),
			array(array('test_holder' => array('test_author' => array('id' => 2, 'name' => 'test'))), TRUE),
			array(array('test_holder' => array('test_author' => array('name' => 'test'))), TRUE),
		);
	}

	/**
	 * @dataProvider data_model_after_check_polymorphic
	 */
	public function test_model_after_check_polymorphic($changed, $perform_check)
	{
		$author = $this->getMock('Model_Test_Post', array('check'), array('test_post'));
		$author
			->expects($perform_check ? $this->once() : $this->never())
			->method('check')
			->will($this->returnValue(TRUE));

		$model = new Model_Test_Image();
		$model->test_holder = $author;

		$model->meta()->association('test_holder')->model_after_check($model, new Jam_Event_Data(array()), $changed);
	}


	public function data_model_after_save()
	{
		return array(
			array(array('test_author' => 1), FALSE),
			array(array('test_author' => 'test'), FALSE),
			array(array('test_author' => array('id' => 2, 'name' => 'test')), TRUE),
			array(array('test_author' => array('name' => 'test')), TRUE),
		);
	}

	/**
	 * @dataProvider data_model_after_save
	 */
	public function test_model_after_save($changed, $perform_save)
	{
		$author = $this->getMock('Model_Test_Post', array('check', 'save'), array('test_post'));
		$author
			->expects($this->any())
			->method('check')
			->will($this->returnValue(TRUE));

		$author
			->expects($perform_save ? $this->once() : $this->never())
			->method('save')
			->will($this->returnValue($author));

		$model = new Model_Test_Post();
		$model->test_author = $author;

		$model->meta()->association('test_author')->model_before_save($model, new Jam_Event_Data(array()), $changed);
	}

	/**
	 * Test foreign key is not changed when association is updated
	 * with the same value, but with string type.
	 *
	 * @coversNothing
	 */
	public function test_foreign_key_not_changed_with_string()
	{
		$test_post = Jam::build('test_post')
			->load_fields(array(
				'test_blog_id' => 5
			));

		$test_post->test_blog = '5';
		$this->assertFalse($test_post->changed('test_blog_id'));
	}
}
