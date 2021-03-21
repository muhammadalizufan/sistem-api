<?php
namespace App\Exceptions;

use Exception;

class RoleExistException extends Exception
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
                        "reason" => "RoleExistException",
                        "message" => "Role Has Been Added",
                    ],
                ],
            ],
        ], 400);
    }
}
