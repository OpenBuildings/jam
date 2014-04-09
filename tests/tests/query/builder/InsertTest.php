<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Model functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.query.builder
 * @group   jam.query.builder.insert
 */
class Jam_Query_Builder_InsertTest extends PHPUnit_Framework_TestCase {

	/**
	 * @covers Jam_Query_Builder_Insert::__construct
	 */
	public function test_constructs_model_to_table()
	{
		$insert = new Jam_Query_Builder_Insert('test_author');

		$this->assertSame(
			'INSERT INTO `test_authors` () VALUES ',
			$insert->compile()
		);
	}

	/**
	 * @covers Jam_Query_Builder_Insert::__construct
	 */
	public function test_constructs_columns()
	{
		$insert = new Jam_Query_Builder_Insert('test_author', array(
			'name',
			'username'
		));

		$this->assertSame(
			'INSERT INTO `test_authors` (`name`, `username`) VALUES ',
			$insert->compile()
		);
	}

	/**
	 * @covers Jam_Query_Builder_Insert::__construct
	 */
	public function test_constructs_meta()
	{
		$insert = new Jam_Query_Builder_Insert('test_author');

		$this->assertSame(Jam::meta('test_author'), $insert->meta());
	}

	/**
	 * @covers Jam_Query_Builder_Insert::__construct
	 */
	public function test_event_builder_after_construct()
	{
		$mock = $this->getMock('stdClass', array(
			'test_event_callback'
		));

		$jam_event_data_constraint = new PHPUnit_Framework_Constraint_And;

		$jam_event_data_constraint
			->setConstraints(array(
				$this->isInstanceOf('Jam_Event_Data'),
				$this->attribute($this->equalTo('builder.after_construct'), 'event'),
				$this->attribute($this->isInstanceOf('Jam_Query_Builder_Insert'), 'sender'),
				$this->attribute($this->equalTo(array()), 'args'),
			));

		$mock
			->expects($this->once())
			->method('test_event_callback')
			->with(
				$this
					->isInstanceOf('Jam_Query_Builder_Insert'),
				$jam_event_data_constraint
			);

		Jam::meta('test_author')
			->events()
				->bind('builder.after_construct', array(
					$mock, 'test_event_callback'
				));

		new Jam_Query_Builder_Insert('test_author');
	}

	/**
	 * @covers Jam_Query_Builder_Insert::__call
	 */
	public function test_call()
	{
		$mock = $this->getMock('stdClass', array(
			'test_event_callback'
		));

		$jam_event_data_constraint = new PHPUnit_Framework_Constraint_And;

		$jam_event_data_constraint
			->setConstraints(array(
				$this->isInstanceOf('Jam_Event_Data'),
				$this->attribute($this->equalTo('builder.call_custom'), 'event'),
				$this->attribute($this->isInstanceOf('Jam_Query_Builder_Insert'), 'sender'),
				$this->attribute($this->equalTo(array('abc')), 'args'),
			));

		$mock
			->expects($this->once())
			->method('test_event_callback')
			->with(
				$this
					->isInstanceOf('Jam_Query_Builder_Insert'),
				$jam_event_data_constraint,
				$this->equalTo('abc')
			);

		Jam::meta('test_author')
			->events()
				->bind('builder.call_custom', array(
					$mock, 'test_event_callback'
				));

		$insert = new Jam_Query_Builder_Insert('test_author');

		$insert->custom('abc');
	}

	/**
	 * @covers Jam_Query_Builder_Insert::meta
	 */
	public function test_meta()
	{
		$insert = new Jam_Query_Builder_Insert('test_author');

		$this->assertSame(Jam::meta('test_author'), $insert->meta());
	}

	/**
	 * @covers Jam_Query_Builder_Insert::factory
	 */
	public function test_factory()
	{
		$insert = Jam_Query_Builder_Insert::factory('test_author', array('first_name', 'last_name'));

		$this->assertInstanceOf('Jam_Query_Builder_Insert', $insert);
		$this->assertSame(
			'INSERT INTO `test_authors` (`first_name`, `last_name`) VALUES ',
			$insert->compile()
		);
	}

	/**
	 * @covers Jam_Query_Builder_Insert::__toString
	 */
	public function test_toString()
	{
		$insert = $this->getMock('Jam_Query_Builder_Insert', array(
			'compile'
		), array(
			'test_author'
		));

		$insert
			->expects($this->once())
			->method('compile')
			->will($this->returnValue('ABCDE'));

		$this->assertSame('ABCDE', $insert->__toString());
	}

	/**
	 * @covers Jam_Query_Builder_Insert::__toString
	 */
	public function test_toString_exception()
	{
		$insert = $this->getMock('Jam_Query_Builder_Insert', array(
			'compile'
		), array(
			'test_author'
		));

		$insert
			->expects($this->once())
			->method('compile')
			->will($this->throwException(new Jam_Exception_Missing('test exception catching'))); $line = __LINE__;

		$file = __FILE__;

		$this->assertSame("Jam_Exception_Missing [ 0 ]: test exception catching ~ {$file} [ {$line} ]", $insert->__toString());
	}

	public function data_params_setting()
	{
		return array(
			array(
				'abc',
				'xyz',
				array('abc' => 'xyz'),
			),
			array(
				array('qwe' => 'qwerty'),
				'xyz',
				array('qwe' => 'qwerty'),
			),
			array(
				array('qwe' => 'qwerty'),
				NULL,
				array('qwe' => 'qwerty'),
			),
		);
	}

	/**
	 * @dataProvider data_params_setting
	 * @covers Jam_Query_Builder_Insert::params
	 */
	public function test_params_setting($key, $value, $expected)
	{
		$insert = Jam_Query_Builder_Insert::factory('test_author');

		$insert->params($key, $value);

		$this->assertSame($expected, $insert->params());
	}

	public function data_params_getting()
	{
		return array(
			array(
				array('abc' => 'xyz'),
				'abc',
				'xyz',
			),
			array(
				array('abc' => 'xyz'),
				NULL,
				array('abc' => 'xyz'),
			),
		);
	}

	/**
	 * @dataProvider data_params_getting
	 * @covers Jam_Query_Builder_Insert::params
	 */
	public function test_params_getting($params, $key, $expected)
	{
		$insert = Jam_Query_Builder_Insert::factory('test_author');

		$insert->params($params);

		$this->assertSame($expected, $insert->params($key));
	}

	/**
	 * @covers Jam_Query_Builder_Insert::params
	 */
	public function test_params_merging()
	{
		$insert = Jam_Query_Builder_Insert::factory('test_author');
		$insert->params(array('abc' => 'xyz'));
		$insert->params('rt', 'qw');
		$insert->params(array('abc' => 'uiop'));
		$insert->params(array('zyx' => 'xyz'));

		$this->assertSame(array(
			'abc' => 'uiop',
			'rt' => 'qw',
			'zyx' => 'xyz'
		), $insert->params());
	}
}

