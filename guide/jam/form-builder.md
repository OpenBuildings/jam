## Binding a Form to an Object

A particularly common task for a form is editing or creating a model object. While the Kohana Form helpers can certainly be used for this task they are somewhat verbose as for each tag you would have to ensure the correct parameter name is used and set the default value of the input appropriately. Jam provides helpers tailored to this task.

Here's how the typical form code looks like


```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Controller_Articles extends Controller {

	public function action_new()
	{
		$article = Jam::build('article');

		$view = View::factory('article/form');


		if ($this->request->method() === Request::POST)
		{
			if ($article->set($this->request->post()->check())
			{
				$article->save();
				$view->set('message', 'Successfully saved');
			}
		}

		$this->request->body($view->set('article', $article));
	}
}
?>
```

The corresponding view views/articles/form.php using Jam_Form looks like this:

```php
<?php if (isset($message)): ?>
	<div><?php echo $message ?></div>
<?php endif ?>
<?php $form = Jam::form($article); ?>
<?php echo Form::open('/articles/new') ?>
	<?php echo $form->input('title') ?>
	<?php echo $form->textarea('body', array(), array('width' => 60, 'height' => 12)) ?>
	<?php echo Form::submit('Create') ?>
<?php echo Form::close(); ?>
```

Here's how all of this work:

* First off, we create an empty Jam_Model object with `Jam::build('article')`.
* At first pass, when te request is only GET, we show the form populated by $article (which is empty)
* We enter a bunch of stuff and press "Create"
* In the controller we see that it's a POST request, and proceed by getting the data from the post and setting it to the model
* If the validation passes, we display the message "successfully saved"
* Otherwise, we display the form again, with its previous input, allowing the user to correct it.


## Nesting Forms

Sometimes we have associations that we want to set alongside the object in one single form. For example if a Article has a belongsto 'category' and we can create a form to update them both:

```php
<?php $form = Jam::form($article); ?>
<?php echo Form::open('/articles/new') ?>
	<?php echo $form->input('title') ?>
	<?php echo $form->textarea('body', array(), array('width' => 60, 'height' => 12)) ?>
	<fieldset>
		<legend>Category</legend>
		<?php $category_form = $form->fields_for('category') ?>
		<?php echo $category_form->input('name') ?>
		<?php echo $category_form->checkbox('available') ?>
	</fieldset>
	<?php echo Form::submit('Create') ?>
<?php echo Form::close(); ?>
```


We can now keep the same controller code - it will save both the article and the new category, associated with it. This is quite powerfull and can express any type of nesting that you might require in your forms.

Fields_for has a second parameter "index" this is used to allow you to manage collections. For example if we needed a form to edit an article, by changing the info for its "hasmany" tags - we can do:

```php
<?php $form = Jam::form($article); ?>
<?php echo Form::open('/articles/edit/'.$article->id()) ?>
	<?php echo $form->input('title') ?>
	<?php echo $form->textarea('body', array(), array('width' => 60, 'height' => 12)) ?>
	<fieldset>
		<legend>Tags</legend>
		<ul>
			<?php foreach ($article->tags as $i => $tag): ?>
				<li>
					<?php $tag_form = $form->fields_for('tags', $i) ?>
					<?php echo $tag_form->hidden('id') ?>
					<?php echo $tag_form->input('name') ?>
				</li>
			<?php endforeach ?>
		</ul>
	</fieldset>
	<?php echo Form::submit('Update') ?>
<?php echo Form::close(); ?>
```

By adding an 'id' hidden field we insure that the tags get updated in place in place instead of created.

## Form helpers.

The available built in helpers (found in Jam_Form_General) are:

* __input__ normal text input tag
* __hidden__ input hidden tag
* __hidden_list__ list of hidden inputs - used to include a Jam_Collection
* __checkbox__ checkbox input, has the options "value" to be set when the input is checked (default is 1). Additionally it adds a second hidden input with value 0 so that if the checkbox is not set, the value recieved will be "0", as opposed to "no value at all"
* __radio__ input radio. Add several of this with the same name and different option "value" to allow the user to select between them
* __checkboxes__ - a list of checkbox input tags. Values and labels are set through "choices" option (value => label)
* __radios__ - a list of radio input tags. Values and labels are set through "choices" option (value => label)
* __file__ - input file tag
* __password__ input password tag
* __textarea__ textarea tag
* __select__ select tag, Options are set through the option "choices" (value => label). If its a nested array the result will be optiongroup tags.

## Form rows.

These inputs are probably rather simplistinc. When we start to add labels and error handling for each field, things can get verbose

```php
<?php $form = Jam::form($article); ?>
<?php echo Form::open('/articles/edit/'.$article->id()) ?>
	<div class="row">
		<?php echo $form->label('title') ?>
		<?php echo $form->input('title') ?>
		<?php echo $form->errors('title') ?>
	</div>
	<div class="row">
		<?php echo $form->label('title') ?>
		<?php echo $form->textarea('body', array(), array('width' => 60, 'height' => 12) ?>
		<?php echo $form->errors('body') ?>
	</div>
	<?php echo Form::submit('Create') ?>
<?php echo Form::close(); ?>
```

We can simplify this with the "->row" method. Based on a template it will create div tag with the input, label and error handling all in one call. Along with "help" section and appropriate css classes.

```php
<?php $form = Jam::form($article); ?>
<?php echo Form::open('/articles/edit/'.$article->id()) ?>
	<?php echo $form->row('input', 'title') ?>
	<?php echo $form->row('textarea', 'title', array(), array('width' => 60, 'height' => 12) ?>
	<?php echo Form::submit('Create') ?>
<?php echo Form::close(); ?>
```

## Extending Jam_Form

Often you will need to write custom form helpers which you can do easily by extending the "Jam_Form_General" class. Afterward when you call Jam::form($help, 'newform') to get your form class. You can further simplify this by setting the 'default_form' config setting in the jam/config.php file.





