<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests BelongsTo associatons.
 *
 * @package Jam
 * @group   jam
 * @group   jam.association
 * @group   jam.association.core
 */
class Jam_AssociationTest extends Unittest_TestCase {

	public $meta;
	public $association;

	public function setUp()
	{
		parent::setUp();

		$this->meta = $this->getMock('Jam_Meta', NULL, array('test_post'));
	}

	public function data_initialize()
	{
		return array(
			array('test_author', array(), 'test_author', NULL),
			array('test_tags', array(), 'test_tag', NULL),
			array('test_tag_fake', array(), 'test_tag', 'Foreign model "test_tag_fake" does not exist for association test_tag_fake'),
			array('test_tag_fake', array('foreign_model' => 'test_tag'), 'test_tag', NULL),
			array('test_tag_fake', array('foreign_model' => 'test_tag_fake'), 'test_tag', 'Foreign model "test_tag_fake" does not exist for association test_tag_fake'),
		);
	}

	/**
	 * @dataProvider data_initialize
	 */
	public function test_initialize($name, $options, $foreign_model, $exception)
	{
		if ($exception)
		{
			$this->setExpectedException('Kohana_Exception', $exception);
		}

		$association = $this->getMockForAbstractClass('Jam_Association', array($options));
		$association->initialize($this->meta, $name);

		$this->assertEquals($foreign_model, $association->foreign_model);
		$this->assertEquals($name, $association->name);
	}

	public function data_value_to_key_and_model()
	{
		$test_position = Jam::factory('test_position')->load_fields(array('id' => 10, 'name' => 'name'));

		return array(
			array(NULL, 'test_position', FALSE, array(NULL, NULL)),
			array(1, 'test_position', FALSE, array(1, 'test_position')),
			array(1, 'test_position', TRUE, array(1, 'test_position')),
			array('test', 'test_position', FALSE, array('test', 'test_position')),
			array('test', 'test_position', TRUE, array('test', 'test_position')),
			array($test_position, 'test_position', FALSE, array(10, 'test_position')),
			array($test_position, 'test_position', TRUE, array(10, 'test_position')),
			array($test_position, NULL, FALSE, array(10, 'test_position')),
			array($test_position, NULL, TRUE, array(10, 'test_position')),
			array(array('id' => 10), 'test_position', FALSE, array(10, 'test_position')),
			array(array('name' => 10), 'test_position', FALSE, array(NULL, 'test_position')),
			array(array('test_position' => array('id' => 10)), NULL, TRUE, array(10, 'test_position')),
			array(array('test_position' => array('name' => 10)), NULL, TRUE, array(NULL, 'test_position')),
			array(array('test_position' => 10), NULL, TRUE, array(10, 'test_position')),
		);
	}

	/**
	 * @dataProvider data_value_to_key_and_model
	 */
	public function test_value_to_key_and_model($value, $model, $is_polymorphic, $expected_key_and_model)
	{
		$this->assertEquals($expected_key_and_model, Jam_Association::value_to_key_and_model($value, $model, $is_polymorphic));
	}
}