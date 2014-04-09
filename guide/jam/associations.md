## The Types of Associations

With Jam, an association is a connection between two Jam models. Associations are implemented using macro-style calls, so that you can declaratively add features to your models. For example, by declaring that one model `belongsto` another, you instruct Jam to maintain Primary Key–Foreign Key information between instances of the two models, and you also get a number of utility methods added to your model. Jam supports four types of associations:

* belongsto
* hasone
* hasmany
* manytomany

In the remainder of this guide, you'll learn how to declare and use the various forms of associations. But first, a quick introduction to the situations where each association type is appropriate.

### The belongsto Association

A belongsto association sets up a one-to-one connection with another model, such that each instance of the declaring model "belongs to" one instance of the other model. For example, if your application includes customers and orders, and each order can be assigned to exactly one customer, you’d declare the order model this way:

```php
<?php
class Model_Order extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('customer', Jam::association('belongsto'));

		$meta->fields(array(
			'id'         => Jam::field('primary'),
			'order_date' => Jam::field('timestamp'),
		));
	}
}
?>
```

<pre>
┌───────────────────────┐
│ Model: ORder          │
│ belongsto: customer   │    ┌───────────────────────┐
├─────────────┬─────────┤    │ Model: Customer       │
│ id          │ ingeter │    ├───────────────────────┤
│ customer_id │ ingeter │───>│ id          | ingeter │
│ order_date  │ date    │    │ name        | string  │
└─────────────┴─────────┘    └───────────────────────┘
</pre>


### The hasone Association

A `hasone` association also sets up a one-to-one connection with another model, but with somewhat different semantics (and consequences). This association indicates that each instance of a model contains or possesses one instance of another model. For example, if each supplier in your application has only one account, you'd declare the supplier model like this:


```php
<?php
class Model_Supplier extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('account', Jam::association('hasone'));

		$meta->fields(array(
			'id'         => Jam::field('primary'),
			'name'       => Jam::field('string'),
		));
	}
}
?>
```

<pre>
┌───────────────────────┐    ┌───────────────────────┐
│ Model: Supplier       │    │ Model: Account        │
│ hasone: account       │    ├─────────────┬─────────│
├─────────────┬─────────┤    │ id          │ ingeter │
│ id          │ ingeter │◄───│ supplier_id │ ingeter │
│ name        │ string  │    │ account_num │ string  │
└─────────────┴─────────┘    └─────────────┴─────────┘
</pre>

### The hasmany Association

A `hasmany` association indicates a one-to-many connection with another model. You'll often find this association on the "other side" of a `belongsto` association. This association indicates that each instance of the model has zero or more instances of another model. For example, in an application containing customers and orders, the customer model could be declared like this:

```php
<?php
class Model_Customer extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('orders', Jam::association('hasmany'));

		$meta->fields(array(
			'id'         => Jam::field('primary'),
			'name'       => Jam::field('string'),
		));
	}
}
?>
```

<pre>
┌───────────────────────┐    ┌───────────────────────┐
│ Model: Customer       │    │ Model: Order          │
│ hasmany: orders       │    ├─────────────┬─────────┤
├─────────────┬─────────┤    │ id          │ ingeter │
│ id          │ ingeter │◄───│ supplier_id │ ingeter │
│ name        │ string  │    │ account_num │ string  │
└─────────────┴─────────┘    └─────────────┴─────────┘
</pre>


### The manytomany Association

A `manytomany` association creates a many-to-many connection with another model. For example, if your application includes assemblies and parts, with each assembly having many parts and each part appearing in many assemblies, you could declare the models this way:

```php
<?php
class Model_Assembly extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('parts', Jam::association('manytomany'));

		$meta->fields(array(
			'id'         => Jam::field('primary'),
			'name'       => Jam::field('string'),
		));
	}
}

class Model_Part extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('parts', Jam::association('manytomany'));

		$meta->fields(array(
			'id'         => Jam::field('primary'),
			'name'       => Jam::field('string'),
		));
	}
}
?>
```

<pre>
┌───────────────────────┐
│ Model: Assembly       │
│ manytomany: parts     │
├─────────────┬─────────┤
│ id          │ ingeter │◄──┐  ┌────────────────────────┐
│ name        │ string  │   │  │ Table:assemblies_parts │
└─────────────┴─────────┘   │  ├─────────────┬──────────┤
                            └──│ assembly_id │ ingeter  │
┌───────────────────────┐   ┌──│ part_id     │ string   │
│ Model: Parts          │   │  └─────────────┴──────────┘
│ manytomany: assemblies│   │
├─────────────┬─────────┤   │
│ id          │ ingeter │◄──┘
│ name        │ string  │
└─────────────┴─────────┘
</pre>

