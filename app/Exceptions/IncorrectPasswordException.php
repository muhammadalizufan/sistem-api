<?php
namespace App\Exceptions;

use Exception;

class IncorrectPasswordException extends Exception
{
    public function render()
    {
        return response([
            'api_version' => "1.0",
            "error" => [
                'code' => 401,
                "message" => "Unauthorized",
                "reason" => "UnauthorizedException",
                "errors" => [
                    [
                        "domain" => null,
                        "reason" => "IncorrectPasswordException",
                        "message" => "Incorrect User Password",
                    ],
                ],
            ],
        ], 401);
    }
}
