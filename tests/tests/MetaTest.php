<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests Jam_Meta
 *
 * @package Jam
 * @group   jam
 * @group   jam.meta
 */
class Jam_MetaTest extends PHPUnit_Framework_TestCase {

	/**
	 * Tests various properties on a meta object.
	 */
	public function test_properties()
	{
		$fields = array(
			'id' => new Jam_Field_Primary,
			'id2' => new Jam_Field_Primary,
			'name' => new Jam_Field_String,
		);

		$meta = new Jam_Meta('foo');
		$meta->db('foo')
				->table('foo')
				->fields($fields)
				->sorting(array('foo' => 'bar'))
				->primary_key('id2')
				->name_key('name')
				->foreign_key('meta_fk')
				->behaviors(array(new Jam_Behavior_Test))
				->finalize('meta');

		// Ensure the simple properties are preserved
		$expected = array(
			'initialized' => TRUE,
			'db' => 'foo',
			'table' => 'foo',
			'model' => 'meta',
			'primary_key' => 'id2',
			'name_key'    => 'name',
			'foreign_key' => 'meta_fk',
			'sorting'     => array('foo' => 'bar'),
		);

		foreach ($expected as $property => $value)
		{
			$this->assertSame($meta->$property(), $value);
		}

		// Ensure we can retrieve fields properly
		$this->assertSame($meta->field('id2')->name, 'id2');

		// Ensure all fields match
		$this->assertSame($meta->fields(), $fields);

		// Ensure defaults are set properly
		$this->assertSame($meta->defaults(), array(
			'id' => NULL,
			'id2' => NULL,
			'name' => ''
		));

		foreach ($meta->behaviors() as $behavior)
		{
			// Ensure Behaviors return actual objects
			$this->assertTrue($behavior instanceof Jam_Behavior);
		}
	}
}