## Choosing Between belongsto and hasone

If you want to set up a one-to-one relationship between two models, you'll need to add `belongsto` to one, and `hasone` to the other. How do you know which is which?

The distinction is in where you place the foreign key (it goes on the table for the class declaring the `belongsto` association), but you should give some thought to the actual meaning of the data as well. The `hasone` relationship says that one of something is yours - that is, that something points back to you. For example, it makes more sense to say that a supplier owns an account than that an account owns a supplier. This suggests that the correct relationships are like this:

```php
<?php
class Model_Supplier extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('account', Jam::association('hasone'));
		// ...
	}
}

class Model_Account extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('supplier', Jam::association('belongsto'));
		// ...
	}
}
?>
```

## Polymorphic Associations

A slightly more advanced twist on associations is the polymorphic association. With polymorphic associations, a model can belong to more than one other model, on a single association. For example, you might have a picture model that belongs to either an employee model or a product model. Here's how this could be declared:


```php
<?php
class Model_Picture extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('imageable', Jam::association('belongsto', array('polymorphic' => TRUE)));
		// ...
	}
}

class Model_Employee extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('pictures', Jam::association('hasmany', array('as' => 'imageable')));
		// ...
	}
}

class Model_Product extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('pictures', Jam::association('hasmany', array('as' => 'imageable')));
		// ...
	}
}
?>
```

You can think of a polymorphic `belongsto` declaration as setting up an interface that any other model can use. From an instance of the Model_Employee, you can retrieve a collection of pictures: $employee->pictures.

Similarly, you can retrieve $product->pictures.

If you have an instance of the Model_Picture, you can get to its parent via $picture->imageable. To make this work, you need to declare both a foreign key column and a type column in the model that declares the polymorphic interface:

<pre>
┌───────────────────────────────────┐
│ Model: Employee                   │
│ hasmany: pictures, as => imagable │      ┌──────────────────────────┐
├─────────────────────────┬─────────┤      │ Model: Model             │
│ id                      │ ingeter │◄──┐  │ belongsto: imagable,     │
│ name                    │ string  │   │  │   polymorphic => TRUE    │
└─────────────────────────┴─────────┘   │  ├────────────────┬─────────┤
                                        │  │ id             │ ingeter │
                                        │  │ name           │ string  │
┌───────────────────────────────────┐   ├──│ imagable_id    │ ingeter │
│ Model: Product                    │   │  │ imagable_model │ string  │
│ hasmany: pictures, as => imagable │   │  └────────────────┴─────────┘
├─────────────────────────┬─────────┤   │
│ id                      │ ingeter │◄──┘
│ name                    │ string  │
└─────────────────────────┴─────────┘
</pre>

## Self Joins

In designing a data model, you will sometimes find a model that should have a relation to itself. For example, you may want to store all employees in a single database model, but be able to trace relationships such as between manager and subordinates. This situation can be modeled with self-joining associations:

```php
<?php

class Model_Employee extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('subordinates', Jam::association('hasmany', array('foreign_model' => 'employee', 'foreign_key' => 'manager_id')));
		$meta->association('manager', Jam::association('belongsto', array('foreign_model' => 'employee', 'foreign_key' => 'manager_id')));
		// ...
	}
}

?>
```

## Tips, Tricks, and Warnings

Here are a few things you should know to make efficient use of Jam associations in your Jam applications:

* Controlling caching
* Avoiding name collisions
* Updating the schema
* Controlling association scope
* Bi-directional associations

### Controlling Caching

All of the association methods are built around caching, which keeps the result of the most recent query available for further operations. The cache is even shared across methods. For example:

```php
<?php
$customer->orders;                             // retrieves orders from the database
$customer->orders->count();                    // uses the cached copy of orders
foreach ($customer->orders as $order);          // uses the cached copy of orders
?>
```

But what if you want to reload the cache, because data might have been changed by some other part of the application? Just use the `reload()` method on the association:


```php
<?php
$customer->orders;                             // retrieves orders from the database
$customer->orders->count();                    // uses the cached copy of orders
foreach($customer->orders->reload() as $order) // discards the cached copy of orders
                                               // and goes back to the database
?>
```

### Avoiding Name Collisions

You are not free to use just any name for your associations. Because creating an association adds a field with that name to the model, it is a bad idea to give an association a name that is already used Jam Fields. The association will overwrite the field, breaking things. For instance, its bad to have "profile" as both field and association in your model.

### Updating the Schema

