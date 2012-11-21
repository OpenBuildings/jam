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
} // End Jam_Association_BelongsToTest