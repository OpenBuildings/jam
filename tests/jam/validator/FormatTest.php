<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.validator
 * @group   jam.validator.format
 */
class Jam_Validator_FormatTest extends Unittest_Jam_TestCase {

	public function data_validate()
	{
		return array(
			// FILTER EMAIL
			array('asd', array('filter' => FILTER_VALIDATE_EMAIL), 'format_filter', FALSE),
			array('asd@asd$', array('filter' => FILTER_VALIDATE_EMAIL), 'format_filter', FALSE),
			array('test@example.com', array('filter' => FILTER_VALIDATE_EMAIL), 'format_filter', TRUE),
			array('@test.com', array('filter' => FILTER_VALIDATE_EMAIL), 'format_filter', FALSE),
			
			// FILTER URL
			array('asd', array('filter' => FILTER_VALIDATE_URL), 'format_filter', FALSE),
			array('example.com', array('filter' => FILTER_VALIDATE_URL), 'format_filter', FALSE),
			array('http://example.com', array('filter' => FILTER_VALIDATE_URL), 'format_filter', TRUE),
			array('//example.com', array('filter' => FILTER_VALIDATE_URL), 'format_filter', FALSE),

			// FILTER IP
			array('asd', array('filter' => FILTER_VALIDATE_IP), 'format_filter', FALSE),
			array('1.1.1.1', array('filter' => FILTER_VALIDATE_IP), 'format_filter', TRUE),
			array('95.87.212.88', array('filter' => FILTER_VALIDATE_IP), 'format_filter', TRUE),
			array('192.168.1.1', array('filter' => FILTER_VALIDATE_IP), 'format_filter', TRUE),
			array('192.168.1.1', array('filter' => FILTER_VALIDATE_IP, 'flag' => FILTER_FLAG_NO_PRIV_RANGE), 'format_filter', FALSE),

			// EMAIL
			array('asd', array('email' => TRUE), 'format_email', FALSE),
			array('asd@asd$', array('email' => TRUE), 'format_email', FALSE),
			array('test@example.com', array('email' => TRUE), 'format_email', TRUE),
			array('@test.com', array('email' => TRUE), 'format_email', FALSE),

			// URL
			array('asd', array('url' => TRUE), 'format_url', FALSE),
			array('example.com', array('url' => TRUE), 'format_url', FALSE),
			array('http://example.com', array('url' => TRUE), 'format_url', TRUE),
			array('//example.com', array('url' => TRUE), 'format_url', FALSE),

			// IP
			array('asd', array('ip' => TRUE), 'format_ip', FALSE),
			array('1.1.1.1', array('ip' => TRUE), 'format_ip', TRUE),
			array('95.87.212.88', array('ip' => TRUE), 'format_ip', TRUE),
			array('192.168.1.1', array('ip' => TRUE), 'format_ip', TRUE),

			// REGEX
			array('asd', array('regex' => '/.{4}/'), 'format_regex', FALSE),
			array('test', array('regex' => '/.{4}/'), 'format_regex', TRUE),
		);
	}

	/**
	 * @dataProvider data_validate
	 */
	public function test_validate($value, $options, $error, $is_valid)
	{
		$element = Jam::build('test_element');

		Jam::validator_rule('format', $options)->validate($element, 'name', $value);

		if ($is_valid)
		{
			$this->assertNotHasError($element, 'name', $error);
			
		}
		else
		{
			$this->assertHasError($element, 'name', $error);
		}
	}
}