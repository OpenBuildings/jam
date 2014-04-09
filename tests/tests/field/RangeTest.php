<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests timestamp fields.
 *
 * @package Jam
 * @group   jam
 * @group   jam.field
 * @group   jam.field.range
 */
class Jam_Field_RangeTest extends PHPUnit_Framework_TestCase {

	public function test_set()
	{

		$field = new Jam_Field_Range();

		$model = Jam::build('test_position');

		$range = $field->set($model, '10|20', FALSE);

		$this->assertInstanceOf('Jam_Range', $range);
		$this->assertEquals(10, $range->min());
		$this->assertEquals(20, $range->max());

		$range = $field->set($model, array(5,10), FALSE);

		$this->assertInstanceOf('Jam_Range', $range);
		$this->assertEquals(5, $range->min());
		$this->assertEquals(10, $range->max());
	}

	/**
	 * @covers Jam_Field_Range::get
	 */
	public function test_get()
	{
		$field = new Jam_Field_Range();

		$model = Jam::build('test_position');

		$range = $field->get($model, NULL, FALSE);
		$this->assertNull($range);

		$range = $field->get($model, '', FALSE);
		$this->assertNull($range);

		$expected = new Jam_Range(array(2, 3));

		$range = $field->get($model, $expected, FALSE);
		$this->assertSame($expected, $range);

		$range = $field->get($model, '10|20', FALSE);

		$expected = new Jam_Range(array(10, 20));
		$this->assertEquals($expected, $range);
	}

	public function test_format()
	{
		$field = new Jam_Field_Range();
		$field->format = ':min - :max days';
		$model = Jam::build('test_position');

		$range = $field->get($model, '10|20', FALSE);

		$this->assertInstanceOf('Jam_Range', $range);
		$this->assertEquals('10 - 20 days', $range->humanize());
	}
}
