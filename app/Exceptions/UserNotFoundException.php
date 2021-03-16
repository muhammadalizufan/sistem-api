<?php
namespace App\Exceptions;

use Exception;

class UserNotFoundException extends Exception
{
    public function render()
    {
        return response([
            'api_version' => '1.0',
            "error" => [
                'code'=> 401,
                "message" => "Unauthorized",
                "reason" => "UnauthorizedException",
                "errors" => [
                    [
                        "domain" => null,
                        "reason" => "UserNotFoundException",
                        "message" => "User Not Found"
                    ]
                ]
            ]
        ], 401);
    }
}
