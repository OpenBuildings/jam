<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package Jam
 * @group   jam
 * @group   jam.range
 */
class Jam_RangeTest extends PHPUnit_Framework_TestCase {

	public function data_construct()
	{
		return array(
			array(NULL, NULL, NULL),
			array('30|40', 30, 40),
			array('-2|19', -2, 19),
			array('|', NULL, NULL),
			array('-19.2|10.12', -19.2, 10.12),
			array(array(4,10), 4, 10),
			array(array(10.3,10), 10.3, 10),
		);
	}

	/**
	 * @dataProvider data_construct
	 */
	public function test_construct($source, $expected_min, $expected_max)
	{
		$range = new Jam_Range($source);
		$this->assertEquals($expected_min, $range->min());
		$this->assertEquals($expected_max, $range->max());
	}

	public function data_toString()
	{
		return array(
			array(NULL, NULL, '|'),
			array(30, 40, '30|40'),
			array(-2, 19, '-2|19'),
			array(-19.2, 10.12, '-19.2|10.12'),
		);
	}

	/**
	 * @dataProvider data_toString
	 */
	public function test_toString($min, $max, $expected)
	{
		$range = new Jam_Range();
		$range->min($min);
		$range->max($max);

		$this->assertEquals($expected, (string) $range);
	}

	public function test_arrayaccessor()
	{
		$range = new Jam_Range(array(10, 30));
		$this->assertEquals(10, $range[0]);
		$this->assertEquals(30, $range[1]);

		$range[0] = 14;
		$range[1] = 124;

		$this->assertEquals(14, $range[0]);
		$this->assertEquals(14, $range->min());
		$this->assertEquals(124, $range[1]);
		$this->assertEquals(124, $range->max());
	}

	public function test_add()
	{
		$range1 = new Jam_Range(array(10, 30));
		$range2 = new Jam_Range(array(3, 12));

		$range_added = $range1->add($range2);

		$this->assertInstanceOf('Jam_Range', $range_added);
		$this->assertEquals(array(13, 42), $range_added->as_array());
	}

	public function test_sum()
	{
		$range1 = new Jam_Range(array(10, 30));
		$range2 = new Jam_Range(array(3, 12));
		$range3 = new Jam_Range(array(5, 21));

		$sum = Jam_Range::sum(array($range1, $range2, $range3));

		$this->assertInstanceOf('Jam_Range', $sum);
		$this->assertEquals(array(10+3+5, 30+12+21), $sum->as_array());
	}

	public function test_merge()
	{
		$range1 = new Jam_Range(array(10, 30));
		$range2 = new Jam_Range(array(3, 12));
		$range3 = new Jam_Range(array(5, 42));

		$merge = Jam_Range::merge(array($range1, $range2, $range3));

		$this->assertInstanceOf('Jam_Range', $merge);
		$this->assertEquals(array(10, 42), $merge->as_array());
	}
}