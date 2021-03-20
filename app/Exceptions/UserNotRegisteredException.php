<?php
namespace App\Exceptions;

use Exception;

class UserNotRegisteredException extends Exception
{
    public function render()
    {
        return response([
            'api_version' => '1.0',
            "error" => [
                'code' => 401,
                "message" => "Unauthorized",
                "reason" => "UnauthorizedException",
                "errors" => [
                    [
                        "domain" => null,
                        "reason" => "UserNotRegisteredException",
                        "message" => "User Not Registered",
                    ],
                ],
            ],
        ], 401);
    }
}