Associations are extremely useful, but they are not magic. You are responsible for maintaining your database schema to match your associations. In practice, this means two things, depending on what sort of associations you are creating. For `belongsto` associations you need to create foreign keys, and for manytomany associations you need to create the appropriate join table.

#### Creating Foreign Keys for belongsto Associations

When you declare a belongs_to association, you need to create foreign keys as appropriate. For example, consider this model:

```php
<?php

class Model_Order extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('customer', Jam::association('belongsto'));

		$meta->fields(array(
			'id'         => Jam::field('primary'),
			'order_date' => Jam::field('timestamp'),
		));
	}
}
?>
```

This declaration needs to be backed up by the proper foreign key declaration on the orders table:

<pre>
┌───────────────────────┐
│ Model: Order          │
├─────────────┬─────────┤
│ id          │ ingeter │
│ customer_id │ ingeter │
│ order_date  │ date    │
└─────────────┴─────────┘
</pre>

If you create an association some time after you build the underlying model, you need to remember to add the column to your database table to provide the necessary foreign key.

#### Creating Join Tables for manytomany Associations

If you create a manytomany association, you need to explicitly create the joining table. Unless the name of the join table is explicitly specified by using the 'through' option, Jam creates the name by using the lexical order of the class names. So a join between customer and order models will give the default join table name of "customers_orders" because "c" outranks "o" in lexical ordering.

> __Be careful__ The precedence between model names is calculated using the < operator for a string. This means that if the strings are of different lengths, and the strings are equal when compared up to the shortest length, then the longer string is considered of higher lexical precedence than the shorter one. For example, one would expect the tables "paper_boxes" and "papers" to generate a join table name of "papers_paper_boxes" because of the length of the name "paper_boxes", but it in fact generates a join table name of "paper_boxes_papers" (because the underscore ‘_’ is lexicographically less than 's' in common encodings).

Whatever the name, you must manually generate the join table with an appropriate migration. For example, consider these associations:

```php
<?php
class Model_Assembly extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('parts', Jam::association('manytomany'));
		// ...
	}
}

class Model_Part extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('parts', Jam::association('manytomany'));

		// ...
	}
}
?>
```

You need to manually create the joining table `assemblies_parts` in your database with foreign keys `assembly_id` and `part_id`.

## Bi-directional Associations

It' normal for associations to work in two directions, requiring declaration on two different models:

```php
<?php
class Model_Customer extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('orders', Jam::association('hasmany'));
		// ...
	}
}

class Model_Order extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('customer', Jam::association('belongsto'));

		// ...
	}
}
?>
```

By default, Jam doesn't know about the connection between these associations. This can lead to two copies of an object getting out of sync:

```php
<?php
$customer = Jam::all('customer')->first();
$order = $customer->orders[0];
$customer->first_name == $order->customer->first_name; // TRUE
$customer->first_name = 'Manny';
$customer->first_name == $order->customer->first_name; // FALSE
?>
```

This happens because $customer and $order->customer are two different in-memory representations of the same data, and neither one is automatically refreshed from changes to the other. Jam provides the `inverse_of` option so that you can inform it of these relations:

```php
<?php
class Model_Customer extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('orders', Jam::association('hasmany', array('inverse_of' => 'customer')));
		// ...
	}
}

class Model_Order extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('customer', Jam::association('belongsto', array('inverse_of' => 'orders')));

		// ...
	}
}
?>
```

With these changes, Jam will only load one copy of the customer object, preventing inconsistencies and making your application more efficient:

```php
<?php
$customer = Jam::all('customer')->first();
$order = $customer->orders[0];
$customer->first_name == $order->customer->first_name; // TRUE
$customer->first_name = 'Manny';
$customer->first_name == $order->customer->first_name; // TRUE
?>
```

## Association Collections

When you want to retrieve `hasmany` and `manytomany` associations, you receive a `Jam_Array_Association` object which behaves like an array (Implements all the array interfaces) so you can iterate through it with `foreach`, retrieve individual rows with `[]` or even add items to it.

It is important to note that `Jam_Array_Association` utilizes lazy loading so the SQL query to retrieve the objects from the database is executed at the last possible moment (in a `foreach` or `[]`).

```php
<?php
class Model_Supplier extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('orders', Jam::association('hasmany'));

		// ...
	}
}

class Controller_Supliers extends Controller_Template {

	public function action_show()
	{
		$supplier = Jam::find('supplier', $this->request->parma('id'));

		// This line will not perform an SQL query, just create a Jam_Query_Builder_Collection object
		$this->template->content = View::factory("suppliers/show", array('orders' => $supplier->orders));
	}
}
?>
```

