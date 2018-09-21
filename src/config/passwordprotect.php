<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Greg Gilberts Recaptcha Package
    |--------------------------------------------------------------------------
    |
    | Indicate if you want to use Recaptcha
    |
    | Make sure you have it installed and configured
    | https://github.com/greggilbert/recaptcha

    */
    'use_greggilbert_recaptcha'    => false,

	/*
	|--------------------------------------------------------------------------
	| SecurImage Captcha
	|--------------------------------------------------------------------------
	|
	|	Activate SecurImage Captcha instead of recaptcha.
	|	Make sure to configure a input field in the form to send a 'captcha_code'
	|	and install SecurImage version 3.x because this does not work with 4.x or
	|	newer.
	|	See https://stackoverflow.com/questions/28928767/implementing-php-captcha-into-laravel-framework
	|
	*/
	'use_securimage_captcha'	=> false,

    /*
    |--------------------------------------------------------------------------
    | Session Key Names
    |--------------------------------------------------------------------------
    |
    | Password Protect uses the session to stores routes the user navigates to
    |
    | Below are the default session key names that are used
    | leave them be unless you already uses theses key names elsewhere
    |
    */
    'desired_route_key_name'     => 'pp_desiredroute',
    'root_route_key_name'    => 'pp_rootroute',
];
