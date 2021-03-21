<?php
namespace App\Exceptions;

use Exception;

class GroupExistException extends Exception
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
                        "reason" => "GroupExistException",
                        "message" => "Group Has Been Added",
                    ],
                ],
            ],
        ], 400);
    }
}
