#zSpark
A lightweight MVC framework for PHP

## About

zSpark is a framework that has been under development for several years. It has altered and adopted features and syntax from various other projects that I have worked on along the way. The point of zSpark is to have a framework that is very quick to install with the base features that I (and most developers) use the most. The idea is to open 1 file on your server where you set the database options then start building your application. 

## Main Features

* 1 File install
* MVC structure
* Custom Database class
* Automatic URL routing
* Automatic ORM relationships
* Deploys with Bootstrap and jQuery

## Installation

1. Download the index.php file and place it in the server where you would like it to install. (i.e. /public_html/zSpark).
2. Run the file by visiting it in the browser.
3. Provide your database credentials - Or - Uncheck the box "use database" if you are planning on using zSpark without a database
4. Hit install and watch it redirect you to the home page of your new installation.
 
# Using zSpark

Once you have zSpark installed it is very easy to navigate. In the installation folder you will see a model, view, controller, plugin, and system folder.

These folders are named pretty obviously, however inside the system folder are the core zSpark files. Unless there is something custom you need to do with the framework, it is unlikely that you will be working in this folder.

## URLS

URLs automatically try to load controllers. Controllers are located in the /controller folder of the zSpark install directory.
If a controller is not found then zSpark will try to load a view that exists with that name.

The first part of a zSpark URL translates to a controller
The second part of a URL translates to a method AND/OR a view
Any parts after that are variables that can be passed to the controller or view

---

**So what does zSpark DO with URLS.** 

*If your visiting the website http://www.example.com/*

```
 - /controller/Home_Controller.php is loaded
 - /view/Home/index.php is loaded
```

*If the URL is http://www.example.com/Dashboard*

``` 
 - Try to load /controller/Dashboard_Controller.php
 - Try to load /view/Dashboard/index.php
 - If neither are found load /view/System/404.php
```

*If the URL is http://www.example.com/Dashboard/profile*

```
- Try to load /controller/Dashboard/Dashboard_Controller.php
- Try to find a method called "profile" in Dashboard_Controller.php and run it.
- Try to load /view/Dashboard/profile.php
- If /controller/Dashboard/Dashboard_Controller.php AND /view/Dashboard/profile.php where not found zSpark loads the 404 template.
```

## Controllers

Controller are where you place all of your logic for a view. I like to think of them as keeping all of the PHP out of the view template so that the front end programmers never have to see it, and I dont have to sift through a bunch of front end code to handle find backend logic.

#### Creating Controllers

Creating a controller is relatively easy. There are only a few things that need to be in place for a controller to work.

Controllers **HAVE** to:
- Be named correctly.
- Be in the right folder
- Extend the zSpark_Controller class

So lets say we want to create a controller that the url *http://www.example.com/Dashboard* will point to automatically.

In the /controllers folder you will need to create a **Dashboard_Controller.php**

The base code for a controller with this name would look like

```
<?php
	class Dashboard_Controller extends zSpark_Controller{
		
		public function __construct(){
			//do stuff on controller load here
		}
		
		public function index(){
			//do stuff on default view load
		}
	}
```

Technically you can actually create the controller without either of the methods (functions) in the class, however I believe both of those to be a good practice as I almost always use them.

## Views

Most of the time views will be loaded by resolving a URL to a controller, then that controller loads a specific view. The default view for any controller is index.php. Controllers will load the default unless a different view is specified in the URL.

So lets say we want a default view for **http://www.example.con/Dashboard**

We need to create a file named *index.php* in the folder **/view/Dashboard/** and thats it!

What will happen now is when the URL *http://www.example.con/Dashboard* is visited, zSpark will load the Dashboard controller, look for the Dashboard_Controller method (function) "index", then load the header, then the template **index.php** then load the footer.

#### Headers & Footers

Headers and footers are loaded automically. This way you don't have to code the header and footer onto every page. But where are they located?

Well there are two kinds of header and footer. There is the **primary** and the **view-specific**

**View-specific** headers take priority over primary headers and are located in the view's folder. 
(i.e /view/Dashboard/header.php and /view/Dashboard/footer.php)

**Primary** footers are only loaded if the **view-specific** footer could not be found.
These files are located in */view/header.php* & */view/footer.php*

