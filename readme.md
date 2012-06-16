# Artifact - The View abstraction for Laravel (with a goofy name ;)

Over at www.getlayla.com we are cooking a super-modern open-source "CMS",
While we aim to make a working CMS solution first, our next goal is to
shake that image off of us and become the next-generation application builder.

We love Laravel, and we love to take parts out of Layla and share them with the
community.

This is the first, in hopefully a series, of releases.


# Why

Over at layla, we are going to use Artifact to load Forms & Partial views from the database
And use it instead of our "normal views" as well to allow us to make custom themes more easy to implement.


# What

Artifact allows you to add a layer between "declaring" and "presenting" views.
It allows you to to define your own structures for for certain components


# How

Artifact allows you to call a series of methods on an Object. This Object will "catch" all your calls
and in the end pass them on to a Renderer.

The renderer will have a method that displays the actually view element.

When you load your Artifact, It will render all calls that were catched and return the output.


# Use

### Setting it up

To use Artifact in your own project. simply install it by running the following command on your terminal

`php artisan bundle:install artifact`

After that, move the config file to your application/config folder.

`mv bundles/artifact/artifact.php application/config`

Now, all we have left to do for installation is starting the bundle in application/bundles.php

Make add this line to the array

`'artifact' => array('auto' => true)`

### Now let's use it!

In order for Artifact to find our defenitions of the artifact, we will route to them.
This is pretty similar to how we route to our controllers with Routes.

We can register an Artifact with a certain name, and a certain type.
You are totally free in how you name your things, let me just give you an example

application/start.php
```php
//<?php

use Layla\Artifact;

// Let's wait for the Artifact bundle to have loaded, before registering our defenitions
Event::listen('laravel.started: artifact', function()
{
	Artifact::register('page', 'user.add', 'user.add@page');
	Artifact::register('form', 'user.edit', 'user.edit@form');
	Artifact::register('table', 'user.add', 'mybundle::user.add@table');
});
```

Here we specified 3 different "types". A page, form and a table.

Let's see what Artifact makes out of the registered artifacts

The "page" artifact is located in application/pages/user/add.php, has a classname of "User_Add_Page" and has a method called page

The "form" artifact is located in application/pages/user/edit.php, has a classname of "User_Edit_Page" and has a method called form

The "table" artifact is located in bundles/mybundle/tables/user/add.php, has a classname of "Mybundle_User_Add_Table" and has a method called table

As you can see the type will be pluralized and added to the file path. classnames are handled in a similar way to registering controllers.

To render our artifacts, we can simple call the Artifact class statically with the "type" as the method, and the "name" as the first argument. You can add as many arguments as you like, that will be passed on to the Artifact.

```php
//<?php

use Layla\Artifact;

Route::get('user/add', function()
{
	return Artifact::page('user.add');	
});

Route::get('user/edit/(:num)', function($id)
{
	$user = User::find($id);

	return Artifact::form('user.edit', $user);
});

Route::get('table', function()
{
	return Artifact::table('user.add');
});
```

And here is the "form" "user.edit" that extends from ShawnMcCool's form-base-model

```
<?php

use Layla\Form;

class User_Edit_Form extends Form {
	
	public static $rules = array(
		'email' => 'required|email'
	);

	public function form($artifact, $user)
	{
		$artifact->form(function($artifact) use ($user)
		{
			$artifact->input('name', ExampleForm::old('name', $user->name));
			$artifact->input('email', ExampleForm::old('email', $user->email));
		}, 'PUT', 'user/edit/'.$user->id);
	}

}
``` 

I will add some better examples soone


# Who

Koen Schmeets	- Artifact
Layla Team		- Motivation
ShawnMcCool		- Form Base Model
Phill Sparks	- Bootsparks