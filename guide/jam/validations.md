**Table of Contents**  *generated with [DocToc](http://doctoc.herokuapp.com/)*

- [Validations Overview](#validations-overview)
	- [Why Use Validations?](#why-use-validations?)
	- [When Does Validation Happen?](#when-does-validation-happen?)
	- [Skipping Validation](#skipping-validation)
	- [check(), check_insist()](#check-check_insist)
	- [errors()](#errors)
	- [Validation Helpers](#validation-helpers)
		- [Valid::not_empty](#valid::not_empty)
		- [Valid::regex](#valid::regex)
		- [Valid::min_length](#valid::min_length)
		- [Valid::max_length](#valid::max_length)
		- [Valid::exact_length](#valid::exact_length)
		- [Valid::equals](#valid::equals)
		- [Valid::email](#valid::email)
		- [Valid::email_domain](#valid::email_domain)
		- [Valid::url](#valid::url)
		- [Valid::ip](#valid::ip)
		- [Valid::credit_card](#valid::credit_card)
		- [Valid::phone](#valid::phone)
		- [Valid::date](#valid::date)
		- [Valid::alpha](#valid::alpha)
		- [Valid::alpha_numeric](#valid::alpha_numeric)
		- [Valid::digit](#valid::digit)
		- [Valid::numeric](#valid::numeric)
		- [Valid::range](#valid::range)
		- [Valid::decimal](#valid::decimal)
		- [Valid::color](#valid::color)
		- [Valid::matches](#valid::matches)
	- [Performing Custom Validations](#performing-custom-validations)
	- [Working with Validation Errors](#working-with-validation-errors)

# Validations Overview

Validations allow you to ensure that only valid data is stored in your database. Before you dive into the detail of validations in Jerry, you should understand a bit about how validations fit into the big picture.

## Why Use Validations?

Validations are used to ensure that only valid data is saved into your database. For example, it may be important to your application to ensure that every user provides a valid email address and mailing address.
There are several ways to validate data before it is saved into your database, including native database constraints, client-side validations, controller-level validations, and model-level validations:

* Database constraints and/or stored procedures make the validation mechanisms database-dependent and can make testing and maintenance more difficult. However, if your database is used by other applications, it may be a good idea to use some constraints at the database level. Additionally, database-level validations can safely handle some things (such as uniqueness in heavily-used tables) that can be difficult to implement otherwise.
* Client-side validations can be useful, but are generally unreliable if used alone. If they are implemented using JavaScript, they may be bypassed if JavaScript is turned off in the user's browser. However, if combined with other techniques, client-side validation can be a convenient way to provide users with immediate feedback as they use your site.
* Controller-level validations can be tempting to use, but often become unwieldy and difficult to test and maintain. Whenever possible, it's a good idea to keep your controllers skinny, as it will make your application a pleasure to work with in the long run.
* Model-level validations are the best way to ensure that only valid data is saved into your database. They are database agnostic, cannot be bypassed by end users, and are convenient to test and maintain. Jerry makes them easy to use, using the built in Validation Helpers of Kohana for common needs, and allows you to create your own validation methods as well.

## When Does Validation Happen?

There are two kinds of Jerry objects: those that correspond to a row inside your database and those that do not. When you create a fresh object, for example using Jerry::factory('model'), that object does not belong to the database yet. Once you call save upon that object it will be saved into the appropriate database table. Jerry uses the ->loaded() instance method to determine whether an object is already in the database or not. Consider the following simple Active Record class:

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Post extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'title'           => Jam::field('string'),
			'content'         => Jam::field('text'),
		));
	}
}
?>
```

You can now use this model like this:

```php
<?php 
$post = Jam::factory("post");

// Will return false
echo $post->loaded();

$post->save();

// Will return true
echo $post->loaded();
?>
```

Creating and saving a new record will send an SQL INSERT operation to the database. Updating an existing record will send an SQL UPDATE operation instead. Validations are typically run before these commands are sent to the database. If any validations fail, the object will be marked as invalid and Jerry will not perform the INSERT or UPDATE operation. This helps to avoid storing an invalid object in the database. You can choose to have specific validations run when an object is created, saved, or updated.

> __Be careful__
> There are many ways to change the state of an object in the database. Some methods will trigger validations, but some will not. This means that it's possible to save an object in the database in an invalid state if you aren't careful.

Validations are triggered by these methods

* `save()`
* `check()`
* `check_insist()`

Both `save()` and `check_insist()` will raise an exception if the validation fails.

## Skipping Validation

If you want to skip the validation of a model, you can insert / update data explicitly through the Database Builder. This method should be used with caution:

```php
<?php 
// Insert a new record
$post = Jam::query("post")->set(array('name' => 'value'))->insert();

// Update an existing one
$post = Jam::query("post")->key(1)->set(array('name' => 'value'))->update();
?>
```

Note that save also has the ability to skip validations if passed FALSE as argument. This technique should be used with caution.

```php
<?php 
$post = Jam::factory("post", 1);
$post->save(FALSE);
?>
```
## check(), check_insist()

To verify whether or not an object is valid, Jerry uses the `check()` method. You can also use this method on your own. `check()` triggers your validations and returns TRUE if no errors were found in the object, and FALSE otherwise.

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Post extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'title'           => Jam::field('string', array(
				'rules' => array(
					array('not_empty')
				)
			)),
			'content'         => Jam::field('text'),
		));
	}
}

$post = Jam::factory("post");

// Will return TRUE
echo $post->set('title', 'New Post')->check();

// Will return FALSE
echo $post->set('title', NULL)->check();

// Will rise a Jam_Validation_Exception with access to the errors and validation object
$post->set('title', NULL)->check_insist();

?>
```

After Jerry has performed validations, any errors found can be accessed through the `errors()` instance method, which returns a collection of errors.

If you want to make sure the model is valid, you can use the `check_insist()` method. It will raise a `Jam_Validation_Exception` on failure to validate. The exception itself contains all the errors so you can inspect them, as well as references to the model itself. The plain `save()` method uses this to ensure that only valid models are saved and will raise the `Jam_Validation_Exception` if its not valid.

## errors()

To verify whether or not a particular field of an object is valid, you can use `errors('name')`. It returns an array of all the errors for 'name' field. If there are no errors on the specified attribute, an empty array is returned.

This method is only useful after validations have been run, because it only inspects the errors collection and does not trigger validations itself. It's different from the `check()` method explained above because it doesn't verify the validity of the object as a whole. It only checks to see whether there are errors found on an individual attribute of the object.

We'll cover validation errors in greater depth in the Working with Validation Errors section. For now, let's turn to the built-in validation helpers that Jerry provides by default.

## Validation Helpers

Jerry uses Kohana's Validation helpers directly inside your class definitions. These helpers provide common validation rules. Every time a validation fails, an error message is added to the object's errors collection, and this message is associated with the attribute being validated.

By default Jerry passes the value being checked to the validation helper, but there a lot of other variables you can pass. Jerry uses the concept of "bound" variables. Those are bound to the current execution context and can be used to reference variables. The available once are those:

* `:value` - the value being checked 
* `:field` - the name of the field
* `:model` - the model instance object
* `:validation` - the internal Validation object that Jerry uses - this is useful as it holds all the data being saved.

Of caurse any other variable will be passed directly. In the example above, if we wanted to add a more complex validation rules for "title", we'll use this

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Post extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'title'           => Jam::field('string', array(
				'rules' => array(
					array('not_empty'),
					array('min_length', array(':value', 5))
				)
			)),
			'content'         => Jam::field('text'),
		));
	}
}
?>
```

This will call `Valid::not_empty({value})` and `Valid::min_length({value}, 5)` and make sure they all return TRUE

The available Valid helpers from Kohana are:

### Valid::not_empty

This validation will fail if the value is not present. All the other validations will not fire if the value is not passed.

```php
<?php 
$meta->field('title', Jam::field('string', array(
	'rules' => array(array('not_empty'))
)));
?>
```

### Valid::regex

Use this if you want to validate by a custom RegEx. It will not execute if the value is empty.

```php
<?php 
$meta->field('title', Jam::field('string', array(
	'rules' => array(array('regex', array(":value", "/\d+.?\d+")))
)));
?>
```

### Valid::min_length

This validation will fail if the string is smaller length than specified. It will not execute if the value is empty.

```php
<?php 
$meta->field('title', Jam::field('string', array(
	'rules' => array(array('min_length', array(":value", 5)))
)));
?>
```

### Valid::max_length

This validation will fail if the string is larger length than specified. It will not execute if the value is empty.

```php
<?php 
$meta->field('title', Jam::field('string', array(
	'rules' => array(array('max_length', array(":value", 15)))
)));
?>
```

### Valid::exact_length

This validation will fail if the string is not the exact length specified. It will not execute if the value is empty.

```php
<?php 
$meta->field('title', Jam::field('string', array(
	'rules' => array(array('exact_length', array(":value", 10)))
)));
?>
```

### Valid::equals

Make sure the value of the field is the same as the specified one. It will not execute if the value is empty.

```php
<?php 
$meta->field('title', Jam::field('string', array(
	'rules' => array(array('equals', array(":value", 'some title')))
)));
?>
```

### Valid::email

Make sure the value of the field is valid email. You have two modes - normal and strict. By default it is more lenient towards the emails, but if you pass a second argument as TRUE, it will be more strict. Will not execute if the value is empty.

```php
<?php 
$meta->field('email', Jam::field('string', array(
	'rules' => array(array('email'))
)));
$meta->field('strict_email', Jam::field('string', array(
	'rules' => array(array('email', array(':value', TRUE)))
)));
?>
```

### Valid::email_domain

Validate the domain of an email address by checking if the domain has a valid MX record. Will not execute if the value is empty.

```php
<?php 
$meta->field('email', Jam::field('string', array(
	'rules' => array(array('email_domain'))
)));
?>
```

### Valid::url

Validate if the field value is a valid URL, Required a protocol prefix (http://) but IP addresses are considered valid. Will not execute if the value is empty.

```php
<?php 
$meta->field('email', Jam::field('string', array(
	'rules' => array(array('email_domain'))
)));
?>
```

### Valid::ip

Make sure the value of the field is valid ip address. By default all IPv4 addresses are accepted but if you pass the second value as FALSE, will not validate private range IPs. Will not execute if the value is empty.

```php
<?php 
$meta->field('my_ip', Jam::field('string', array(
	'rules' => array(array('ip'))
)));
$meta->field('public_ip', Jam::field('string', array(
	'rules' => array(array('ip', array(':value', FALSE)))
)));
?>
```

### Valid::credit_card

With this validation helper you can validate various credit cards. Kohana supports most popular vendors:

* 'american express'
* 'diners club'
* 'discover'
* 'jcb'
* 'maestro'
* 'mastercard'
* 'visa'

You pass the type of the credit card as the second parameter. Will not execute if the value is empty.

```php
<?php
$meta->field('maestro', Jam::field('string', array(
	'rules' => array(array('credit_card', array(':value', 'maestro')))
)));
?>
```

### Valid::phone

Check if its a valid phone number. This is done by removing all the non-digit characters in the number and then checking its length. You define a number of correct lengths yourself. The default lengths that are considered correct are 7,10 and 11. Will not execute if the value is empty.

```php
<?php
$meta->field('phone', Jam::field('string', array(
	'rules' => array(array('phone', array(':value', array(7,8,10))))
)));
?>
```

### Valid::date

Check if its a valid date string. All sorts of dates are considered valid, such as "yesterday", "-10 days", "10.0.99" and so on. Basically whatever can be parsed by the strtotime methods considered a date. Will not execute if the value is empty.

```php
<?php
$meta->field('phone', Jam::field('string', array(
	'rules' => array(array('date'))
)));
?>
```

### Valid::alpha

Checks whether a string consists of alphabetical characters only. By default it only considers ASCII characters as valid but if you pass the second argument as true, will also include UTF characters. Will not execute if the value is empty.

```php
<?php
$meta->field('name', Jam::field('string', array(
	'rules' => array(array('alpha'))
)));
$meta->field('name', Jam::field('string', array(
	'rules' => array(array('alpha', array(':value', TRUE)))
)));
?>
```

### Valid::alpha_numeric

Checks whether a string consists of alphabetical characters or numbers only. By default it only considers ASCII characters as valid but if you pass the second argument as true, will also include UTF characters. Will not execute if the value is empty.

```php
<?php
$meta->field('name', Jam::field('string', array(
	'rules' => array(array('alpha_numeric'))
)));
$meta->field('name', Jam::field('string', array(
	'rules' => array(array('alpha_numeric', array(':value', TRUE)))
)));
?>
```

### Valid::digit

Checks whether a string consists of numbers only (no dots or commas). By default it only considers ASCII characters as valid but if you pass the second argument as true, will also include UTF characters. Will not execute if the value is empty.

```php
<?php
$meta->field('product_numbder', Jam::field('string', array(
	'rules' => array(array('digit'))
)));
$meta->field('product_numbder', Jam::field('string', array(
	'rules' => array(array('digit', array(':value', TRUE)))
)));
?>
```

### Valid::numeric

Checks whether a string is a valid number (negative and decimal numbers allowed). Uses the current php locale to determine what decimal ceparator to use. Will not execute if the value is empty.

```php
<?php
$meta->field('product_price', Jam::field('string', array(
	'rules' => array(array('numeric'))
)));
?>
```

### Valid::range

Tests if a number is within a range. Requires to arguments for mix and max values inclusively. Will not execute if the value is empty.

```php
<?php
$meta->field('product_price', Jam::field('string', array(
	'rules' => array(array('range', array(":value", 10, 500)))
)));
?>
```

### Valid::decimal

Checks if a string is a proper decimal format. Optionally, a specific number of digits can be checked too. Will not execute if the value is empty.

```php
<?php
$meta->field('product_price', Jam::field('string', array(
	'rules' => array(
		// Normal
		array('decimal')

		// With 2 numbers after the decimal dot
		array('decimal', array(':value', 2))

		// With 2 numbers after the decimal dot and 10 before
		array('decimal', array(':value', 2, 10))
	)
)));
?>
```

### Valid::color

Checks if a string is a proper hexadecimal HTML color value. The validation is quite flexible as it does not require an initial "#" and also allows for the short notation using only three instead of six hexadecimal characters. Will not execute if the value is empty.

```php
<?php
$meta->field('product_color', Jam::field('string', array(
	'rules' => array(array('color'))
)));
?>
```

### Valid::matches

Checks if a field matches the value of another field. This validation will fail if it is not present

```php
<?php
$meta->field('email', Jam::field('string', array(
	'rules' => array(
		array('not_empty'), 
		array('email')
	)
)));
$meta->field('email_confirmation', Jam::field('string', array(

	//Ensure it matches email
	'rules' => array(array('matches', array(':validation', ':field', 'email')))

	// we do not need to save this value in the database
	'in_db' => FALSE,
)));
?>
```

## Performing Custom Validations

When the built-in validation helpers are not enough for your needs, you can write your validation methods as you prefer.
You can pass any method of any object and all it needs to do is respond with either TRUE or FALSE accordingly.

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Post extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'title'           => Jam::field('string', array(
				'rules' => array(
					array('Model_Post::valid_title', array(":value", ":model"))
					array(array(':model', 'valid_title_instance'), array(":value", ":validation"))
				)
			)),
		));
	}

	public static function valid_title($value, Model_Post $post)
	{
		return # code...
	}

	public function valid_title_instance($value, Validation $array)
	{
		return # code...
	}
}	
?>
```

Notice that you can pass ":model" and other bound context variables even to the method name array, so you can call those method specifically on the current model/validation instance, as the "initialize" method is static and does not have access to those variables.

## Working with Validation Errors

In addition to the check() and check_insist() methods covered earlier, Jerry provides an error() for working with the errors collection and inquiring about the validity of objects.

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Post extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'title'           => Jam::field('string', array(
				'rules' => array(
					array('not_empty')
				)
			)),
			'content'         => Jam::field('text'),
		));
	}
}

$post = Jam::factory("post");

// Will return FALSE
echo $post->set('title', NULL)->check();

// Will return an array of errors for the whole model.
print_r($post->errors());

// Will return an array of errors only for the title field.
print_r($post->errors('title'));

?>
```

The `errors()` method returns any results only after the validation has been performed, otherwise it will return NULL, as it does not perform a validation itself, only retrieves the errors. 

Kohana Validation uses error message files to make the errors human readable. Jerry automatically sets this filename to "messages/jam/{model name}.php" so the result of `errors()` or `errors('name')` are already human readable. If you want to change the file used for validation error messages of a particular model, you can do so with the `errors_filename()`method on the meta:

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Post extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->errors_filename('messages/my-errors');

		// ...
	}
}