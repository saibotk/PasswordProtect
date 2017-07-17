<?php

//----PasswordProtect Middleware Routes
//Had to add the web middleware in order to get session to work properly
//https://stackoverflow.com/questions/35640175/laravel-package-cannot-access-session

Route::get('/passwordprotect',
    'michaelmetz\passwordprotect\PasswordProtectController@getForm')->middleware('web');
Route::post('/passwordprotect',
    'michaelmetz\passwordprotect\PasswordProtectController@postForm')->middleware('web');
