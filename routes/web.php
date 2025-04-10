<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return response()->json([
        'message' => 'Welcome to the Lumen API',
        'version' => $router->app->version(),
    ]);
});

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->get('users', 'UserController@index');
    $router->get('user/{id}', 'UserController@show');
    $router->post('users', 'UserController@store');
    $router->patch('user/{id}', 'UserController@update');
    $router->delete('user/{id}', 'UserController@destroy');
});