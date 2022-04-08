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
    $router->resource('xhs-notes', App\Admin\Controllers\Xhs\NoteController::class);
    $router->resource('xhs-images', App\Admin\Controllers\Xhs\ImageController::class);
    $router->resource('xhs-videos', App\Admin\Controllers\Xhs\VideoController::class);
    $router->resource('xhs-comments', App\Admin\Controllers\Xhs\CommentController::class);
});
