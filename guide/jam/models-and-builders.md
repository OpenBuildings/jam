## Defining models

Models go in the classes / model folder of your application and their initializations happens with the static method `initialize()`. Jam_Meta is the class that holds all the information about the model - its fields, associations and behaviors. It gets initialized only once and holds the information for all the models of this class.

	class Model_Order extends Jam_Model {

		public static function initialize(Jam_Meta $meta)
		{
			$meta->field('title', Jam::field('name'));
			$meta->name_key('title');
			$meta->behaviors(array(
				'sluggable' => Jam::behavior('sluggable')
			));
		}
	}

### Jam_Meta

The `Jam_Meta` object has a lot of methods to help you configure the model.

* db()
* model()
* table()
* builder()
* field()
* field_insist()
* fields()
* association()
* association_insist()
* associations()
* defaults()
* errors_filename()
* behaviors()
* events()
* primary_key()
* name_key()
* sorting()

#### db()

Using `db()` you can set / get the database that this model uses. That way you can have models talk to different databases of different types

	class Model_Order extends Jam_Model {

		public static function initialize(Jam_Meta $meta)
		{
			$meta->db('testing');

			// ...
		}
	}

##### table()

By default the database table that is association with the model is the plural form of the model

<pre>
model            | table
-----------------------------------
order            | orders
customer_profile | customer_profiles
best_of_user     | best_of_users
</pre>

But you can change the table name by using this method as a setter

```php
<?php
$meta->table('funky_table_name');
?>
```

##### model()

Get the name of the model as a string with the `model()` method

```php
<?php
class Model_Order extends Jam_Model {
	// ...
}

$order = Jam::find('order', 1);
echo $order->meta()->model();        // 'order'
?>
```

#### field(), field_insist(), fields()

Using `field()` you can set and retrieve a field for the model. If you want to make sure the field exists use `field_insist()` it will throw a Kohana_Exception if the field is not present in the model. And lastly you can set multiple fields with `fields()`.

```php
<?php
$meta->field('title', Jam::field('string'));
$meta->fields(array(
	'type' =>  Jam::field('string')
	'size' =>  Jam::field('integer')
));

$meta->field('title');               // Jam_Field('string');

$meta->field_insist('orange');       // throws Kohana_Exception
?>
```
#### association(), association_insist(), associations()

Using `association()` you can set and retrieve an association for the model. If you want to make sure the association exists use `association_insist()` it will throw a Kohana_Exception if the association is not present in the model. And lastly you can set multiple associations with `fields()`.

```php
<?php
$meta->association('title', Jam::association('string'));
$meta->associations(array(
	'type' =>  Jam::association('string')
	'size' =>  Jam::association('integer')
));

$meta->association('title');         // Jam_Association('string');

$meta->association_insist('orange'); // throws Kohana_Exception
?>
```

#### defaults()

You can use this to get all the defaults for this model or only the defaults for a given field. You cannot set defaults using this method.

```php
<?php
$meta->defaults('title');            // ''
$meta->defaults();                   // array('title' => '', 'size' => 0)
?>
```

#### errors_filename()

By default Jam uses `messages/jam/{model name}.php` file for error messages of the model. but you can override this with the `errors_filename()` method. It acts as a getter to so if you're not sure of the name of the error messages file you can use this method to find out.

```php
<?php
$meta->errors_filename('messages/errors/order.php');
echo $meta->errors_filename();       // 'messages/errors/order.php'
?>
```

#### behaviors()

Get or set behaviors with `behaviors()` - you can either pass an array to set them, or you can pass a string name to retrieve a behavior

```php
<?php
$meta->behaviors(array(
	'sluggable' => Jam::behavior('sluggable')
));

echo $meta->behaviors('sluggable');
?>
```

#### events()

To access `Jam_Event` object directly use `events()` method - This object acts as an event dispatcher for all the models and you can add and trigger events directly through this object. Read up on behaviors if you want to understand events better.

```php
<?php
$meta->events()->bind('model.after_save', 'Model_Order::after_save_action');

// This is very low level and there shouldn't be a case where you need to use this
$meta->events()->trigger('model.after_save', $model, array("args"));
?>
```

#### primary_key()

Get or set the primary key of a model with `primary_key()` method. The default primary key name is "id";

```php
<?php
$meta->primary_key('uid');
?>
```

Later this field will be used when you call `$model->id()` method to get the primary_key of the object

#### name_key()

Get or set the name key of a model with `name_key()` method. The default name key is "name";

```php
<?php
$meta->name_key('title');
?>
```

Later this field will be used when you call `$model->name()` method to get the name_key of the object and will also be used in various places where we need the textual representation of the object.

#### sorting()

Set or get the default sorting of the model. This is going to be used as the default sorting for builder queries on this model.

```php
<?php
$meta->sorting(array(
	'title' => 'DESC',
	'id' => 'ASC'
));
?>
```

#### unique_key()

This method is designed to be extended to provide easier way of finding objects in the database based on a unique key. What it does is returns the name of the unique column based on the value. By default if its a numeric value it will return the primary_key, otherwise - the name_key. But this can be extended to recognize other patterns.

```php
<?php
class Model_Order extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->fields(array(
			'ip' => Jam::field('string'),
			'email' => Jam::field('string'),
			'name' => Jam::field('string'),
		));

		// ...
	}

	public static function unique_key($value)
	{
		if (Valid::ip($value))
		{
			return 'ip';
		}

		if (Valid::email($value))
		{
			return 'email';
		}

		return 'id';
	}
}

// Find the order based on the ip field
$order = Jam::find('order', '10.20.10.1');

// Find the order based on the email field
$order = Jam::find('order', 'joe@example.com');
?>
```
