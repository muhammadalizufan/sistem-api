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
    return $router->app->version();
});

$router->group(["prefix" => "api"], function () use ($router) {
    $router->group(["prefix" => "v1"], function () use ($router) {

        $router->group(["namespace" => "Auth", "prefix" => "auth"], function () use ($router) {
            $router->post("login", "LoginController@Handler");
        });

        $router->group(["namespace" => "Auth"], function () use ($router) {
            $router->get("permissions", "PermissionController@GetAllPermissionHandler");
            $router->get("groups", "GroupController@GetHandler");
            $router->get("roles", "RoleController@GetRoleHandler");
        });

        $router->group(["namespace" => "Auth", "prefix" => "group"], function () use ($router) {
            $router->post("add", "GroupController@AddHandler");
            $router->post("update/{id:[0-9]+}", "GroupController@EditHandler");
        });

        $router->group(["namespace" => "Auth", "prefix" => "role"], function () use ($router) {
            $router->post("add", "RoleController@AddRoleHandler");
            $router->post("update/{id:[0-9]+}", "RoleController@EditRoleHandler");
        });

        $router->group(["namespace" => "Auth", "prefix" => "user"], function () use ($router) {
            $router->post("add", "UserController@AddUserHandler");
        });

        $router->group(["prefix" => "siap"], function () use ($router) {
            $router->get("dispositions", "DispositionController@GetLetterHandler");
            $router->group(["namespace" => "SIAP", "prefix" => "disposition"], function () use ($router) {
                $router->post("add", "DispositionController@AddNewLetterHandler");
                $router->post("add", "DispositionController@AddNewLetterHandler");
            });
        });
    });
});
