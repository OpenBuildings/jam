# Validations Overview

Validations allow you to ensure that only valid data is stored in your database. Before you dive into the detail of validations with Jam, you should understand a bit about how validations fit into the big picture.

## Why Use Validations?

Validations are used to ensure that only valid data is saved into your database. For example, it may be important to your application to ensure that every user provides a valid email address and mailing address.
There are several ways to validate data before it is saved into your database, including native database constraints, client-side validations, controller-level validations, and model-level validations:

* Database constraints and/or stored procedures make the validation mechanisms database-dependent and can make testing and maintenance more difficult. However, if your database is used by other applications, it may be a good idea to use some constraints at the database level. Additionally, database-level validations can safely handle some things (such as uniqueness in heavily-used tables) that can be difficult to implement otherwise.
* Client-side validations can be useful, but are generally unreliable if used alone. If they are implemented using JavaScript, they may be bypassed if JavaScript is turned off in the user's browser. However, if combined with other techniques, client-side validation can be a convenient way to provide users with immediate feedback as they use your site.
* Controller-level validations can be tempting to use, but often become unwieldy and difficult to test and maintain. Whenever possible, it's a good idea to keep your controllers skinny, as it will make your application a pleasure to work with in the long run.
* Model-level validations are the best way to ensure that only valid data is saved into your database. They are database agnostic, cannot be bypassed by end users, and are convenient to test and maintain. Jam makes them easy to use, using the built in Validation Helpers of Kohana for common needs, and allows you to create your own validation methods as well.

## When Does Validation Happen?

There are two kinds of Jam objects: those that correspond to a row inside your database and those that do not. When you create a fresh object, for example using Jam::build('model'), that object does not belong to the database yet. Once you call save upon that object it will be saved into the appropriate database table. Jam uses the ->loaded() instance method to determine whether an object is already in the database or not. Consider the following simple Active Record class:

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
$post = Jam::build("post");

// Will return false
echo $post->loaded();

$post->save();

// Will return true
echo $post->loaded();
?>
```

Creating and saving a new record will send an SQL INSERT operation to the database. Updating an existing record will send an SQL UPDATE operation instead. Validations are typically run before these commands are sent to the database. If any validations fail, the object will be marked as invalid and Jam will not perform the INSERT or UPDATE operation. This helps to avoid storing an invalid object in the database. You can choose to have specific validations run when an object is created, saved, or updated.

> __Be careful__
> There are many ways to change the state of an object in the database. Some methods will trigger validations, but some will not. This means that it's possible to save an object in the database in an invalid state if you aren't careful.

Validations are triggered by these methods

* `save()`
* `check()`
* `check_insist()`

Both `save()` and `check_insist()` will raise Jam_Exception_Validation if the validation fails.

## Skipping Validation

If you want to skip the validation of a model, you can insert / update data explicitly through the Database Builder. This method should be used with caution:

```php
<?php
// Insert a new record
$post = Jam::insert('post')->set(array('name' => 'value'))->execute();

// Update an existing one
$post = Jam::update('post', 1)->set(array('name' => 'value'))->execute();
?>
```

Note that save also has the ability to skip validations if passed FALSE as argument. This technique should be used with caution.

```php
<?php
$post = Jam::find("post", 1);
$post->save(FALSE);
?>
```
## check(), check_insist()

To verify whether or not an object is valid, Jam uses the `check()` method. You can also use this method on your own. `check()` triggers your validations and returns TRUE if no errors were found in the object, and FALSE otherwise.

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

		$meta->validator('title', array('present' => TRUE));
	}
}

$post = Jam::build("post");

// Will return TRUE
echo $post->set('title', 'New Post')->check();

// Will return FALSE
echo $post->set('title', NULL)->check();

// Will rise a Jam_Exception_Validation with access to the model
$post->set('title', NULL)->check_insist();

?>
```

After Jam has performed validations, any errors found can be accessed through the `errors()` instance method, which returns a Jam_Errors object. This is an iteratable object containing all the errors on the model, you can access it as if it was an array, however it has some convenience methods.

If you want to make sure the model is valid, you can use the `check_insist()` method. It will raise a `Jam_Exception_Validation` on failure to validate. The exception itself the references to the model itself so you can inspect them with the `errors()` method. The plain `save()` method uses this to ensure that only valid models are saved and will raise the `Jam_Exception_Validation` if its not valid.

