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

**Lets see some examples.** 

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


