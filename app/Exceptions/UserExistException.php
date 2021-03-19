<?php
namespace App\Exceptions;

use Exception;

class UserExistException extends Exception
{
    public function render()
    {
        return response([
            'api_version' => '1.0',
            "error" => [
                'code' => 400,
                "message" => "BadRequest",
                "reason" => "BadRequestException",
                "errors" => [
                    [
                        "domain" => null,
                        "reason" => "UserExistException",
                        "message" => "User Email Already Registered",
                    ],
                ],
            ],
        ], 400);
    }
}
