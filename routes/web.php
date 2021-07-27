<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

$request = app()->make('request');
$currentHost = $request->header('host');
$defaultUri = new Uri(config('app.url'));

Route::get('/', 'Index@index');


//Auth::routes();

//Login Routes
Route::get('login', ['as' => 'login', 'uses' => 'Auth\LoginController@showLoginForm']);
Route::post('login', ['as' => '', 'uses' => 'Auth\LoginController@login']);
Route::post('logout', ['as' => 'logout', 'uses' => 'Auth\LoginController@logout']);
  
// Password Reset Routes...
Route::post('password/email', ['as' => 'password.email', 'uses' => 'Auth\ForgotPasswordController@sendResetLinkEmail']);
Route::get('password/reset', ['as' => 'password.request', 'uses' => 'Auth\ForgotPasswordController@showLinkRequestForm']);
Route::post('password/reset', ['as' => 'password.update', 'uses' => 'Auth\ResetPasswordController@reset']);
Route::get('password/reset/{token}', ['as' => 'password.reset', 'uses' => 'Auth\ResetPasswordController@showResetForm']);

// Registration Routes...
Route::get('register', ['as' => 'register', 'uses' => 'Auth\RegisterController@showRegistrationForm']);
Route::post('register', ['as' => '', 'uses' => 'Auth\RegisterController@register']);



Route::get('/logout', 'Auth\LoginController@logout');
Route::get('/forgot-password', 'Auth\ForgotPasswordController@showLinkRequestForm');
Route::post('/forgot-password', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('forgot-password');
Route::get('/reset-password/{token}', 'Auth\ResetPasswordController@showResetForm');
Route::post('/reset-password/{token}', 'Auth\ResetPasswordController@reset')->name('forgot-password-reset');
//Route::get('/home', 'HomeController@index')->name('home');
Route::get('/verify-email/{id}', 'Auth\Verify@verifyEmail')->name('verify-email');

Route::get('/sso', 'Auth\Partners\SingleSignOn@sso')->middleware('guest');
Route::get('/sso-silent', 'Auth\Partners\SingleSignOn@ssoSilent')->middleware('guest');

//Route::get('/home', 'HomeController@index')->name('home');
Route::post('/home', 'HomeController@post');
Route::get('/invites/{id}', 'Invites@index')->name('invite');
Route::post('/invites/{id}', 'Invites@post');
Route::get('/register/professionals', 'Auth\RegisterController@showProfessionalRegistrationForm')->name('professional.register');
Route::get('/register/vendors', 'Auth\RegisterController@showVendorRegistrationForm')->name('vendor.register');
Route::get('/professionals', 'Auth\RegisterController@showOldProfessionalRegistrationForm');



/**
 * Route Group for XHR: /xhr/...
 */
Route::group(['middleware' => ['auth'], 'namespace' => 'Ajax', 'prefix' => 'xhr'], function () {

    
    Route::group(['middleware' => ['require_role:partner'], 'prefix' => 'vpanel', 'namespace' => 'vPanel'], function () {
        Route::get('/companies', 'Businesses@search')->name('xhr.vpanel.companies');
        Route::delete('/companies/{id}', 'Businesses@delete');
        Route::get('/invites', 'Businesses@searchInvites')->name('xhr.vpanel.invites');
        Route::delete('/invites/{id}', 'Businesses@deleteInvite');
        Route::get('/users', 'Users@search')->name('xhr.vpanel.users');
        Route::delete('/users/{id}', 'Users@delete');
    });
});

Route::group(['middleware' => ['auth']], function () {
    Route::get('/plans', 'UpgradePlan@index')->name('plans');
    Route::get('/subscription', 'Subscription@index')->name('subscription');
    Route::post('/subscription', 'Subscription@post');
});



Route::group(['namespace' => 'vPanel', 'prefix' => 'vpanel', 'middleware' => ['auth', 'require_role:partner']], function () {
    Route::get('/', 'Businesses\Businesses@index')->name('vpanel.dashboard');
    Route::get('/businesses', 'Businesses\Businesses@index')->name('vpanel.businesses');
    Route::post('/businesses', 'Invites@post');
    Route::get('/businesses/{id}', 'Businesses\Business@index')->name('vpanel.businesses.profile');
    Route::post('/businesses/{id}', 'Businesses\Business@post');
    Route::get('/invites', 'Invites@index')->name('vpanel.invites');
    Route::post('/invites', 'Invites@post');
    Route::get('/customisation', 'Customisation@index')->name('vpanel.customise');
    Route::post('/customisation', 'Customisation@post');
    Route::get('/users', 'Users\Users@index')->name('vpanel.users');
    Route::get('/managers', 'Users\Users@managers')->name('vpanel.users.managers');
    Route::post('/managers', 'Users\Users@post');
    Route::get('/settings', 'Settings@index')->name('vpanel.settings');
    Route::post('/settings', 'Settings@post');
});