So when you get the orders inside `suppliers/show` view, only then will the SQL Query execute.
```php
<ul>
	<?php foreach ($orders as $order): ?>
		<li><?php echo $order->name() ?></li>
	<?php endforeach ?>
</ul>
?>
```

You also have the ability to modify this Jam_Array_Association, by adding or removing models, using the array interface:

```php
<?php
$customer->orders[] = Jam::create('order');  // Add a newly created order
$customer->orders[0] = Jam::create('order'); // Replace the first order with a newly created one
unset($customer->orders[1]);                 // Remove the second order
?>
```

### Helper Methods for Jam_Array_Association

Along with the basic array stuff, `Jam_Array_Association` implements some useful helper methods:

* model()
* meta()
* as_array()
* reload()
* add()
* remove()
* set()
* ids()
* content()
* original()
* original_ids()
* clear()
* changed()
* changed_check()
* changed_save()
* save()
* association()
* parent()
* build()
* create()
* search()
* has()

### meta(), model()

Get the meta of the model being retrieved. Or the model name itself. For example:

```php
<?php
class Model_Supplier extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('orders', Jam::association('hasmany'));

		// ...
	}
}

$supplier = Jam::find('supplier', 1);

echo $supplier->orders->meta() // Jam_Meta object for the Model_Order class
echo $supplier->orders->model() // 'order'
?>
```

### as_array()

Get the contents of the `Jam_Array_Association` as an array, this get rid of the `Jam_Array_Association` object, and gives you a simple array of `Jam_Model` objects. `as_array()` method is a lot more powerful though:

```
<?php

class Model_Supplier extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('orders', Jam::association('hasmany'));

		// ...
	}
}

// You can get the array indexed by "id" or any other field like this
$supplier->orders->as_array('id');         // array(1 => Model_Order(), 27 => Model_Order())
$supplier->orders->as_array('name');       // array('first order' => Model_Order(), 'second order' => Model_Order())

// You can get an array only with one field
$supplier->orders->as_array(NULL, 'name'); // array('first order', 'second order')

// Or you can mixed the two and get an array of names indexed by id
$supplier->orders->as_array('id', 'name'); // array(1 => 'first order', 27 => 'second order')

// Or even the other way around
$supplier->orders->as_array('name', 'id'); // array('first order' => 1, 'second order' => 27)
?>
```

### reload()

Forces the association to get reloaded. This way the next `foreach` or `[]` operation will trigger a fresh SQL query

```
<?php
// Perform the SQL query
foreach ($supplier->orders as $order)
{
	// ... $order
}

$supplier->orders->reload();

// Perform the SQL query again
echo $supplier->orders[1];
?>
```
### add()

Add an object to the association. The object will be added to the current `Jam_Array_Association` object and will be saved when your save the parent object. Also if you have defined `inverse_of` on the filed, then when you `add()` an object to a collection, then the inverse association is set as well. You can use different representations of the object that you pass to `add()` - you can pass a primary_key a name_key or the object itself:

```php
<?php
class Model_Supplier extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('orders', Jam::association('hasmany'));

		// ...
	}
}

$supplier = Jam::find('supplier', 1);
$order = Jam::find('order', 20);
$supplier->orders->add($order);

// Assigns the parent
echo $suplier->id() == $order->supplier->id(); // TRUE

// The user is already present in the collection even though its not saved yet.
echo $supplier->orders->has($order); // TRUE

// Save associations.
$supplier->save();

// The new association is persisted in the database.
echo Jam::find('supplier', 1)->orders->has($order); // TRUE

// Add by primary key
$supplier->orders->add(21);

// Add by name key
$supplier->orders->add('last order');

// Add multiple items
$supplier->orders->add(array(1, 2, 10));

// Add a collection of objects (adds all the objects)
$supplier->orders->add(Jam::all('order')->where('price', '<', 10));
?>
```

### remove()

Remove an object from the association. The object will be removed from the current `Jam_Array_Association` object and will be saved when your save the parent object. You can use different representations of the object that you pass to `remove()` - you can pass a primary_key a name_key or the object itself

```php
<?php
class Model_Supplier extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('orders', Jam::association('hasmany'));

		// ...
	}
}

$supplier = Jam::find('supplier', 1);
$order = Jam::find('order', 20);
$supplier->orders->remove($order);

// The user is already present in the collection even though its not saved yet.
echo $supplier->orders->has($order); // FALSE

// Save associations.
$supplier->save();

// The new association is persisted in the database.
echo Jam::find('supplier', 1)->orders->has($order); // FALSE

// Remove by primary key
$supplier->orders->remove(21);

// Remove by name key
$supplier->orders->remove('last order');

// Remove multiple items
$supplier->orders->remove(array(1, 2, 10));

// Remove a collection of objects (removes all the objects)
$supplier->orders->remove(Jam::all('order')->where('price', '<', 10))
?>
```

