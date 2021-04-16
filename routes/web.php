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

        // Test Only
        $router->group(["namespace" => "Test"], function () use ($router) {
            $router->get("add-user-dummy", "TestController@AddTestDummy");
        });
        // Test Only

        $router->group(["namespace" => "Auth"], function () use ($router) {
            $router->get("permissions", "PermissionController@GetAllPermissionRawHandler");
            $router->get("list-permissions", "PermissionController@GetAllPermissionHandler");

            // Test Only
            $router->get("update-permissions", "PermissionController@UpdatePermissionHandler");
            // Test Only

            $router->group(["prefix" => "auth"], function () use ($router) {
                $router->post("login", "LoginController@Handler");
                $router->post("refresh-token", "LoginController@RefreshTokenHandler");
                $router->post("change-password", [
                    "permission" => "Setting.UserProfile.ChangePassword",
                    "middleware" => [
                        "auth.private",
                    ],
                    "uses" => "UserController@ChangeUserPasswordHandler",
                ]);
            });

            // Start-Group
            $router->get("groups", [
                "permission" => "Setting.GroupManagement.ViewSearch",
                "middleware" => [
                    "auth.private",
                ],
                "uses" => "GroupController@GetGroupHandler",
            ]);

            $router->group(["prefix" => "group", "middleware" => ["auth.private"]], function () use ($router) {
                $router->get("/{id:[0-9]+}", [
                    "permission" => "Setting.GroupManagement.ViewDetail",
                    "uses" => "GroupController@GetGroupHandler",
                ]);
                $router->post("add", [
                    "permission" => "Setting.GroupManagement.Add",
                    "uses" => "GroupController@AddGroupHandler",
                ]);
                $router->post("update/{id:[0-9]+}", [
                    "permission" => "Setting.GroupManagement.Edit",
                    "uses" => "GroupController@EditGroupHandler",
                ]);
            });
            // End-Group

            // Start-Role
            $router->get("roles", [
                "permission" => "Setting.UserManagement.Role.ViewSearch",
                "middleware" => [
                    "auth.private",
                ],
                "uses" => "RoleController@GetRoleHandler",
            ]);

            $router->group(["prefix" => "role", "middleware" => ["auth.private"]], function () use ($router) {
                $router->get("/{id:[0-9]+}", [
                    "permission" => "Setting.UserManagement.Role.ViewDetail",
                    "uses" => "RoleController@GetRoleHandler",
                ]);
                $router->post("add", [
                    "permission" => "Setting.UserManagement.Role.Add",
                    "uses" => "RoleController@AddRoleHandler",
                ]);
                $router->post("update/{id:[0-9]+}", [
                    "permission" => "Setting.UserManagement.Role.Edit",
                    "uses" => "RoleController@EditRoleHandler",
                ]);
            });
            // End-Role

            // Start-User
            $router->get("users", [
                "permission" => "Setting.UserManagement.Member.ViewSearch",
                "middleware" => [
                    "auth.private",
                ],
                "uses" => "UserController@GetUserHandler",
            ]);

            $router->group(["prefix" => "user", "middleware" => ["auth.private"]], function () use ($router) {
                $router->get("/{id:[0-9]+}", [
                    "permission" => "Setting.UserManagement.Member.ViewDetail",
                    "uses" => "UserController@GetUserHandler",
                ]);
                $router->post("add", [
                    "permission" => "Setting.UserManagement.Member.Add",
                    "uses" => "UserController@AddUserHandler",
                ]);
                $router->post("update/{id:[0-9]+}", [
                    "permission" => "Setting.UserManagement.Member.Edit",
                    "uses" => "UserController@EditUserHandler",
                ]);
            });
            // End-User
        });

        $router->group(["namespace" => "SIAP", "prefix" => "siap", "middleware" => ["auth.private"]], function () use ($router) {
            $router->group(["prefix" => "outgoingletter"], function () use ($router) {
                $router->get("activities", [
                    "permission" => "SIAP.OutgoingLetter.ViewDetail",
                    "uses" => "OutgoingLetterController@GetOutgoingLetterActivityHandler",
                ]);
                $router->get("activity/{id:[0-9]+}", [
                    "permission" => "SIAP.OutgoingLetter.ViewDetail",
                    "uses" => "OutgoingLetterController@GetOutgoingLetterActivityHandler",
                ]);
                $router->get("inboxs", [
                    "permission" => "SIAP.OutgoingLetter.ViewSearch",
                    "uses" => "OutgoingLetterController@GetOutgoingLetterHandler",
                ]);
                $router->get("inbox/{id:[0-9]+}", [
                    "permission" => "SIAP.OutgoingLetter.ViewDetail",
                    "uses" => "OutgoingLetterController@GetOutgoingLetterHandler",
                ]);
                $router->post("add", [
                    "permission" => "SIAP.OutgoingLetter.Add",
                    "uses" => "OutgoingLetterController@AddNewLetterHandler",
                ]);
                $router->post("update/{id:[0-9]+}", [
                    "permission" => "SIAP.OutgoingLetter.Edit",
                    "uses" => "OutgoingLetterController@EditLetterHandler",
                ]);
            });

            $router->group(["prefix" => "disposition"], function () use ($router) {
                $router->get("inboxs", [
                    "permission" => "SIAP.Disposition.ViewSearch",
                    "uses" => "DispositionController@GetInboxHandler",
                ]);
                $router->get("inbox/{id:[0-9]+}", [
                    "permission" => "SIAP.Disposition.ViewDetail",
                    "uses" => "DispositionController@GetInboxHandler",
                ]);
                $router->get("responders", [
                    "permission" => "SIAP.Disposition.ViewDetail",
                    "uses" => "DispositionController@GetDisposistionResponders",
                ]);

                $router->post("add", [
                    "permission" => "SIAP.Disposition.Add",
                    "uses" => "DispositionController@AddNewLetterHandler",
                ]);
                $router->post("update/{id:[0-9]+}", [
                    "permission" => "SIAP.Disposition.Edit",
                    "uses" => "DispositionController@EditLetterHandler",
                ]);
                $router->post("add-responders", [
                    "permission" => [
                        "SIAP.Disposition.ViewSearch",
                        "SIAP.Disposition.ViewDetail",
                    ],
                    "uses" => "DispositionController@AddResponderLetterHandler",
                ]);
                $router->post("comment", [
                    // "permission" => "SIAP.Disposition.Comment",
                    "uses" => "DispositionController@CommentLetterHandler",
                ]);
                $router->post("send", [
                    "permission" => "SIAP.Disposition.Send",
                    "uses" => "DispositionController@SendLetterHandler",
                ]);
            });

            $router->group(["prefix" => "requestdata"], function () use ($router) {
                $router->get("inboxs", [
                    "permission" => "SIAP.RequestData.ViewSearch",
                    "uses" => "RequestDataController@GetRequestDataInbox",
                ]);
                $router->get("inbox/{id:[0-9]+}", [
                    "permission" => "SIAP.RequestData.ViewDetail",
                    "uses" => "RequestDataController@GetRequestDataInbox",
                ]);
                $router->get("responders", [
                    "permission" => "SIAP.RequestData.Administrator",
                    "uses" => "RequestDataController@GetRequestDataResponders",
                ]);

                $router->post("add", [
                    "permission" => "SIAP.RequestData.Requester",
                    "uses" => "RequestDataController@AddRequestDataHandler",
                ]);
                $router->post("comment/{id:[0-9]+}", [
                    "permission" => "SIAP.RequestData.Responders",
                    "uses" => "RequestDataController@CommentRequestDataHandler",
                ]);
                $router->post("add-confirmer/{id:[0-9]+}", [
                    "permission" => "SIAP.RequestData.Administrator",
                    "uses" => "RequestDataController@AddConfirmerRequestDataHandler",
                ]);
                $router->post("confirmation/{id:[0-9]+}", [
                    "permission" => "SIAP.RequestData.Administrator",
                    "uses" => "RequestDataController@ConfirmationRequestDataHandler",
                ]);
            });
        });
    });
});
