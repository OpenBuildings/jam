<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests Belongsto associatons.
 *
 * @package Jam
 * @group   jam
 * @group   jam.association
 * @group   jam.association.belongsto
 */
class Jam_Association_BelongstoTest extends Unittest_TestCase {

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
			array('test_author', array(), NULL, NULL, 'JOIN test_authors ON (test_authors.id = test_posts.test_author_id)'),
			array('test_author', array(), 'author', NULL, 'JOIN test_authors AS author ON (author.id = test_posts.test_author_id)'),
			array('test_author', array(), NULL, 'LEFT', 'LEFT JOIN test_authors ON (test_authors.id = test_posts.test_author_id)'),
			array('creator',     array('foreign_model' => 'test_author'), NULL, NULL, 'JOIN test_authors ON (test_authors.id = test_posts.creator_id)'),
			array('creator',     array('foreign_model' => 'test_author'), 'creator', 'LEFT', 'LEFT JOIN test_authors AS creator ON (creator.id = test_posts.creator_id)'),
			array('creator',     array('polymorphic' => TRUE), 'test_author', NULL, 'JOIN test_authors ON (\'test_author\' = test_posts.creator_model AND test_authors.id = test_posts.creator_id)'),
			array('creator',     array('polymorphic' => 'test_type'), 'test_author', 'LEFT', 'LEFT JOIN test_authors ON (\'test_author\' = test_posts.test_type AND test_authors.id = test_posts.creator_id)'),
		);
	}

	/**
	 * @dataProvider data_join
	 */
	public function test_join($name, $options, $table, $type, $expected_sql)
	{
		$association = new Jam_Association_Belongsto($options);
		$association->initialize($this->meta, $name);

		$this->assertEquals($expected_sql, $association->join($table, $type)->compile());
	}

	public function data_get()
	{
		return array(
			array(FALSE, NULL, TRUE, NULL, NULL),
			array(FALSE, NULL, FALSE, 10, 'test_author'),
			array(FALSE, 1, TRUE, 1, 'test_author'),
			array(FALSE, 'test', TRUE, 'test', 'test_author'),
			array(FALSE, array('id' => 2, 'name' => 'Test'), TRUE, 2, 'test_author'),
			array(TRUE, 1, TRUE, 1, 'test_category'),
			array(TRUE, NULL, FALSE, 10, 'test_category'),
			array(TRUE, array('id' => 2, 'name' => 'Test'), TRUE, 2, 'test_category'),
		);
	}

	/**
	 * @dataProvider data_get
	 */
	public function test_get($is_polymorphic, $value, $is_changed, $expected_id, $expected_model)
	{
		$association = $this->getMock('Jam_Association_Belongsto', array('_find_item'), array(array('polymorphic' => $is_polymorphic)));
		$association->initialize($this->meta, 'test_author');

		$post = Jam::build('test_post')->load_fields(array('id' => 1, 'test_author_id' => 10, 'test_author_model' => 'test_category'));

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

	public function data_set()
	{
		return array(
			array(FALSE, NULL, NULL, NULL, NULL),
			array(FALSE, 1, 1, 1, NULL),
			array(FALSE, 'test', 'test', NULL, NULL),
			array(FALSE, array('id' => 2, 'name' => 'Test'), array('id' => 2, 'name' => 'Test'), 2, NULL),
			array(TRUE, array('test_author' => 1), 1, 1, 'test_author'),
			array(TRUE, array('test_author' => array('id' => 2, 'name' => 'Test')), array('id' => 2, 'name' => 'Test'), 2, 'test_author'),
		);
	}

	/**
	 * @dataProvider data_set
	 */
	public function test_set($is_polymorphic, $value, $expected_value, $expected_foreign_key, $expected_polymorphic)
	{
		$association = new Jam_Association_Belongsto(array('polymorphic' => $is_polymorphic));
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
}