### ids()

Using `ids()` you can get all the ids of the objects in the association.


```php
<?php
class Model_Supplier extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('orders', Jam::association('hasmany'));

		// ...
	}
}

$supplier = Jam::find('supplier', 1);

echo $supplier->orders->ids(); // Array with the ids

?>
```

### content()

If you want to get (or set) the row data from the database, you can use the 'content()' Getter / Setter. This is a very low level method and you should only using when building extensions and other low - level coding, generally you should not mess with this.

```php
<?php
class Model_Supplier extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('orders', Jam::association('hasmany'));

		// ...
	}
}

$supplier = Jam::find('supplier', 1);

echo $supplier->orders->content(); // Array of all the orders data from the database

$suplier->orders->content(array(
	array('id' => 1, 'name' => 'Order 1'),
	array('id' => 3, 'name' => 'Order 2'),
));

?>
```

### original()

Getter - the original data from the database. This cannot be changed.

### original_ids()

Getter - get the ids of all the models originally loaded from the database.


### clear()

Clear the contents of the association leaving an empty `Jam_Array_Association`, and saves the result to the database imidiately

```php
<?php
class Model_Supplier extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('orders', Jam::association('hasmany'));

		// ...
	}
}

$supplier = Jam::find('supplier', 1);

// Lets assume we have some orders
echo $supplier->orders; // Jam_Collection: Model_Order(2)

$supplier->orders->clear();

echo $supplier->orders; // Jam_Collection: Model_Order(0)

$supplier->save();

echo Jam::find('supplier', 1)->orders; // Jam_Collection: Model_Order(0)
?>
```

### changed()

A boolean getter to find out if the collection has been changed. It is considered changed if elements have been added or removed or if it has been cleared or set through `set()`. You can pass an offset to see if a specific entry has been changed.

```php
<?php
class Model_Supplier extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('orders', Jam::association('hasmany'));

		// ...
	}
}

$supplier = Jam::find('supplier', 1);
echo $supplier->orders->changed(); // Returns FALSE
echo $supplier->orders->changed(2); // Returns FALSE

$suplier->orders[2] = Jam::create('order');

echo $supplier->orders->changed(); // Returns TRUE
echo $supplier->orders->changed(1); // Returns FALSE
echo $supplier->orders->changed(2); // Returns TRUE


?>
```

### changed_check()

Call `changed_check()` method to check if any of the changed items are valid. Basically performs a check() on each added / updated item and returns FALSE if at least one is not valid.

### changed_save()

Call `changed_save()` method to call a "save()" method on every changed item in this array (added / updated).

### save()

Save the association in its present condition (it calls changed_check() and changed_save() internally.), without the need to call ->save() on the parent model. This is called automatically if the parent model is saved.

```php
<?php
class Model_Supplier extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('orders', Jam::association('hasmany'));

		// ...
	}
}

$supplier = Jam::find('supplier', 1);

$suplier->orders[2] = Jam::create('order');

$suplier->orders->save();
?>
```

### parent()

This method will return the parent model that requested the association.

```php
<?php
class Model_Supplier extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('orders', Jam::association('hasmany'));

		// ...
	}
}

$supplier = Jam::find('supplier', 1);

// The parent is the supplier
echo $supplier->orders->parent() === $supplier; // TRUE
?>
```

### build()

You can build an new object for the association and have it assigned to the collection. Does not work with polymorphic associations.

```
<?php
class Model_Supplier extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('orders', Jam::association('hasmany'));

		// ...
	}
}

$supplier = Jam::find('supplier', 1);

// The parent is the supplier
$order = $supplier->orders->build(array('name' => 'new name'));

// Newly created order
echo $order; // Model_Order(NULL)

// The new order is automatically added to the collection
echo $supplier->orders->has($order); // TRUE
?>
```

### create()

The same as `build()` but actually created the object in the database.

### search()

If you want to find out the key of an element in the collection you can use the `search()` method. You can pass an object argument, a name_key or a primary_key. The search will be performed based on primary key so even if you've created the object later and it does not use the exact same object, if the primary keys match then you will find your object.

### has()

If you want to check if an element is in the collection you can use the `has()` method. You can pass an object argument, a name_key or a primary_key. The search will be performed based on primary key so even if you've created the object later and it does not use the exact same object, if the primary keys match then you will find your object.

