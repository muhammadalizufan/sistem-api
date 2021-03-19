<?php
namespace App\Exceptions;

use Exception;

class PermissionNotFoundException extends Exception
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
                        "reason" => "PermissionNotFoundException",
                        "message" => "Permission Not Found",
                    ],
                ],
            ],
        ], 404);
    }
}
