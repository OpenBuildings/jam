<?php defined('SYSPATH') OR die('No direct script access.');

return array(
	'unique'                           => 'This :attribute is already in use',
	'present'                          => ':attribute must not be blank',
	'confirmed'                        => ':attribute must be the same as :confirmation',
	'length_minimum'                   => ':attribute must be longer than :minimum letters',
	'length_maximum'                   => ':attribute must be shorter than :maximum letters',
	'length_bwteen'                    => ':attribute must be shorter than :maximum and longer than :minimum letters',
	'length_is'                        => ':attribute must be :is letters',
	'accepted'                         => 'You must accept :attribute',
	'choice_in'                        => ':attribute is not an accepted value',
	'choice_out'                       => ':attribute is not an accepted value',
	'confirmed'                        => ':attribute must be the same as :',
	'count_minimum'                    => ':attribute must be more than :minimum items',
	'count_maximum'                    => ':attribute must be less than :maximum items',
	'count_bwteen'                     => ':attribute must be less than :maximum and more than :minimum items',
	'count_is'                         => ':attribute must be :is items',
	'numeric'                          => ':attribute must be a proper number',
	'numeric_greater_than_or_equal_to' => ':attribute must be greater than or equal to :greater_than_or_equal_to',
	'numeric_greater_than'             => ':attribute must be greater than :greater_than',
	'numeric_equal_to'                 => ':attribute must be equal to :equal_to',
	'numeric_less_than'                => ':attribute must be less than :less_than',
	'numeric_less_than_or_equal_to'    => ':attribute must be less than or equal to :less_than_or_equal_to',
	'numeric_between'                  => ':attribute must be less than :maximum and more than :minimum',
	'numeric_odd'                      => ':attribute must be an odd number',
	'numeric_even'                     => ':attribute must be an even number',
	'numeric_only_integer'             => ':attribute must be an integer ',
	'uploaded_is_file'                 => ':attribute is not a valid file',
	'uploaded_extension'               => ':attribute must be a :extension',
	'uploaded_minimum_width'           => ':attribute\'s width must be bigger than :minimum_width',
	'uploaded_minimum_height'          => ':attribute\'s height must be bigger than :minimum_height',
	'uploaded_maximum_width'           => ':attribute\'s width must be smaller than :maximum_width',
	'uploaded_maximum_height'          => ':attribute\'s height must be smaller than :maximum_height',
	'association'                      => 'There were errors in :attribute: :errors',
	'format_email'                     => ':attribute must be a valid email address',
	'format_url'                       => ':attribute must be a valid URL',
	'format_ip'                        => ':attribute must be a valid IP address',
	'format_regex'                     => ':attribute is invalid',
	'format_filter'                    => ':attribute is invalid',
);