```php
<?php
class Model_Supplier extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('orders', Jam::association('hasmany'));

		// ...
	}
}

$supplier = Jam::find('supplier', 1);
$order = Jam::find('order', 1)

// Check with Jam_Model object
echo $supplier->orders->has($order);

// Check with id of an
echo $supplier->orders->has(1);

// Check with name key
echo $supplier->orders->has('first order');
?>
```


### Mass Assignment

Jam provides a way to set the whole content of an association with nested arrays. This is particularly useful when you have nested forms on your site and you want to save them all in one single save call. Without mass assignment it might get tricky if some of the associated models exist in the database and some must be created. Jam takes care of all of that for you - saving all the items in the correct order so you don't have to worry about this stuff. For example lets assume we have those 2 models

```php
<?php
class Model_Customer extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('orders', Jam::association('hasmany'));

		// ...
	}
}

class Model_Order extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('customer', Jam::association('belongsto'));

		$meta->fields(array(
			'name' => Jam::field('string'),
			'price' => Jam::field('float'),
		));
	}
}
?>
```

You can set all the orders in one single assignment, creating the objects in the process:

```php
<?php
$customer = Jam::find('customer', 1);
$customer->orders = array(
	array(
		'name' => 'one order',
		'price' => 10.2
	),
	array(
		'name' => 'second order',
		'price' => 20.5
	),
	array(
		'id' => 4,
		'name' => 'change title 4'
	)
);

$customer->save();
?>
```
When you perform `$customer->save();` the objects themselves are created and the associations are saved. If you have an "id" in the array, then it will load that object and change its fields with the passed other parameter. In the example above the `Model_Order` with id of 4 will be added to customer 1's orders and its title will be changed to 'change title 4'. The other 2 orders will be created and assign to customer 1.

There's one more cool feature, after you perform the assignment, you can get the item and instead of the array it will return the actual `Model_Order` object (not saved yet).

```php
<?php
$customer = Jam::find('customer', 1);
$customer->orders = array(
	array(
		'name' => 'one order',
		'price' => 10.2
	),
	array(
		'name' => 'second order',
		'price' => 20.5
	),
);

// Get the first array, but converted to a Model_Order object
echo $customer->orders[0]; // Model_Order(NULL)
echo $customer->orders[0]->name; // 'one order'
?>
```

Polymorphic associations also can be populated with mass assignments, but you will have to wrap them in an array where the key is the name of the new object's model.

```php
<?php
class Model_Picture extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('imageable', Jam::association('belongsto', array('polymorphic' => TRUE)));
		$meta->fields(array(
			'name' => Jam::field('string'),
			'file' => Jam::field('image'),
		))
	}
}

class Model_Employee extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('pictures', Jam::association('hasmany', array('as' => 'imageable')));

		// ...
	}
}

$employee = Jam::find('employee', 1);
$employee->pictures = array(
	array(
		'picture' => array(
			'name' => 'Mug Shot',
			'file' => 'mugshot.jpg'
		)
	),
	array(
		'picture' => array(
			'name' => 'Mug Shot 2',
			'file' => 'mugshot2.jpg'
		)
	),
);

echo $employee->picture[0]; // Model_Picture(Null)
echo $employee->picture[0]->name; // 'Mug Shot'

?>
```

## Detailed Association Reference

The following sections give the details of each type of association, including the methods that they add and the options that you can use when declaring an association.

### belongsto Association Reference

The `belongsto` association creates a one-to-one match with another model. In database terms, this association says that this class contains the foreign key. If the other class contains the foreign key, then you should use `hasone` instead.

While Jam uses intelligent defaults that will work well in most situations, there may be times when you want to customize the behavior of the `belongsto` association reference. Such customizations can easily be accomplished by passing options when you create the association. For example, this association uses two such options:

```php
<?php
class Model_Order extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('customer', Jam::association('belongsto', array(
			'foreign_model' => 'order_customer',
			'foreign_key' => 'order_customer_uid'
		)));

		// ...
	}
}
?>
```

The `belongsto` association supports these options:

* foreign_key
* dependent
* foreign_model
* inverse_of
* label
* model
* name
* polymorphic

#### column

By convention, Jam assumes that the foreign_key used to hold the foreign key on this model is the name of the association with the suffix _id added. The `foreign_key` option lets you set the name of the foreign key directly:

```php
<?php
class Model_Order extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('customer', Jam::association('belongsto', array(
			'foreign_key' => 'customer_identifier'
		)));

		// ...
	}
}
?>
```

