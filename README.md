PasswordProtect
====
Password Protect is a Laravel 5 package that allows routes to be protected with out using Auth or setting up a database.

Installation
---
#### 1. In the root directory of your laravel project, run the following in the command line
```
$ composer require michaelmetz/passwordprotect
```

##### 1b. (Only Laravel version < 5.5): Once that finishes installing register the Password Protect service provider by adding the following line to
><b>config/app.php</b>
```php
/*
 * Package Service Providers...
 */
Michaelmetz\Passwordprotect\PasswordProtectServiceProvider::class,
```
#### 2. In order to use the passwordprotect middleware it must be added to
><b>app/Http/Kernal.php</b>
```php
protected $routeMiddleware = [
    /*Default Middleware*/
    //Package MiddleWare
    'passwordprotect' => \Michaelmetz\Passwordprotect\Middleware\PasswordProtect::class,
];
```
#### 3. Finally run the following command
```
$ php artisan vendor:publish --provider="Michaelmetz\Passwordprotect\PasswordProtectServiceProvider"
```
This will publish 2 files
* passwordprotect.php to config/
* passwordprotect.blade.php to views/vendor/passswordprotect

Basic Usage
----
#### Routes
To protect a route simply attach the password protect middleware
><b>routes/web.php </b>
```php
 Route::get('jacks', 'controller@show')->middleware('passwordprotect:1');
```
#### Passwords
Passwords are stored in the `.env` file and are name like so `'PP_[rootRoutePath]=[password]'`

To use a password with the route then
><b>.env</b>
```
PP_JACKS=IDontSleep
```
Now if a user trys to navigate to `'/jacks'`  then they will be redirected to `'/passwordprotect'` and the default password form will be shown

<p align="center"><img src ="https://i.imgur.com/D59Ilso.jpg"/></p>

Once the user enters the correct password they will be able to access the route for as long as the session exists

Advanced Usage
----
#### Route Depth
The Password Protect middleware expects one integer as a parameter. This parameter indicates how far down the route path the password will be.
<p align="center"><img src ="https://media.giphy.com/media/xUA7aMrZRjAjHbAYla/giphy.gif" height="70"/></p>


For example consider the following
><b>routes/web.php </b>
```php
 Route::get('jacks', 'cont@show')                     ->middleware('passwordprotect:1');
 Route::get('jacks/super', 'cont@show1')              ->middleware('passwordprotect:1');
 Route::get('jacks/super/secret', 'cont@show2')       ->middleware('passwordprotect:3');
                                                                                 /* ^Note depth of 3*/
 Route::get('jacks/super/secret/garage', 'cont@show3')->middleware('passwordprotect:1');
```
><b>.env</b>
```
PP_JACKS=IDontSleep
PP_JACKS/SUPER/SECRET=CandyYummyYummy
```
Lets say a user navigates to ``'jacks/super'`` and supplys the valid password ``'IDontSleep'``

Then the user will have access to ``'jack'`` , ``'jacks/super'`` , ``'jacks/super/secret/garage'``  
Since they all pass a depth of 1 to the password protect middleware, they all look for the `'PP_JACKS'` password.

Since `'jacks/super/secrect'` passes a depth of ``'3'`` it will look for the ``'PP_JACKS/SUPER/SECRET'`` password and still be locked.

Configuration
----

#### Recaptcha
To deter brute force password attempts, Password Protect integrates well with [Greg Gilberts recaptcha package](https://github.com/greggilbert/recaptcha)

If you have recaptcha installed and configured, then change the following config key to 'true'
><b>congfig/passwordprotect.php</b>
```php
'use_greggilbert_recaptcha'    => true,
```
Once this is set recaptcha should appear on the default view.

#### Customize The Password Page

The Password input page view is published to

>views/vendor/passswordprotect/passwordprotect.blade.php

 Feel free to customize that view and make it your own!
