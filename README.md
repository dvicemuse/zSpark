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


** Full Example **

```
<?php
	class Dashboard_Controller extends zSpark_Controller{
		
		public function __construct(){
		
			//DISABLE THE HEADERS FOR THE PROFILE AND SETINGS VIEWS
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