## errors()

To verify whether or not a particular field of an object is valid, you can use `errors('name')`. It returns an array of all the errors for 'name' field. If there are no errors on the specified attribute, an empty array is returned.

This method is only useful after validations have been run, because it only inspects the errors collection and does not trigger validations itself. It's different from the `check()` method explained above because it doesn't verify the validity of the object as a whole. It only checks to see whether there are errors found on an individual attribute of the object.

We'll cover validation errors in greater depth in the Working with Validation Errors section. For now, let's turn to the built-in validators that Jam provides by default.

## Validators

Jam offers many pre-defined validators that you can use directly inside your class definitions. These validators provide common validation rules. Every time a validation fails, an error message is added to the object’s errors collection, and this message is associated with the attribute being validated.

Each validator accepts an arbitrary number of attribute names, so with a single line of code you can add the same kind of validation to several attributes.

Let’s take a look at each one of the available validators.

### accepted

Validates that a checkbox on the user interface was checked when a form was submitted. This is typically used when the user needs to agree to your application's terms of service, confirm reading some text, or any similar concept. This validation is very specific to web applications and this 'acceptance' does not need to be recorded anywhere in your database.


```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Post extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'title'           => Jam::field('string'),
		));

		$meta->validator('terms_of_service', array('accepted' => TRUE));
	}
}
?>
```

It can receive an `accept` option, which determines the value that will be considered acceptance. It defaults to "1" and can be easily changed.

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Post extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'title'           => Jam::field('string'),
		));

		$meta->validator('terms_of_service', array('accepted' => array('accept' => 'yes')));
	}
}
?>
```

### confirmed

You should use this validator when you have two text fields that should receive exactly the same content. For example, you may want to confirm an email address or a password. This validation checks agianst a virtual attribute whose name is the name of the field that has to be confirmed with “_confirmation” appended.

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_User extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'email'           => Jam::field('string'),
		));

		$meta->validator('email', array('confirmed' => TRUE));
	}
}
?>
```

This check is performed only if email_confirmation is not NULL. To require confirmation, make sure to add a presence check for the confirmation attribute (we’ll take a look at presence later on this guide):

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_User extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'email'           => Jam::field('string'),
		));

		$meta->validator('email', array('confirmed' => TRUE));
		$meta->validator('email_confirmation', array('present' => TRUE));
	}
}
?>
```

### choice

This validator checks that the attributes’ values are included in a given set. Or if its not one of specified values.

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Account extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'subdomain'       => Jam::field('string'),
			'status'          => Jam::field('string'),
		));

		$meta->validator('subdomain', array('choice' => array('not_in' => array('www', 'us', 'ca', 'jp'))));
		$meta->validator('status', array('choice' => array('in' => array('closed', 'opened'))));
	}
}
?>
```
The choice validator has an option 'in' that receives the set of values that will be accepted for the validated attributes. Also it has a 'not_in' option for checking for not accepted values.

### format

This helper validates the attributes' values by testing whether they match a given regular expression or other format, which is specified using the :regex option.

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Product extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'legacy_code'       => Jam::field('string'),
		));

		$meta->validator('legacy_code', array('format' => array('regex' => '/\A[a-zA-Z]+\z/')));
	}
}
?>
```

You can also use PHP's native format validation function - `filter_var`.

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_User extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'email'           => Jam::field('string'),
			'url'             => Jam::field('string'),
		));

		$meta->validator('email', array('format' => array('filter' => FILTER_VALIDATE_EMAIL)));
		$meta->validator('url', array('format' => array('filter' => FILTER_VALIDATE_URL, 'flag' => FILTER_FLAG_PATH_REQUIRED)));
	}
}
?>
```