#### dependent

If you set the `dependent` option to `Jam_Association::DELETE`, then deleting this object will call the delete method on the associated object to delete that object. If you set the `dependent` option to `Jam_Association::ERASE`, then deleting this object will delete the associated object without calling its delete method.

> __Be careful__ You should not specify this option on a `belongsto` association that is connected with a `hasmany` association on the other class. Doing so can lead to orphaned records in your database.

#### foreign_model

The `foreign_model` option is used to configure the associated model.

```php
<?php
class Model_Order extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('customer', Jam::association('belongsto', array(
			'foreign_model' => 'customer'
		)));

		// ...
	}
}

$order = Jam::build('order');

echo $order->customer_id; // NULL instead of 0
?>
```

#### inverse_of

The `inverse_of` option specifies the name of the `hasmany` or `hasone` association that is the inverse of this association. Does not work in combination with the `polymorphic` options.

```php
<?php
class Model_Supplier extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('orders', Jam::association('hasmany', array('inverse_of' => 'customer'));

		// ...
	}
}

class Model_Order extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('customer', Jam::association('belongsto', array('inverse_of' => 'orders')));

		// ...
	}
}

?>
```

#### label

Define the human readable name of the field this is used in automatically generated error messages for example. By default uses `Inflector::humanize()` to generate this based on the name

#### model

This is defined automatically as the model setting the association, but in very rare cases can be overridden.

#### name

Change the name of the association - this name will be used in cache keys and such - you can use this to resolve naming conflicts.

#### polymorphic

Passing TRUE to the `polymorphic` option indicates that this is a polymorphic association. Polymorphic associations were discussed in detail earlier in this guide.

### hasone Association Reference

The `hasone` association creates a one-to-one match with another model. In database terms, this association says that the other class contains the foreign key. If this class contains the foreign key, then you should use `belongsto` instead.

While Jam uses intelligent defaults that will work well in most situations, there may be times when you want to customize the behavior of the `hasone` association reference. Such customizations can easily be accomplished by passing options when you create the association. For example, this association uses two such options:

```php
<?php

class Model_Supplier extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('order', Jam::association('hasone', array(
			'foreign_model' => 'customer_order',
			'foreign_key' => 'order_uid',
			'label' => 'Purchase'
		)));

		// ...
	}
}
?>
```

The `hasone` association supports these options:

* as
* dependent
* foreign_model
* foreign_key
* polymorphic_key
* inverse_of
* label
* model
* name

#### as

Setting the `as` option indicates that this is a polymorphic association. Polymorphic associations were discussed in detail earlier in this guide.

#### dependent

If you set the `dependent` option to `Jam_Association::DELETE`, then deleting this object will call the delete method on the associated object to delete that object. If you set the `dependent` option to Jam_Association::ERASE, then deleting this object will delete the associated object without calling its delete method. If you set the `dependent` option to `Jam_Association::NULLIFY`, then deleting this object will set the foreign key in the association object to NULL.

#### foreign_model

The `foreign` option is used to configure the associated model.

```php
<?php
class Model_Customer extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('order', Jam::association('hasone', array(
			'foreign_model' => 'order'
		)));

		// ...
	}
}
?>

```

#### foreign_key

The `foreign_key` option is used to configure the field pointing to the associated model a You can pass a string with the model name.

```php
<?php
class Model_Customer extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('order', Jam::association('hasone', array(
			'foreign_key' => 'my_order_id'
		)));

		// ...
	}
}
?>
```

#### polymorphic_key

You can use the `polymorphic_key` option to customize the name of the field, use for polymorphic asociaiton. By default its `$as.'_model'`.

#### inverse_of

The `inverse_of` option specifies the name of the `belongto` association that is the inverse of this association.

```php
<?php
class Model_Supplier extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('account', Jam::association('hasone', array(
			'inverse_of' => 'supplier'
		)));

		// ...
	}
}

class Model_Account extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('supplier', Jam::association('belongsto', array(
			'inverse_of' => 'account'
		)));

		// ...
	}
}
?>
```

#### label

Define the human readable name of the field this is used in automatically generated error messages for example. By default uses `Inflector::humanize()` to generate this based on the name

#### model

This is defined automatically as the model setting the association, but in very rare cases can be overridden.

#### name

Change the name of the association - this name will be used in cache keys and such - you can use this to resolve naming conflicts.

### hasmany Association Reference

The `hasmany` association creates a one-to-many relationship with another model. In database terms, this association says that the other class will have a foreign key that refers to instances of this class.

