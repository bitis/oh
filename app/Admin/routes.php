<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('home');
    $router->resource('xhs-notes', Xhs\NoteController::class);
    $router->resource('xhs-images', Xhs\ImageController::class);
    $router->resource('xhs-videos', Xhs\VideoController::class);
    $router->resource('xhs-comments', Xhs\CommentController::class);
});