You can specify the validation criteria with the 'filter' option - use one of PHP's constants - FILTER_VALIDATE_BOOLEAN, FILTER_VALIDATE_EMAIL, FILTER_VALIDATE_FLOAT, FILTER_VALIDATE_INT, FILTER_VALIDATE_IP, FILTER_VALIDATE_REGEXP or FILTER_VALIDATE_URL - those are explained in PHP's documentation [http://www.php.net/manual/en/filter.filters.validate.php](http://www.php.net/manual/en/filter.filters.validate.php)

Additionally you can use Kohana's validations for 'email', 'url' and 'ip'. They call Valid::email(), Valid::url() and Valid::ip() respectively.


```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_User extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'email'           => Jam::field('string'),
			'url'             => Jam::field('string'),
		));

		$meta->validator('email', array('format' => array('email' => TRUE)));
		$meta->validator('url', array('format' => array('url' => TRUE)));
	}
}
?>
```

### length

This validator checks the length of the attributes’ values. It provides a variety of options, so you can specify length constraints in different ways:

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Person extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'                   => Jam::field('primary'),
			'name'                 => Jam::field('string'),
			'bio'                  => Jam::field('text'),
			'password'             => Jam::field('string'),
			'registration_number'  => Jam::field('string'),
		));

		$meta->validator('name', array('length' => array('minimum' => 2)));
		$meta->validator('bio', array('length' => array('maximum' => 500)));
		$meta->validator('password', array('length' => array('between' => array(2, 20))));
		$meta->validator('registration_number', array('length' => array('is' => 6)));
	}
}
?>
```

### range

This validator is used specifically for range fields:

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Person extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'                   => Jam::field('primary'),
			'delivery_time'        => Jam::field('range'),
			'processing_time'      => Jam::field('range'),
			'date_range'           => Jam::field('range'),
		));

		$meta->validator('delivery_time', array('range' => array('minimum' => 2)));
		$meta->validator('processing_time', array('range' => array('maximum' => 500)));
		$meta->validator('date_range', array('range' => array('between' => array(2, 20))));
		$meta->validator('date_range', array('range' => array('consecutive' => TRUE)));
	}
}
?>
```

The possible length constraint options are:

* 'minimum' – The attribute cannot have a value less than the specified.
* 'maximum' – The attribute cannot have a value more than the specified.
* 'between' – The attribute values must be included in a given interval. The value for this option must be an array with 2 values (min, max).
* 'consecutive' – The first value must be less than or equal to the first value.

### numeric

This validator checks that your attributes have only numeric values. By default, it will match an optional sign followed by an integral or floating point number. To specify that only integral numbers are allowed set 'only_integer' to TRUE.

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Player extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'                   => Jam::field('primary'),
			'points'               => Jam::field('integer'),
			'games_played'         => Jam::field('integer'),
		));

		$meta->validator('points', array('numeric' => TRUE)));
		$meta->validator('games_played', array('numeric' => array('only_integer' => TRUE)));
	}
}
?>
```

Besides 'only_integer', this helper also accepts the following options to add constraints to acceptable values:

* 'greater_than' – Specifies the value must be greater than the supplied value.
* 'greater_than_or_equal_to' – Specifies the value must be greater than or equal to the supplied value.
* 'equal_to' – Specifies the value must be equal to the supplied value.
:less_than – Specifies the value must be less than the supplied value.
* 'less_than_or_equal_to' – Specifies the value must be less than or equal the supplied value.
* 'odd' – Specifies the value must be an odd number if set to TRUE.
* 'between' – The attribute must be included in a given interval. The value for this option must be an array with 2 values (min, max).
* 'even' – Specifies the value must be an even number if set to TRUE.

# present

This validator checks that the specified attributes are not empty. It uses the blank? method to check if the value is either nil or a blank string, that is, a string that is either empty or consists of whitespace.

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Person extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'                   => Jam::field('primary'),
			'name'                 => Jam::field('string'),
		));

		$meta->validator('name', array('present' => TRUE)));
	}
}
?>
```

It can validate associations as well, it checks for `->loaded()` for Jam_Model and `->count()` for Jam_Array_Association

### unique

This validator checks that the attribute’s value is unique right before the object gets saved. It does not create a uniqueness constraint in the database, so it may happen that two different database connections create two records with the same value for a column that you intend to be unique. To avoid that, you must create a unique index in your database.

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Account extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'                   => Jam::field('primary'),
			'email'                => Jam::field('string'),
		));

		$meta->validator('email', array('unique' => TRUE)));
	}
}
?>
```

The validation happens by performing an SQL query into the model’s table, searching for an existing record with the same value in that attribute.

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Holiday extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'                   => Jam::field('primary'),
			'name'                 => Jam::field('string'),
			'year'                 => Jam::field('integer'),
		));

		$meta->validator('name', array('unique' => array('scope' => 'year'))));
	}
}
?>
```

There is a 'scope' option that you can use to specify other attributes that are used to limit the uniqueness check:

## Conditional Validation

Sometimes it will make sense to validate an object just when a given predicate is satisfied. You can do that by using the 'if' and 'unless' options, which can take a string or a Callable. You may use the 'if' option when you want to specify when the validation should happen. If you want to specify when the validation should not happen, then you may use the 'unless' option.

### Using a model method or attribute with 'if' and 'unless'

You can associate the 'if' and 'unless' options with a string corresponding to the name of a method or attribute that will get called right before validation happens. This is the most commonly used option.

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Order extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'                   => Jam::field('primary'),
			'card_number'          => Jam::field('string'),
			'transaction_number'   => Jam::field('string'),
			'payment_type'         => Jam::field('string'),
		));

		$meta->validator('name', array('present' => TRUE, 'if' => 'is_paid_with_card()')));
		$meta->validator('transaction_number', array('present' => TRUE, 'if' => 'is_paid')));
	}

	/**
	 * Set this to true to validate the transaction number
	 * @var boolean
	 */
	public $is_paid;


	public function is_paid_with_card()
	{
		return $this->payment_type == 'card';
	}
}
?>
```

### Using a Callable with 'if' and 'unless'

It's possible to associate 'if' and 'unless' with a Callbale (closure, array or string) which will be called. Using a Callable object gives you the ability to write an inline condition instead of a separate method. This option is best suited for one-liners.

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Account extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'         => Jam::field('primary'),
			'password'   => Jam::field('string'),
		));

		$meta->validator('password', array('confirmation' => TRUE, 'unless' => function($account){ return ! $account->passowrd; }));
	}

}
?>
```

### Grouping conditional validations

Sometimes it is useful to have multiple validations use one condition, it can be easily achieved using `with_options`.


```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_User extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'         => Jam::field('primary'),
			'password'   => Jam::field('string'),
			'is_admin'   => Jam::field('string'),
			'email'      => Jam::field('string'),
		));

		$this
			->with_options(array('if' => 'is_admin'))
				->validator('password', array('length' => array('minimum' => 10)))
				->validator('email', array('present' => TRUE))
			->end();
	}
}
?>
```

## Performing Custom Validations

The easiest way to add custom validation is to implement the 'validate' method on the model, it is executed right after the normal validators and you can add your own error messages with `$this->errors()->add('my_error')`


```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_User extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'id'         => Jam::field('primary'),
			'password'   => Jam::field('string'),
			'is_admin'   => Jam::field('string'),
			'email'      => Jam::field('string'),
		));
	}

	public function validate()
	{
		if ($this->is_admin AND ! $this->email)
		{
			$this->errors()->add('invalid_admin');
		}
	}
}
?>
```

## Models only for validation

Sometimes you want to use validation, but without accually having a database table to store the model information in. You can create models that don't have any connection to a database (no save() and delete() methods), but can use all the validations. Jam_Model accually extends Jam_Validated, which encapsulates all the logic that is not associatited with the database, so you can extend it instead of Jam_Model, and use it as if its is a Jam_Model itself.

For example we want to have a login form, but we only want to validate it, without storing anithing in the DB. We just create a Model_Session:

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Session extends Jam_Validated {

	public $login_on_check;

	public static function initialize(Jam_Meta $meta)
	{

		$meta->fields(array(
			'email' => Jam::field('string'),
			'password' => Jam::field('string'),
			'remember_me' => Jam::field('boolean'),
		));

		$meta
			->validator('email', 'password', array('present' => TRUE));
	}

	public function validate()
	{
		if ($this->login_on_check AND $this->is_valid() AND ! $this->login())
		{
			$this->errors()->add('email', 'login');
		}
	}

	public function login()
	{
		return Auth::instance()->login($this->email, $this->password, $this->remember_me);
	}

}
?>
```

We even encapsulte the logging in logic with this model allowing us to validate the form and login with one call to `$session->check()`.
Also we can easily use Jam_Form and its methods to build the form and use all the error messages from it.

```
<?php echo Form::open('/login') ?>
	<?php echo $form->row('input', 'email') ?>
	<?php echo $form->row('input', 'passowrd') ?>

	<div class="remember-row">
		<?php echo $form->row('checkbox', 'remember_me', array('label' => 'Remember me')) ?>
	</div>

	<?php echo Form::button('submit', 'Login') ?>

<?php echo Form::close() ?>
```

