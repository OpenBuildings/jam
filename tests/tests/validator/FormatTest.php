<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.validator
 * @group   jam.validator.format
 */
class Jam_Validator_FormatTest extends Testcase_Validate {

	public function data_validate()
	{
		return array(
			// FILTER EMAIL
			array('asd', array('filter' => FILTER_VALIDATE_EMAIL), 'format_filter', NULL, FALSE),
			array('asd@asd$', array('filter' => FILTER_VALIDATE_EMAIL), 'format_filter', NULL, FALSE),
			array('test@example.com', array('filter' => FILTER_VALIDATE_EMAIL), 'format_filter', NULL, TRUE),
			array('@test.com', array('filter' => FILTER_VALIDATE_EMAIL), 'format_filter', NULL, FALSE),

			// FILTER URL
			array('asd', array('filter' => FILTER_VALIDATE_URL), 'format_filter', NULL, FALSE),
			array('example.com', array('filter' => FILTER_VALIDATE_URL), 'format_filter', NULL, FALSE),
			array('http://example.com', array('filter' => FILTER_VALIDATE_URL), 'format_filter', NULL, TRUE),
			array('//example.com', array('filter' => FILTER_VALIDATE_URL), 'format_filter', NULL, FALSE),

			// FILTER IP
			array('asd', array('filter' => FILTER_VALIDATE_IP), 'format_filter', NULL, FALSE),
			array('1.1.1.1', array('filter' => FILTER_VALIDATE_IP), 'format_filter', NULL, TRUE),
			array('95.87.212.88', array('filter' => FILTER_VALIDATE_IP), 'format_filter', NULL, TRUE),
			array('192.168.1.1', array('filter' => FILTER_VALIDATE_IP), 'format_filter', NULL, TRUE),
			array('192.168.1.1', array('filter' => FILTER_VALIDATE_IP, 'flag' => FILTER_FLAG_NO_PRIV_RANGE), 'format_filter', NULL, FALSE),

			// EMAIL
			array('asd', array('email' => TRUE), 'format_email', array('type' => 'email'), FALSE),
			array('asd@asd$', array('email' => TRUE), 'format_email', array('type' => 'email'), FALSE),
			array('test@example.com', array('email' => TRUE), 'format_email', array('type' => 'email'), TRUE),
			array('@test.com', array('email' => TRUE), 'format_email', array('type' => 'email'), FALSE),

			// URL
			array('asd', array('url' => TRUE), 'format_url', array('type' => 'url'), FALSE),
			array('example.com', array('url' => TRUE), 'format_url', array('type' => 'url'), FALSE),
			array('http://example.com', array('url' => TRUE), 'format_url', array('type' => 'url'), TRUE),
			array('//example.com', array('url' => TRUE), 'format_url', array('type' => 'url'), FALSE),

			// IP
			array('asd', array('ip' => TRUE), 'format_ip', NULL, FALSE),
			array('1.1.1.1', array('ip' => TRUE), 'format_ip', NULL, TRUE),
			array('95.87.212.88', array('ip' => TRUE), 'format_ip', NULL, TRUE),
			array('192.168.1.1', array('ip' => TRUE), 'format_ip', NULL, TRUE),

			// CREDIT CARD
			array('asd', array('credit_card' => TRUE), 'format_credit_card', array('pattern' => '^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6(?:011|5[0-9][0-9])[0-9]{12}|3[47][0-9]{13}|3(?:0[0-5]|[68][0-9])[0-9]{11}|(?:2131|1800|35\d{3})\d{11})$'), FALSE),
			array('378282246310005', array('credit_card' => TRUE), 'format_credit_card', array('pattern' => '^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6(?:011|5[0-9][0-9])[0-9]{12}|3[47][0-9]{13}|3(?:0[0-5]|[68][0-9])[0-9]{11}|(?:2131|1800|35\d{3})\d{11})$'), TRUE),
			array('3530111333300000', array('credit_card' => TRUE), 'format_credit_card', array('pattern' => '^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6(?:011|5[0-9][0-9])[0-9]{12}|3[47][0-9]{13}|3(?:0[0-5]|[68][0-9])[0-9]{11}|(?:2131|1800|35\d{3})\d{11})$'), TRUE),
			array('4074721508894', array('credit_card' => TRUE), 'format_credit_card', array('pattern' => '^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6(?:011|5[0-9][0-9])[0-9]{12}|3[47][0-9]{13}|3(?:0[0-5]|[68][0-9])[0-9]{11}|(?:2131|1800|35\d{3})\d{11})$'), TRUE),
			array('4012888888881881', array('credit_card' => TRUE), 'format_credit_card', array('pattern' => '^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6(?:011|5[0-9][0-9])[0-9]{12}|3[47][0-9]{13}|3(?:0[0-5]|[68][0-9])[0-9]{11}|(?:2131|1800|35\d{3})\d{11})$'), TRUE),

			// REGEX
			array('asd', array('regex' => '/.{4}/'), 'format_regex', array('pattern' => '.{4}'), FALSE),
			array('test', array('regex' => '/.{4}/'), 'format_regex', array('pattern' => '.{4}'), TRUE),
		);
	}

	/**
	 * @dataProvider data_validate
	 */
	public function test_validate($value, $options, $error, $expected_attributes, $is_valid)
	{
		$element = Jam::build('test_element');

		$validator_rule = Jam::validator_rule('format', $options);

		$this->assertEquals($expected_attributes, $validator_rule->html5_validation());

		$validator_rule->validate($element, 'name', $value);

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
