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
			array(NULL, NULL, NULL, NULL, ':min - :max'),
			array('30|40', NULL, '30', '40', ':min - :max'),
			array('-2|19', ':min / :max time', '-2', '19', ':min / :max time'),
			array('|', NULL, '', '', ':min - :max'),
			array('-19.2|10.12', NULL, '-19.2', '10.12', ':min - :max'),
			array(array(4, 10), ':min - ?', 4, 10, ':min - ?'),
			array(array(10.3, 10), ':min - :max days', 10.3, 10, ':min - :max days'),
		);
	}

	/**
	 * @dataProvider data_construct
	 * @covers Jam_Range::__construct
	 */
	public function test_construct($source, $format, $expected_min, $expected_max, $expected_format)
	{
		$range = new Jam_Range($source, $format);
		$this->assertSame($expected_min, $range->min());
		$this->assertSame($expected_max, $range->max());
		$this->assertSame($expected_format, $range->format());
	}

	public function data_construct_from_jam_range()
	{
		return array(
			array(NULL, NULL, NULL, NULL, NULL, ':min - :max'),
			array(array(4, 10), ':min - :max', ':min - ?', 4, 10, ':min - ?'),
			array(array(10.3, 10), ':min - :max hours', ':min - :max days', 10.3, 10, ':min - :max days'),
		);
	}

	/**
	 * @dataProvider data_construct_from_jam_range
	 * @covers Jam_Range::__construct
	 */
	public function test_construct_from_jam_range($jam_range_source, $jam_range_format, $format, $expected_min, $expected_max, $expected_format)
	{
		$range = new Jam_Range(new Jam_Range($jam_range_source, $jam_range_format), $format);
		$this->assertSame($expected_min, $range->min());
		$this->assertSame($expected_max, $range->max());
		$this->assertSame($expected_format, $range->format());
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
	 * @covers Jam_Range::__toString
	 */
	public function test_toString($min, $max, $expected)
	{
		$range = new Jam_Range();
		$range->min($min);
		$range->max($max);

		$this->assertEquals($expected, (string) $range);
	}

	public function data_offsetExists()
	{
		return array(
			array(0, TRUE),
			array(1, TRUE),
			array(2, FALSE),
			array(-1, FALSE),
		);
	}

	/**
	 * @dataProvider data_offsetExists
	 * @covers Jam_Range::offsetExists
	 */
	public function test_offsetExists($offset, $expected)
	{
		$range = new Jam_Range;
		$this->assertSame($expected, $range->offsetExists($offset));
	}

	/**
	 * @covers Jam_Range::offsetGet
	 */
	public function test_offsetGet()
	{
		$range = new Jam_Range(array(10, 30));
		$this->assertEquals(10, $range[0]);
		$this->assertEquals(30, $range[1]);

		$range->min(3);
		$range->max(5);
		$this->assertEquals(3, $range[0]);
		$this->assertEquals(5, $range[1]);
	}

	/**
	 * @covers Jam_Range::offsetSet
	 */
	public function test_offsetSet()
	{
		$range = new Jam_Range;
		$range[0] = 14;
		$range[1] = 124;

		$this->assertEquals(14, $range[0]);
		$this->assertEquals(14, $range->min());
		$this->assertEquals(124, $range[1]);
		$this->assertEquals(124, $range->max());
	}

	/**
	 * @covers Jam_Range::offsetSet
	 */
	public function test_offsetSet_exception()
	{
		$range = new Jam_Range;
		$this->setExpectedException('Kohana_Exception', 'Use offset 0 for min and offset 1 for max, offset 2 not supported');
		$range[2] = 5;
	}

	/**
	 * @covers Jam_Range::offsetUnset
	 */
	public function test_offsetUnset()
	{
		$range = new Jam_Range;
		$this->setExpectedException('Kohana_Exception', 'Cannot unset range object');
		unset($range[0]);
	}

	/**
	 * @covers Jam_Range::add
	 */
	public function test_add()
	{
		$range1 = new Jam_Range(array(10, 30));
		$range2 = new Jam_Range(array(3, 12));

		$range_added = $range1->add($range2);

		$this->assertInstanceOf('Jam_Range', $range_added);
		$this->assertEquals(array(13, 42), $range_added->as_array());
	}

	/**
	 * @covers Jam_Range::sum
	 */
	public function test_sum()
	{
		$range1 = new Jam_Range(array(10, 30));
		$range2 = new Jam_Range(array(3, 12));
		$range3 = new Jam_Range(array(5, 21));

		$sum = Jam_Range::sum(array($range1, $range2, $range3));

		$this->assertInstanceOf('Jam_Range', $sum);
		$this->assertEquals(array(10+3+5, 30+12+21), $sum->as_array());
	}

	/**
	 * @covers Jam_Range::merge
	 */
	public function test_merge()
	{
		$range1 = new Jam_Range(array(10, 30));
		$range2 = new Jam_Range(array(3, 12));
		$range3 = new Jam_Range(array(5, 42));

		$merge = Jam_Range::merge(array($range1, $range2, $range3));

		$this->assertInstanceOf('Jam_Range', $merge);
		$this->assertEquals(array(10, 42), $merge->as_array());
	}

	/**
	 * @covers Jam_Range::format
	 */
	public function test_format()
	{
		$range = new Jam_Range(array(2, 3));
		$this->assertSame(':min - :max', $range->format());

		$range->format('some other format');
		$this->assertSame('some other format', $range->format());
		$this->assertSame('some other format', $range->humanize());

		$range->format(function($min, $max){
			return 'some closure '.$min.' - '.$max;
		});

		$this->assertSame('some closure 2 - 3', $range->humanize());

		$range->format('Jam_RangeTest::format_formatter');

		$this->assertSame('some function 2 - 3', $range->humanize());
	}

	public static function format_formatter($min, $max)
	{
		return 'some function '.$min.' - '.$max;
	}

	/**
	 * @covers Jam_Range::min
	 */
	public function test_min()
	{
		$range = new Jam_Range;
		$this->assertSame(NULL, $range->min());

		$range->min(5);
		$this->assertSame(5, $range->min());
	}

	/**
	 * @covers Jam_Range::max
	 */
	public function test_max()
	{
		$range = new Jam_Range;
		$this->assertSame(NULL, $range->max());

		$range->max(10);
		$this->assertSame(10, $range->max());
	}

	public function data_humanize()
	{
		return array(
			array(1, 2, ':min - :max', '1 - 2'),
			array(NULL, 2, ':min - :max', ' - 2'),
			array(NULL, NULL, ':min - :max', ' - '),
			array(4, NULL, ':min - :max', '4 - '),
			array(4, 554, ':min - :max', '4 - 554'),
			array(5, 12, '(:min/:max)', '(5/12)'),
		);
	}

	/**
	 * @dataProvider data_humanize
	 * @covers Jam_Range::humanize
	 */
	public function test_humanize($min, $max, $format, $expected_humanize)
	{
		$range = new Jam_Range(array($min, $max), $format);
		$this->assertSame($expected_humanize, $range->humanize());
	}

	/**
	 * @covers Jam_Range::as_array
	 */
	public function test_as_array()
	{
		$range = new Jam_Range;
		$range->min(4);
		$range->max(10);
		$this->assertSame(array(4, 10), $range->as_array());
	}

	/**
	 * @coversNothing
	 */
	public function test_serializable()
	{
		$range = new Jam_Range(array(4, 5));
		$serialized = serialize($range);
		$this->assertEquals($range, unserialize($serialized));
	}

	/**
	 * @covers Jam_Range::serialize
	 */
	public function test_serialize()
	{
		$range = $this->getMock('Jam_Range', array('__toString'));

		$range
			->expects($this->once())
				->method('__toString')
				->will($this->returnValue('ABCDE'));

		$this->assertSame('ABCDE', $range->serialize());
	}

	/**
	 * @covers Jam_Range::unserialize
	 */
	public function test_unserialize()
	{
		$range = new Jam_Range;

		$range->unserialize('4|5');
		$this->assertSame('4', $range->min());
		$this->assertSame('5', $range->max());
	}
}
