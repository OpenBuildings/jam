<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Model functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.query.builder
 * @group   jam.query.builder.associated
 */
class Jam_Query_Builder_AssociatedTest extends Unittest_Jam_TestCase {

	public $collection;
	public $data;
	public $association;
	public $test_author;

	public function setUp()
	{
		parent::setUp();

		$this->data = array(
			array('id' => 1, 'name' => 'Staff'),
			array('id' => 2, 'name' => 'Freelancer'),
			array('id' => 3, 'name' => 'Manager'),
		);

		$this->collection = new Jam_Query_Builder_Associated('test_position');
		$this->collection->load_fields($this->data);

		$this->association = $this->getMock('Jam_Association_Hasmany', array('clear'), array(array('inverse_of' => 'test_author')));
		$this->association->initialize(Jam::meta('test_author'), 'test_positions');

		$this->test_author = Jam::build('test_author')->load_fields(array('id' => 1, 'name' => 'Test'));
	}

	public function test_getters_and_setters()
	{
		$this->collection->association($this->association);
		$this->assertSame($this->association, $this->collection->association());

		$this->collection->parent($this->test_author);
		$this->assertSame($this->test_author, $this->collection->parent());
	}

	public function test_load_model_changed()
	{
		$this->collection->association($this->association);
		$this->collection->parent($this->test_author);

		$this->assertSame($this->test_author, $this->collection[0]->test_author);
	}

	public function test_clear()
	{
		$this->association
			->expects($this->once())
			->method('clear')
			->with($this->equalTo($this->test_author), $this->isInstanceOf(get_class($this->collection)));

		$this->collection
			->association($this->association)
			->parent($this->test_author);

		$this->assertCount(count($this->data), $this->collection);
		$this->collection->clear();
		$this->assertCount(0, $this->collection);
	}

	public function test_build()
	{
		$this->collection
			->association($this->association)
			->parent($this->test_author);

		$item = $this->collection->build(array('name' => 'New'));
		
		$this->assertEquals(1, $item->test_author_id);

		$this->assertCount(count($this->data) + 1, $this->collection);

		$this->assertSame($this->test_author, $item->test_author);
	}
}