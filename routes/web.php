<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

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

    $redis = Redis::connection();

    // Set and Get string
    $redis->set('greeting', 'Hello Redis!');
    $greeting = $redis->get('greeting');

    // Hash operations
    $redis->hset('user:1', 'name', 'Anichur');
    $redis->hset('user:1', 'role', 'Engineer');
    $user = $redis->hgetall('user:1');

    // List operations
    $redis->del('fruits'); // Reset list
    $redis->rpush('fruits', 'apple', 'banana', 'orange');
    $fruits = $redis->lrange('fruits', 0, -1);

    // Set operations
    $redis->del('colors'); // Reset set
    $redis->sadd('colors', 'red', 'blue', 'green');
    $colors = $redis->smembers('colors');

    // Sorted Set (ZSet)
    $redis->del('leaders'); // Reset zset
    $redis->zadd('leaders', 100, 'Alice');
    $redis->zadd('leaders', 200, 'Bob');
    $redis->zadd('leaders', 10, 'John Doe');
    $redis->zadd('leaders', 50, 'John');
    $leaders = $redis->zrange('leaders', 0, -1, ['withscores' => true]);

    // TTL and Expire
    $redis->setex('temp_key', 60, 'This will expire in 60 seconds');
    $ttl = $redis->ttl('temp_key');

    return response()->json([
        'message' => 'Welcome to the Lumen API',
        'version' => $router->app->version(),
        'redis' => [
            'greeting' => $greeting,
            'user' => $user,
            'fruits' => $fruits,
            'colors' => $colors,
            'leaders' => $leaders,
            'temp_key_ttl' => $ttl,
        ],
        'info' => $redis->info(),

    ]);
});

$router->get('/pipeline-test', function () {
    $results = Redis::pipeline(function ($pipe) {
        // String
        $pipe->set('pipeline:string', 'Hello from pipeline!');
        $pipe->get('pipeline:string');

        // Hash
        $pipe->hmset('pipeline:hash', [
            'name' => 'Anichur',
            'position' => 'Full-Stack Engineer'
        ]);
        $pipe->hgetall('pipeline:hash');

        // List
        $pipe->del('pipeline:list');
        $pipe->rpush('pipeline:list', 'Vue', 'React', 'Laravel');
        $pipe->lrange('pipeline:list', 0, -1);

        // Set
        $pipe->del('pipeline:set');
        $pipe->sadd('pipeline:set', 'PHP', 'Node.js', 'Python');
        $pipe->smembers('pipeline:set');

        // Sorted Set
        $pipe->del('pipeline:zset');
        $pipe->zadd('pipeline:zset', 90, 'Alice');
        $pipe->zadd('pipeline:zset', 100, 'Bob');
        $pipe->zrange('pipeline:zset', 0, -1, ['withscores' => true]);

        // Expiry
        $pipe->setex('pipeline:temp', 30, 'Will expire in 30 seconds');
        $pipe->ttl('pipeline:temp');
    });

    // Now format the result array
    return response()->json([
        'pipeline_all_results' => $results,
        'pipeline_results' => [
            'string_value' => $results[1],
            'hash_data' => $results[3],
            'list_data' => $results[6],
            'set_data' => $results[8],
            'zset_data' => $results[11],
            'temp_ttl' => $results[13],
        ]
    ]);
});



$router->group(['prefix' => 'api'], function () use ($router) {
    $router->get('users', 'UserController@index');
    $router->get('user/{id}', 'UserController@show');
    $router->post('users', 'UserController@store');
    $router->patch('user/{id}', 'UserController@update');
    $router->delete('user/{id}', 'UserController@destroy');
});