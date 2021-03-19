<?php
namespace App\Exceptions;

use Exception;

class GroupNotFoundException extends Exception
{
    public function render()
    {
        return response([
            'api_version' => '1.0',
            "error" => [
                'code' => 404,
                "message" => "NotFound",
                "reason" => "NotFoundException",
                "errors" => [
                    [
                        "domain" => null,
                        "reason" => "GroupNotFoundException",
                        "message" => "Group Not Found",
                    ],
                ],
            ],
        ], 404);
    }
}
