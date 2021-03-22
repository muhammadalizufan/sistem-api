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
        $router->group(['namespace' => 'File', "prefix" => "file"], function () use ($router) {
            $router->post('upload', 'UploadController@Handler');
        });

        $router->group(["namespace" => "Auth"], function () use ($router) {
            $router->get("permissions", "PermissionController@GetAllPermissionRawHandler");
            $router->get("list-permissions", "PermissionController@GetAllPermissionHandler");
            $router->get("update-permissions", "PermissionController@UpdatePermissionHandler");

            $router->group(["prefix" => "auth"], function () use ($router) {
                $router->post("login", "LoginController@Handler");
                $router->post("refresh-token", "LoginController@RefreshTokenHandler");
                $router->post("change-password", [
                    "middleware" => ["auth.private"],
                    "uses" => "UserController@ChangeUserPasswordHandler",
                ]);
            });

            // Start-Group
            $router->get("groups", ["middleware" => ["auth.private"], "uses" => "GroupController@GetGroupHandler"]);

            $router->group(["prefix" => "group", "middleware" => ["auth.private"]], function () use ($router) {
                $router->get("/{id:[0-9]+}", "GroupController@GetGroupHandler");
                $router->post("add", "GroupController@AddGroupHandler");
                $router->post("update/{id:[0-9]+}", "GroupController@EditGroupHandler");
            });
            // End-Group

            // Start-Role
            $router->get("roles", ["middleware" => ["auth.private"], "uses" => "RoleController@GetRoleHandler"]);

            $router->group(["prefix" => "role", "middleware" => ["auth.private"]], function () use ($router) {
                $router->get("/{id:[0-9]+}", "RoleController@GetRoleHandler");
                $router->post("add", "RoleController@AddRoleHandler");
                $router->post("update/{id:[0-9]+}", "RoleController@EditRoleHandler");
            });
            // End-Role

            // Start-User
            $router->get("users", ["middleware" => ["auth.private"], "uses" => "UserController@GetUserHandler"]);

            $router->group(["prefix" => "user", "middleware" => ["auth.private"]], function () use ($router) {
                $router->get("/{id:[0-9]+}", "UserController@GetUserHandler");
                $router->post("add", "UserController@AddUserHandler");
                $router->post("update/{id:[0-9]+}", "UserController@EditUserHandler");
            });
            // End-User
        });

        $router->group(["namespace" => "SIAP", "prefix" => "siap", "middleware" => ["auth.private"]], function () use ($router) {

            $router->get("inboxs", "DispositionController@GetInboxHandler");
            $router->get("inbox/{id:[0-9]+}", "DispositionController@GetInboxHandler");

            $router->get("dispositions", "DispositionController@GetLetterHandler");
            $router->group(["prefix" => "disposition"], function () use ($router) {
                $router->get("/{id:[0-9]+}", "DispositionController@GetLetterHandler");
                $router->post("add", "DispositionController@AddNewLetterHandler");
                $router->post("update/{id:[0-9]+}", "DispositionController@EditLetterHandler");
                $router->post("comment/{id:[0-9]+}", "DispositionController@CommentLetterHandler");
                $router->post("send/{id:[0-9]+}", "DispositionController@SendLetterHandler");
            });
        });
    });
});