While Jam uses intelligent defaults that will work well in most situations, there may be times when you want to customize the behavior of the `hasmany` association reference. Such customizations can easily be accomplished by passing options when you create the association. For example, this association uses two such options:

```php
<?php
class Model_Customer extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('order', Jam::association('hasmany', array(
			'foreign_model' => 'order_customer',
			'foreign_key' => 'order_customer_uid'
		)));

		// ...
	}
}
?>
```

The `hasmany` association supports these options:

* as
* dependent
* extend
* foreign_model
* foreign_key
* polymorphic_key
* inverse_of
* model
* label
* name

#### as

Setting the `as` option indicates that this is a polymorphic association. Polymorphic associations were discussed in detail earlier in this guide.

#### dependent

If you set the `dependent` option to `Jam_Association::DELETE`, then deleting this object will call the delete method on the associated objects to delete that object. If you set the `dependent` option to `Jam_Association::ERASE`, then deleting this object will delete the associated objects without calling its delete method. If you set the `dependent` option to `Jam_Association::NULLIFY`, then deleting this object will set the foreign key in the association objects to NULL.

#### foreign_model

The `foreign` option is used to configure the associated model.

```php
<?php
class Model_Customer extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('order', Jam::association('hasone', array(
			'foreign_model' => 'order'
		)));

		// ...
	}
}
?>

```

#### foreign_key

The `foreign_key` option is used to configure the field pointing to the associated model a You can pass a string with the model name.

```php
<?php
class Model_Customer extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('order', Jam::association('hasone', array(
			'foreign_key' => 'my_order_id'
		)));

		// ...
	}
}
?>
```

#### polymorphic_key

You can use the `polymorphic_key` option to customize the name of the field, use for polymorphic asociaiton. By default its `$as.'_model'`.

#### inverse_of

The `inverse_of` option specifies the name of the `belongto` association that is the inverse of this association.

```php
<?php
class Model_Supplier extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('account', Jam::association('hasmany', array(
			'inverse_of' => 'supplier'
		)));

		// ...
	}
}

class Model_Account extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('supplier', Jam::association('belongsto', array(
			'inverse_of' => 'account'
		)));

		// ...
	}
}
?>
```

#### label

Define the human readable name of the field this is used in automatically generated error messages for example. By default uses `Inflector::humanize()` to generate this based on the name

#### model

This is defined automatically as the model setting the association, but in very rare cases can be overridden.

#### name

Change the name of the association - this name will be used in cache keys and such - you can use this to resolve naming conflicts.


### manytomany Association Reference

The `manytomany` association creates a many-to-many relationship with another model. In database terms, this associates two classes via an intermediate join table that includes foreign keys referring to each of the classes.

While Jam uses intelligent defaults that will work well in most situations, there may be times when you want to customize the behavior of the `manytomany` association reference. Such customizations can easily be accomplished by passing options when you create the association. For example, this association uses two such options:

```php
<?php
class Model_Supplier extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('parts', Jam::association('manytomany', array(
			'join_table' => 'assemblies',
			'foreign_key' => 'order_customer_uid'
		)));

		// ...
	}
}
?>
```

The `manytomany` association supports these options:

* join_table
* foreign_model
* foreign_key
* association_foreign_key
* label
* model
* name

#### join_table

The `join_table` option allows you to configure the join table for the association. By default its the names of the two models being associated, pluralized and alphabetically orderd. E.g. `$supplier->parts` will have a default join_table `suppliers_parts`

```php
<?php

class Model_Supplier extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('parts', Jam::association('manytomany', array(
			'join_table' => 'assembly'
		));

		// ...
	}
}
?>
```

#### foreign_model

The `foreign` option is used to configure the associated model.

```php
<?php
class Model_Customer extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->association('order', Jam::association('hasone', array(
			'foreign_model' => 'order'
		)));

		// ...
	}
}
?>

```

#### foreign_key

The `foreign_key` option is used to configure the field pointing to the the parent model from the association table. E.g. `$supplier->parts` will have a foreign_key of `supplier_id` by default on the `suppliers_parts` table

#### association_foreign_key

The `association_foreign_key` option is used to configure the field pointing to the the foreign_model from the association table. E.g. `$supplier->parts` will have a foreign_key of `part_id` by default on the `suppliers_parts` table

#### label

Define the human readable name of the field this is used in automatically generated error messages for example. By default uses `Inflector::humanize()` to generate this based on the name

#### model

This is defined automatically as the model setting the association, but in very rare cases can be overridden.

#### name

Change the name of the association - this name will be used in cache keys and such - you can use this to resolve naming conflicts.