---

#### Disable Headers And Footers

What if I dont want headers/footers to load at all?

Well you're in luck. For the example **http://www.example.com/Dashboard/profile** lets say we don't want header/footer for the view that will be loaded.

---

In the Dashboard_Controller.php file, add 
```
	$this->disable_headers('profile');
```

You can also disable headers for multiple views here

```
	$this->disable_headers(arrray('profile', 'settings'));
```
OR

```
	$this->disable_headers('profile');
	$this->disable_headers('settings');
```

**Full Example**

```
<?php
	class Dashboard_Controller extends zSpark_Controller{
		
		public function __construct(){
		
			$this->disable_headers(arrray('profile', 'settings'));
		}
		
		public function index(){
			//do stuff on default view load
		}
		
		public function profile(){
			//do profile stuff here
		}
		
		
		public function settings(){
			//do settings stuff here
		}
	}
```

## Models

Models are representations of a database table. They can contain 1 or more rows of a database table

#### Creating Models

To create a model it needs to be named properly, live in the models directory, and be named just like the database table.

If we have a table named **user** to create a model for it lets create a file in the **/model** folder named **User_Model.php**

The code should look like this.
```
<?php
	class User_Model extends zSpark_Model{
		
		public function __construct(){
		
		}
	}
```

Now we can call the model and retrieve its data by using

```
	$this->load_model('User')->orm_load();
```

The above command will load every record in the **user** mysql table.


#### But my table isnt named the same as my model

Take a breath. Its okay. In the model construct we can use
```
	$this->_table_name = 'people';
```

Now when we use **$this->load_model('User')->orm_load();** it will use the mysql table **people**

**Full Example**

```
<?php
	class User_Model extends zSpark_Model{
		
		public function __construct(){
			$this->_table_name = 'people';
		}
	}
```

## Working with the zSpark ORM

The zSpark orm helps to load parts of the database quickly and navigate to their relationships

#### zSpark ORM Methods

There are a few methods for the zSpark ORM

- orm_load
- orm_set
- orm_save
- orm_delete
- where
- order
- limit
- has_many
- has_one

#### orm_load

After after the model is loaded, orm_load tells zSpark to run the query. 

There are 2 ways to use orm_load. The first is to supply no command like:
```
	$this->load_model('User')->orm_load();
```

The second is to supply a mysql id like:

```
	$this->load_model('User')->orm_load(1);
```

The first command will load every single record in the mysql table that the model is mapped to, and the second will only load the record where the mysql id is what was passed.

#### orm_set

This method allows us to set data to the model object.

Lets say that we are trying to create a new user. We would do something like this.

```
$data = array(
	'user_first_name' 	=> 'John',
	'user_last_name		=> 'Doe',
	'user_email'		=> 'example@domain.com'
);

$user = $this->load_model('User')->orm_set($data)->orm_save();
```

The above PHP will load the User model, set the data for that model and save it to the database. Now the new database record is contained in the **$user** variable.

#### orm_save

orm_save can either create or update a model's record in the database. Weather it creates or updates is dependent on weather the models data has an id in associated.

If we wanted to update a model's record instead of creating a new one we could do something like this

```
$data = array(
	'user_id'		=> 1,
	'user_first_name' 	=> 'Jack',
);

$user = $this->load_model('User')->orm_set($data)->orm_save();
```
The above will update the **user_first_name** of the user record with a **user_id** of 1 to Jack

OR if we want to update data for a specific model record we can use:

```
//load the model
$user = $this->load_model('User')->orm_load(1);

//create data for update
$data = array('user_first_name' => 'Joe');

//set and save the data
$user->orm_set($data)->orm_save();

//or if you want it on one line just use
$user = $this->load_model('User')->orm_load(1)->orm_set($data)->orm_save();
```

#### orm_delete

Although I generally dont like to actually delete records from the database but usually set up a field named delete with a true/false, sometimes it is necessary. To delete a record, load the model, load the record, use orm_delete

```
	$this->load_model('User')->orm_load(1)->orm_delete();
```

#### ORM: Where

Often we will need to load many records with specific context from the database. For this we us **->where()**

Lets say I want all users whose user_create_date value is after July 1st of 2016. I would use something like:

```
$users = $this->load_model('User')->where("user_create_date > 2016-07-01")->orm_load();
```

#### ORM: Order 

Lets say that I want to order my results of an orm_load. By default zSpark loads everything by id ascending. We can overwrite that by using something like:

```
$users = $this->load_model('User')->order("user_create_date DESC")->orm_load();
```

#### ORM: LIMIT 

Lets say that I want limit my results of an orm_load. I would use something like

```
$users = $this->load_model('User')->limit(5)->orm_load();
```

#### ORM: Combine ORM Methods

You can combine and reuse any combination of ORM methods

```
$users = $this->load_model('User')->limit(5)->where("user_id > 200")->where("user_first_name = 'Jack' OR user_create_date > 2016-07-01")->order("user_create_date DESC")->orm_load();
```
### ORM Relationships

Unlike the other ORM methods the relationships are set in the model.

#### ORM: Has One

Has one refers to a model being a parent of one other model.

The has_one method take a few parameters. They are 
```
$this->has_one('model', 'local_field', 'remote_field', array('field_name' => 'field_value', 'field2_name' => 'field2_value'));
```


For example a User model may have one Profile model. To set this relatioship you would add this to the User_Model.php

```
$this->has_one('Profile');
```

However maybe the field name in the **user** table for profile is not the same as the profiles id. You can use something like this

```
$this->has_one('Profile', 'profile_id', 'id');
```

Sometimes we want to be able to specify adittional field to take into consideration. In this example lets map a profile that is active to a user

```
$this->has_one('Profile', 'profile_id', 'profile_id', array('profile_active' => 'true'));
```

Now we can call the profile from the user model by using something like this:

```
	$profile = $this->load_model('User')->orm_load(1)->profile();
```

**Full Example**

```
<?php
	class User_Model extends zSpark_Model{
		public function __construct(){
			$this->has_one('Profile', 'profile_id', 'id', array('profile_active' => 'true'));
		}
		
		public function get_profile(){
			$profile = $this->profile();
		}
	}
```

#### Has Many

**has_many** works EXACTLY like **has_one** but it load multiple objects. For example, a user may have multiple locations. You would use something like this

```
	//db mapping is identical
	$this->has_many('Location');
	
	//db mapping is not identical
	//user location id is 'location_id' and location tables id is 'id'
	$this->has_many('Location', 'location_id', 'id');
	
	//db mapping needs extra params
	$this->has_many('Location', 'location_id', 'location_id', array('city' => 'Sacramento', 'state' => 'CA'));
```

Once this relationship is set up it can be used like:

```
	//if this code is inside of the User_Model.php
	$this->location();
	
	//this code is in the view
	$user = $this->load_model('User')->orm_load(1);
	$locations = $user->location();
```


#### Passing Parameters to relationships

Sometimes we want to load a relationship, but only grab some of the relationship records and not all of them

Lets say that we want a users friend, but we only want them in a specific city.

The code would look like this
```
<?php
	class User_Model extends zSpark_Model{
	
		public function __construct(){
			$this->has_many('Friend');
		}
		
		public function get_sacramento_friends(){
			$friends = $this->location("friend_city = 'sacramento'");
		}
		
		public function get_friends_by_state($state){
			$friends = $this->location("friend_city = '{$state}'");
		}
		
	
	}
	
	
```
